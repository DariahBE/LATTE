<?php
header('Content-Type: application/json; charset=utf-8');

include_once(dirname(__DIR__) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');
$graph = new Node($client);

$approvedEntities = array_keys(CORENODES);
$approvedEntities[] = '';     // words of unknown type should be looked for too!

$caseSensitive = isset($_GET['casesensitive']) ? $_GET['casesensitive'] : false;
$caseSensitive = (strtolower($caseSensitive) == 'true') ? true : false;
$findEntityByType = false; 
if (isset($_GET['type'])) {
  $findEntityByType = $_GET['type'];
}
$findEntityByValue = isset($_GET['value']) ? $_GET['value'] : '';

$use_levenshtein = isset($_GET['allow_levenshtein']) ? $_GET['allow_levenshtein'] : false;
if (strtolower(trim($use_levenshtein)) === 'true') {
  $use_levenshtein = true;
} else {
  $use_levenshtein = false;
}
$max_levenshtein_hits = isset($_GET['levenshtein_items']) ? $_GET['levenshtein_items'] : 5;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100; 
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0; 
$texid = isset($_GET['texid']) ? (int)$_GET['texid'] : 0;

if (in_array($findEntityByType, $approvedEntities)) {
  $data = $graph->getEntities($findEntityByType, $findEntityByValue, $caseSensitive, $limit, $offset, $use_levenshtein, $max_levenshtein_hits);

  // query entities that already co-occur in this document for context-aware ranking
  $contextMentions = [];
  if ($texid > 0) {
    try {
      $contextQuery = 'MATCH (t)-[:contains]->(a:Annotation)-[:references]->(e) WHERE id(t) = $texid
                       RETURN id(e) AS eid, count(a) AS freq';
      $resContext = $client->run($contextQuery, ['texid' => $texid]);
      foreach ($resContext as $rec) {
        $contextMentions[(int)$rec->get('eid')] = (int)$rec->get('freq');
      }
    } catch (\Throwable $ignored) {}
  }

  $data['context_mentions'] = $contextMentions;
  if (!empty($data['nodes'])) {
    foreach ($data['nodes'] as &$nodeItem) {
      $eid = (int)$nodeItem[0];
      if (isset($contextMentions[$eid])) {
        $nodeItem['context_boost'] = true;
        $nodeItem['local_doc_freq'] = $contextMentions[$eid];
      } else {
        $nodeItem['context_boost'] = false;
        $nodeItem['local_doc_freq'] = 0;
      }
    }
    unset($nodeItem);

    // sort candidate entities with context-boosted matches placed first
    usort($data['nodes'], function($a, $b) {
      $boostA = !empty($a['context_boost']) ? 1 : 0;
      $boostB = !empty($b['context_boost']) ? 1 : 0;
      if ($boostA !== $boostB) {
        return $boostB <=> $boostA;
      }
      $freqA = $a['local_doc_freq'] ?? 0;
      $freqB = $b['local_doc_freq'] ?? 0;
      return $freqB <=> $freqA;
    });
  }

  /*
    returned data provides directives to connect the nodes to variants.
  */
  foreach ($data['labelvariants'] as $key => $value) {
    $variantNodeId = $value[0];
    foreach ($data['edges'] as $subkey => $subvalue) {
      if ($subvalue['startNodeId'] === $variantNodeId) {
        if (!(array_key_exists('variantOfEntity', $data['labelvariants'][$key][2]))) {
          $data['labelvariants'][$key][2]['variantOfEntity'] = array();
        }
        array_push($data['labelvariants'][$key][2]['variantOfEntity'], $subvalue['endNodeId']);
      }
    }
  }
  echo json_encode((array)$data);
} else {
  echo json_encode(array('message' => 'Invalid request'));
}
die();
?>
