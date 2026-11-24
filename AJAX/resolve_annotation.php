<?php

header('Content-Type: application/json; charset=utf-8');
include_once(dirname(__DIR__) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');
include_once(ROOT_DIR . '/includes/nodes_extend_cud.inc.php');
include_once(ROOT_DIR . '/includes/annotation.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');

$annotationId = isset($_GET['annotation']) ? trim($_GET['annotation']) : '';
$textNeoIdParam = isset($_GET['texid']) && is_numeric($_GET['texid']) ? (int)$_GET['texid'] : 0;

if ($annotationId === '') {
  die(json_encode(array('code' => -1, 'error' => 'This node type is not supported!')));
}

$formattedResponse = array(
  'annotation' => array(),
  'author' => array(),
  'entity' => array(),
  'variants' => array()
);

$graph = new CUDNode($client);
$annotation = new Annotation($client);

$user = new User($client);
$user->checkSession();

$annoLabel = preg_replace('/[^a-zA-Z0-9_]/', '', (string)ANNONODE);

// Query the confirmed annotation, its author, referenced entity, and variants in one labeled round-trip.
$annoQuery = "
  MATCH (a:{$annoLabel} {uid: \$uid})
  OPTIONAL MATCH (a)<-[:priv_created]-(u:priv_user)
  OPTIONAL MATCH (a)-[:references]-(e)
  OPTIONAL MATCH (e)-[:same_as]-(v:Variant)
  RETURN a, id(a) AS egoId, u.role AS author_role, u.name AS author_name, e, id(e) AS entityId, collect(DISTINCT {node: v, neoid: id(v)}) AS variants
  LIMIT 1
";
$annoRes = $client->run($annoQuery, ['uid' => $annotationId]);

if ($annoRes->count() > 0) {
  $row = $annoRes->first();
  $egoId = (int)$row->get('egoId');
  $annoNode = $row->get('a');
  $entityNode = $row->get('e');

  $authorRole = $row->get('author_role');
  $authorName = $row->get('author_name');
  if ($authorRole !== null || $authorName !== null) {
    $formattedResponse['author']['properties']['role'] = array('role', $authorRole, true);
    $formattedResponse['author']['properties']['name'] = array('name', $authorName, true);
  }

  $formattedResponse['annotationFields'] = NODEMODEL[ANNONODE];
  $allowedToEdit = $user->hasEditRights($user->myRole);
  foreach ($annoNode['properties'] as $key => $value) {
    if (array_key_exists($key, NODEMODEL[ANNONODE])) {
      $formattedResponse['annotation']['properties'][$key] = array($key, $value, $annotation->isProtectedKey($key), $allowedToEdit);
    }
  }

  if ($entityNode !== null) {
    $entityLabel = $entityNode['labels'][0];
    $entityId = (int)$row->get('entityId');
    $entityModel = NODEMODEL[$entityLabel] ?? [];
    $formattedResponse['entityFields'] = $entityModel;

    $entityData = array(
      'neoID' => $entityId,
      'properties' => array(),
      'type' => $entityLabel
    );
    foreach ($entityNode['properties'] as $property => $value) {
      if (array_key_exists($property, $entityModel)) {
        $entityData['properties'][$property] = array($property, $value, true, null);
      }
    }
    if (empty($entityData['properties'])) {
      $entityData['properties'] = (object)array();
    }

    $primaryKeyName = helper_extractPrimary($entityLabel);
    $insert = (strtolower($entityLabel) === strtolower(TEXNODE)) ? '' : 'URI/';
    $coreVal = $graph->extractCoreID($entityNode, $primaryKeyName, 'uid');
    $entityData['stableURI'] = array(trim(WEBURL, '/') . '/' . $insert . $entityLabel . '/' . $coreVal);

    $formattedResponse['entity'][] = $entityData;
  } else {
    $formattedResponse['entityFields'] = array();
    $formattedResponse['entity'][] = array();
  }

  $varData = array();
  $variantsRaw = $row->get('variants');
  if (is_iterable($variantsRaw)) {
    foreach ($variantsRaw as $vItem) {
      $vNode = $vItem['node'] ?? null;
      if ($vNode === null) {
        continue;
      }
      $vProps = $vNode['properties'];
      $varData['labelVariants'][] = [
        'DOMstring' => 'Label',
        'value' => $vProps['variant'] ?? null,
        'uid' => $vProps['uid'] ?? null,
        'neoID' => (int)($vItem['neoid'] ?? 0)
      ];
    }
  }
  $formattedResponse['variants'][] = $varData;

  $formattedResponse['annotation']['neoid'] = $egoId;
  $formattedResponse['mode'] = 'controll';
  $formattedResponse['code'] = 1;
  echo json_encode($formattedResponse);
  exit();
}

// Check legacy Annotation_auto nodes using a labeled lookup.
$autoRes = $client->run(
  'MATCH (a:Annotation_auto {uid: $uid}) RETURN a, id(a) AS egoId LIMIT 1',
  ['uid' => $annotationId]
);

if ($autoRes->count() > 0) {
  $row = $autoRes->first();
  $egoId = (int)$row->get('egoId');
  $node = $row->get('a');
  $annotKey = 'Automatic_annotation';

  $formattedResponse['annotationFields'] = $annotation->auto_model[$annotKey];
  $formattedResponse['neo_id_of_auto_anno'] = $egoId;

  foreach ($annotation->auto_model[$annotKey] as $key => $value) {
    $formattedResponse['annotation']['properties'][$key] = array($key, $node->getProperty($key), true, false);
  }

  $formattedResponse['annotation']['neoid'] = $egoId;
  $formattedResponse['mode'] = 'automated';
  $formattedResponse['code'] = 1;
  echo json_encode($formattedResponse);
  exit();
}

// Check whether the ID is a persistent suggestion stored on a text node.
$textLabel = preg_replace('/[^a-zA-Z0-9_]/', '', (string)(defined('TEXNODE') ? TEXNODE : 'Text'));
try {
  $sugRes = null;
  if ($textNeoIdParam > 0) {
    $scopedQuery = "
      MATCH (t:{$textLabel})
      WHERE id(t) = \$textNeoId AND t.latte_suggestions CONTAINS \$annotationId
      RETURN id(t) AS text_neo_id, t.latte_suggestions AS suggestions
      LIMIT 1
    ";
    $sugRes = $client->run($scopedQuery, ['textNeoId' => $textNeoIdParam, 'annotationId' => $annotationId]);
  }
  if ($sugRes === null || $sugRes->count() === 0) {
    $sugQuery = "
      MATCH (t:{$textLabel})
      WHERE t.latte_suggestions CONTAINS \$annotationId
      RETURN id(t) AS text_neo_id, t.latte_suggestions AS suggestions
      LIMIT 1
    ";
    $sugRes = $client->run($sugQuery, ['annotationId' => $annotationId]);
  }

  if ($sugRes->count() > 0) {
    $row = $sugRes->first();
    $textNeoId = (int)$row->get('text_neo_id');
    $rawSuggestions = $row->get('suggestions');
    $sugList = is_string($rawSuggestions) ? json_decode($rawSuggestions, true) : (is_array($rawSuggestions) ? $rawSuggestions : []);

    $matched = null;
    if (is_array($sugList)) {
      foreach ($sugList as $s) {
        if (($s['id'] ?? ($s['annotation'] ?? '')) === $annotationId) {
          $matched = $s;
          break;
        }
      }
    }

    if ($matched !== null) {
      $start = (int)($matched['start'] ?? ($matched['startPos'] ?? 0));
      $stop = (int)($matched['stop'] ?? ($matched['endPos'] ?? 0));
      $detectedType = (string)($matched['label'] ?? ($matched['labelTex'] ?? 'Entity'));
      $allowedToEdit = ($user->hasEditRights($user->myRole) >= 1);

      $formattedResponse['mode'] = 'automated';
      $formattedResponse['code'] = 1;
      $formattedResponse['suggestion_id'] = $annotationId;
      $formattedResponse['text_neo_id'] = $textNeoId;
      $formattedResponse['neo_id_of_auto_anno'] = 0;
      $formattedResponse['can_remove'] = $allowedToEdit;
      $formattedResponse['annotation']['neoid'] = 0;
      $formattedResponse['annotation']['properties'] = [
        ANNOSTART => [ANNOSTART, $start, true, false],
        ANNOSTOP => [ANNOSTOP, $stop, true, false],
        'detected_type' => ['detected_type', $detectedType, true, false],
        'source' => ['source', 'LATTE connector', true, false]
      ];
      $formattedResponse['annotationFields'] = [
        ANNOSTART => ['Starts at', 'int'],
        ANNOSTOP => ['Stops at', 'int'],
        'detected_type' => ['Detected entity type', 'text'],
        'source' => ['Source', 'text']
      ];
      echo json_encode($formattedResponse);
      exit();
    }
  }
} catch (\Throwable $e) {
  error_log('Failed to lookup suggestion on text node: ' . $e->getMessage());
}

die(json_encode(array('code' => -1, 'error' => 'This node type is not supported!')));
?>
