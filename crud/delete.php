<?php
include_once(dirname(__DIR__) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');
include_once(ROOT_DIR . '/includes/nodes_extend_cud.inc.php');
include_once(ROOT_DIR . '/includes/navbar.inc.php');

// Make users return to the page they were on when clicking delete.
$baseUrl = defined('BASE_URL') ? BASE_URL : '';
$cancelUrl = $baseUrl !== '' ? $baseUrl : '/';
$isSafeRedirectUrl = function ($url) {
    if (!is_string($url) || $url === '') {
        return false;
    }
    if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
        return true;
    }
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, array('http', 'https'), true);
    }
    return false;
};

if (isset($_SERVER['HTTP_REFERER'])) {
    $origin = $_SERVER['HTTP_REFERER']; 
    if ($isSafeRedirectUrl($origin)) {
        $expires = time() + 86400;
        setcookie("referrer", $origin, $expires, '/');
        $cancelUrl = $origin;
    }
} elseif (isset($_COOKIE['referrer']) && $isSafeRedirectUrl($_COOKIE['referrer'])) {
    $cancelUrl = $_COOKIE['referrer'];
}

if (!isset($_GET['id'])) {
    header('Location: ' . ($baseUrl !== '' ? $baseUrl : '/'));
    exit;
} else {
    $id = (int)$_GET['id']; 
}

$user = new User($client);
$user_id = $user->checkSession();
if ($user_id === false) {
    $loginUrl = ($baseUrl !== '' ? $baseUrl : '') . '/user/login.php?redir=' . urlencode($_SERVER['REQUEST_URI']);
    header("Location: $loginUrl");
    exit;
}

$tokenManager = new CsrfTokenManager();
$token = $tokenManager->generateToken(); 

// Graph helper and node label retrieval.
$graph = new CUDNode($client);  
$egoLabel = $graph->fetchLabelById($id); 
if ($egoLabel === null) {
    header('Location: ' . ($baseUrl !== '' ? $baseUrl : '') . '/error.php');
    exit;
}

$egoData = $graph->fetchEtById($id);
$egoModel = $graph->fetchModelByLabel($egoLabel); 

// Safe property retriever for Laudis Neo4j nodes.
function getSafeProperty($node, $key, $default = null) {
    if (!$node) return $default;
    $props = $node->getProperties();
    return $props->hasKey($key) ? $props->get($key) : $default;
}

// Determine node category and user-facing strings.
if ($egoLabel === TEXNODE) {
    $nodetype_string = 'a Text node'; 
    $ntype = 'text'; 
} elseif (($egoLabel === ANNONODE) || ($egoLabel === 'Annotation_auto')) {
    $nodetype_string = 'an Annotation node'; 
    $ntype = 'anno'; 
} elseif (array_key_exists($egoLabel, CORENODES)) {
    $nodetype_string = 'an Entity node'; 
    $ntype = 'entity'; 
} else {
    header('Location: ' . ($baseUrl !== '' ? $baseUrl : '') . '/error.php');
    exit;
}

// Fetch detailed metadata for the target node.
$targetDetails = [];
if ($ntype === 'anno') {
    $qAnno = 'MATCH (a) WHERE id(a) = $id
              OPTIONAL MATCH (t:Text)-[:contains]->(a)
              OPTIONAL MATCH (a)-[:references]-(e)
              OPTIONAL MATCH (e)-[:same_as]-(v:Variant)
              RETURN a, t, e, id(e) as entity_id, collect(DISTINCT v.variant) AS variants';
    $annoRes = $client->run($qAnno, ['id' => $id]);
    if (count($annoRes) > 0) {
        $row = $annoRes[0];
        $aNode = $row->get('a');
        $tNode = $row->get('t');
        $eNode = $row->get('e');
        $variants = $row->get('variants');
        $varList = $variants ? (is_array($variants) ? $variants : iterator_to_array($variants)) : [];

        $starts = getSafeProperty($aNode, 'starts');
        $stops = getSafeProperty($aNode, 'stops');
        $uid = getSafeProperty($aNode, 'uid');
        
        $textId = getSafeProperty($tNode, 'text_id');
        $textContent = getSafeProperty($tNode, 'text');
        $textName = getSafeProperty($tNode, 'name', getSafeProperty($tNode, 'title', $textId ? ('Text #' . $textId) : null));
        
        $span = '';
        if ($textContent !== null && $starts !== null && $stops !== null && $stops >= $starts) {
            $span = mb_substr($textContent, (int)$starts, (int)($stops - $starts) + 1);
        }
        
        $entityLabels = $eNode ? $eNode->getLabels()->toArray() : [];
        $entityType = count($entityLabels) > 0 ? $entityLabels[0] : null;
        $entityId = $row->get('entity_id') !== null ? (int)$row->get('entity_id') : null;
        $entityName = getSafeProperty($eNode, 'named', getSafeProperty($eNode, 'name'));
        if (!$entityName && count($varList) > 0) {
            $entityName = $varList[0];
        }
        
        $targetDetails = [
            'uid' => $uid,
            'starts' => $starts,
            'stops' => $stops,
            'span' => $span,
            'text_id' => $textId,
            'text_name' => $textName,
            'entity_id' => $entityId,
            'entity_type' => $entityType,
            'entity_name' => $entityName,
            'variants' => $varList
        ];
    }
} elseif ($ntype === 'entity') {
    $qEt = 'MATCH (e) WHERE id(e) = $id
            OPTIONAL MATCH (e)-[:same_as]-(v:Variant)
            OPTIONAL MATCH (t:Text)-[:contains]->(a:Annotation)-[:references]-(e)
            RETURN e, collect(DISTINCT v.variant) AS variants, count(DISTINCT t) AS textCount, count(DISTINCT a) AS annoCount';
    $etRes = $client->run($qEt, ['id' => $id]);
    if (count($etRes) > 0) {
        $row = $etRes[0];
        $eNode = $row->get('e');
        $variants = $row->get('variants');
        $varList = $variants ? (is_array($variants) ? $variants : iterator_to_array($variants)) : [];
        
        $uid = getSafeProperty($eNode, 'uid');
        $name = getSafeProperty($eNode, 'named', getSafeProperty($eNode, 'name'));
        if (!$name && count($varList) > 0) {
            $name = $varList[0];
        }
        
        $targetDetails = [
            'uid' => $uid,
            'name' => $name,
            'type' => $egoLabel,
            'variants' => $varList,
            'text_count' => (int)$row->get('textCount'),
            'anno_count' => (int)$row->get('annoCount')
        ];
    }
} elseif ($ntype === 'text') {
    $qTxt = 'MATCH (t:Text) WHERE id(t) = $id RETURN t';
    $txtRes = $client->run($qTxt, ['id' => $id]);
    if (count($txtRes) > 0) {
        $tNode = $txtRes[0]->get('t');
        $textId = getSafeProperty($tNode, 'text_id');
        $textName = getSafeProperty($tNode, 'name', getSafeProperty($tNode, 'title', $textId ? ('Text #' . $textId) : null));
        $language = getSafeProperty($tNode, 'latte_language');
        $uid = getSafeProperty($tNode, 'uid');
        $textContent = getSafeProperty($tNode, 'text', '');
        
        $targetDetails = [
            'uid' => $uid,
            'text_id' => $textId,
            'name' => $textName,
            'language' => $language,
            'snippet' => mb_substr($textContent, 0, 160) . (mb_strlen($textContent) > 160 ? '...' : '')
        ];
    }
}

// Calculate dependency and impact statistics.
$impactStats = [];
$isolatedEntityDetails = [];

if ($ntype === 'text') {
    $connectedAnnotations = $graph->distinctAnnotationsInText($id);
    $connectedEntities = $graph->distinctEntitiesInText($id); 
    $siloConnections = $graph->distinctSilosForText($id);
    $impactStats['annotations_count'] = count($connectedAnnotations);
    $impactStats['entities_count'] = count($connectedEntities);
    $impactStats['silos_count'] = count($siloConnections);

    $isolatedEntities = $graph->find_isolated_entities($connectedAnnotations);
    $impactStats['isolated_entities_count'] = count($isolatedEntities);
    if (!empty($isolatedEntities)) {
        $qIso = 'MATCH (e) WHERE id(e) IN $ids
                 OPTIONAL MATCH (e)-[:same_as]-(v:Variant)
                 RETURN e, id(e) as entity_id, labels(e) as labels, collect(DISTINCT v.variant) as variants';
        $isoRes = $client->run($qIso, ['ids' => $isolatedEntities]);
        foreach ($isoRes as $row) {
            $eNode = $row->get('e');
            $labels = $row->get('labels')->toArray();
            $label = count($labels) > 0 ? $labels[0] : 'Entity';
            $name = getSafeProperty($eNode, 'named', getSafeProperty($eNode, 'name'));
            $vars = $row->get('variants')->toArray();
            if (!$name && count($vars) > 0) {
                $name = $vars[0];
            }
            $isolatedEntityDetails[] = [
                'id' => (int)$row->get('entity_id'),
                'type' => $label,
                'name' => $name,
                'variants' => $vars,
                'uid' => getSafeProperty($eNode, 'uid')
            ];
        }
    }
} elseif ($ntype === 'anno') {
    $isolatedEntities = $graph->find_isolated_entities([$id]);
    $impactStats['isolated_entities_count'] = count($isolatedEntities);
    if (!empty($isolatedEntities)) {
        $qIso = 'MATCH (e) WHERE id(e) IN $ids
                 OPTIONAL MATCH (e)-[:same_as]-(v:Variant)
                 RETURN e, id(e) as entity_id, labels(e) as labels, collect(DISTINCT v.variant) as variants';
        $isoRes = $client->run($qIso, ['ids' => $isolatedEntities]);
        foreach ($isoRes as $row) {
            $eNode = $row->get('e');
            $labels = $row->get('labels')->toArray();
            $label = count($labels) > 0 ? $labels[0] : 'Entity';
            $name = getSafeProperty($eNode, 'named', getSafeProperty($eNode, 'name'));
            $vars = $row->get('variants')->toArray();
            if (!$name && count($vars) > 0) {
                $name = $vars[0];
            }
            $isolatedEntityDetails[] = [
                'id' => (int)$row->get('entity_id'),
                'type' => $label,
                'name' => $name,
                'variants' => $vars,
                'uid' => getSafeProperty($eNode, 'uid')
            ];
        }
    }
} elseif ($ntype === 'entity') {
    $entityConnections = $graph->findConnectedTexts($id);
    $impactStats['texts_count'] = count($entityConnections[0]);
    $impactStats['annotations_count'] = count($entityConnections[1]);
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete node #<?php echo $id; ?> - <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/stylePublic.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <?php
      include_once(ROOT_DIR . '/includes/matomo.inc.php');
      echo renderMatomoTracking();
    ?>
</head>
<body class="bg-slate-100 text-slate-800 w-full antialiased min-h-screen flex flex-col">
    <?php
        $navbar = new Navbar(); 
        echo $navbar->getNav();
    ?>

    <main class="max-w-4xl w-full mx-auto px-4 py-8 flex-1">
        <!-- Back Navigation -->
        <div class="mb-5">
            <a href="<?php echo htmlspecialchars($cancelUrl, ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
                <i class="fas fa-arrow-left"></i>
                <span>Back to previous page</span>
            </a>
        </div>

        <!-- Danger Header Card -->
        <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden mb-6">
            <div class="p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b border-slate-100">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-red-100 text-red-600 flex items-center justify-center shrink-0 shadow-2xs">
                        <i class="fas fa-trash-alt text-xl"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-bold tracking-tight text-slate-900">Delete node #<?php echo $id; ?></h1>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-700">Permanent action</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            You are about to remove <?php echo htmlspecialchars($nodetype_string, ENT_QUOTES, 'UTF-8'); ?> from this graph database.
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-amber-50/70 border-t border-amber-200/60 px-6 py-3.5 flex items-start gap-3">
                <i class="fas fa-exclamation-triangle text-amber-600 mt-0.5 shrink-0 text-sm"></i>
                <div class="text-xs text-amber-800">
                    <span class="font-semibold">Irreversible operation:</span> Once deleted, this node and its uniquely dependent relationships cannot be recovered. Please carefully review the implications below.
                </div>
            </div>
        </div>

        <!-- Cascading Entity Deletion Alert (if isolated entity will also be deleted) -->
        <?php if (!empty($isolatedEntityDetails)): ?>
            <div class="p-4 rounded-xl bg-red-50/90 border border-red-200 shadow-2xs mb-6">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-lg bg-red-100 text-red-600 flex items-center justify-center shrink-0 text-base mt-0.5 shadow-2xs">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-red-900">Cascading entity deletion</h3>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-200 text-red-800">Isolated node</span>
                        </div>
                        <?php foreach ($isolatedEntityDetails as $iso): ?>
                            <p class="text-xs text-red-800 mt-1">
                                Because this annotation is the only reference to <strong><?php echo htmlspecialchars($iso['type'], ENT_QUOTES, 'UTF-8'); ?> node #<?php echo $iso['id']; ?><?php echo !empty($iso['name']) ? ' (' . htmlspecialchars($iso['name'], ENT_QUOTES, 'UTF-8') . ')' : ''; ?></strong>, deleting this annotation will also permanently delete the <strong><?php echo htmlspecialchars($iso['type'], ENT_QUOTES, 'UTF-8'); ?> node</strong> and all associated spelling variants from the knowledgebase.
                            </p>
                            <div class="mt-2.5 flex flex-wrap items-center gap-2 text-xs">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-white text-slate-800 border border-red-200 font-medium shadow-2xs">
                                    <i class="fas fa-map-marker-alt text-red-500 text-[10px]"></i>
                                    <span>Target: <strong><?php echo htmlspecialchars($iso['type'], ENT_QUOTES, 'UTF-8'); ?> #<?php echo $iso['id']; ?></strong></span>
                                </span>
                                <?php if (!empty($iso['name'])): ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-white text-slate-800 border border-red-200 font-medium shadow-2xs">
                                        <span>Name: <strong><?php echo htmlspecialchars($iso['name'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($iso['variants'])): ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-white text-slate-700 border border-red-200 text-[11px] shadow-2xs">
                                        <span>Variants: <?php echo htmlspecialchars(implode(', ', $iso['variants']), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($iso['uid'])): ?>
                                    <span class="font-mono text-[11px] text-slate-600 bg-white px-2 py-1 rounded border border-red-200 shadow-2xs">UID: <?php echo htmlspecialchars($iso['uid'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Target Node Overview Card -->
        <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-6 mb-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-cube text-slate-400 text-sm"></i>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700">Target node overview</h2>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold <?php
                        if ($ntype === 'anno') {
                            echo 'bg-amber-50 text-amber-700 border border-amber-200';
                        } elseif ($ntype === 'entity') {
                            echo 'bg-indigo-50 text-indigo-700 border border-indigo-200';
                        } else {
                            echo 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                        }
                    ?>">
                        <i class="fas fa-tag text-[10px]"></i>
                        <span><?php echo htmlspecialchars($egoLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                        <span>Neo4j ID:</span>
                        <span class="font-mono font-bold"><?php echo $id; ?></span>
                    </span>
                </div>
            </div>

            <?php if ($ntype === 'anno'): ?>
                <!-- Annotation Details -->
                <div class="space-y-4">
                    <?php if (!empty($targetDetails['span'])): ?>
                        <div class="p-3.5 bg-amber-50/60 rounded-lg border border-amber-200/80">
                            <span class="text-xs uppercase font-bold tracking-wider text-amber-700 block mb-1">Annotated text span</span>
                            <span class="text-base font-semibold text-slate-900">“<?php echo htmlspecialchars($targetDetails['span'], ENT_QUOTES, 'UTF-8'); ?>”</span>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-xs">
                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="text-slate-500 block mb-1 font-medium">Text offset range</span>
                            <span class="font-mono text-slate-800 font-semibold">
                                <?php echo htmlspecialchars((string)($targetDetails['starts'] ?? '?'), ENT_QUOTES, 'UTF-8'); ?> &rarr; <?php echo htmlspecialchars((string)($targetDetails['stops'] ?? '?'), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="text-slate-500 block mb-1 font-medium">Source text</span>
                            <span class="font-semibold text-slate-800 truncate block" title="<?php echo htmlspecialchars($targetDetails['text_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($targetDetails['text_name'] ?? ('Text #' . ($targetDetails['text_id'] ?? 'unknown')), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="text-slate-500 block mb-1 font-medium">Referenced entity</span>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-semibold text-slate-800 truncate block">
                                    <?php echo htmlspecialchars(($targetDetails['entity_type'] ? $targetDetails['entity_type'] . (!empty($targetDetails['entity_id']) ? ' #' . $targetDetails['entity_id'] : '') . ': ' : '') . ($targetDetails['entity_name'] ?? 'None'), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <?php if (!empty($isolatedEntityDetails)): ?>
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 border border-red-200" title="This entity has no other annotations and will also be deleted">
                                        <i class="fas fa-trash-alt text-[9px]"></i> Will be deleted
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($targetDetails['uid'])): ?>
                        <div class="text-xs text-slate-500 flex items-center gap-2 pt-1">
                            <span class="font-medium">Unique identifier:</span>
                            <span class="font-mono text-slate-700 bg-slate-100 px-2 py-0.5 rounded border border-slate-200"><?php echo htmlspecialchars($targetDetails['uid'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($ntype === 'entity'): ?>
                <!-- Entity Details -->
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="text-slate-500 block mb-1 font-medium">Entity label / name</span>
                            <span class="text-sm font-bold text-slate-900 block"><?php echo htmlspecialchars($targetDetails['name'] ?? 'Unnamed entity', ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="text-slate-500 block mb-1 font-medium">Connected text occurrences</span>
                            <span class="text-sm font-semibold text-slate-800">
                                <?php echo (int)($targetDetails['text_count'] ?? 0); ?> text<?php echo ($targetDetails['text_count'] ?? 0) !== 1 ? 's' : ''; ?>
                                (<?php echo (int)($targetDetails['anno_count'] ?? 0); ?> annotation<?php echo ($targetDetails['anno_count'] ?? 0) !== 1 ? 's' : ''; ?>)
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($targetDetails['variants'])): ?>
                        <div class="text-xs">
                            <span class="text-slate-500 block mb-1.5 font-medium">Associated spelling variants:</span>
                            <div class="flex flex-wrap gap-1.5">
                                <?php foreach ($targetDetails['variants'] as $variant): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200 font-medium">
                                        <?php echo htmlspecialchars($variant, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($targetDetails['uid'])): ?>
                        <div class="text-xs text-slate-500 flex items-center gap-2 pt-1">
                            <span class="font-medium">Entity UID:</span>
                            <span class="font-mono text-slate-700 bg-slate-100 px-2 py-0.5 rounded border border-slate-200"><?php echo htmlspecialchars($targetDetails['uid'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($ntype === 'text'): ?>
                <!-- Text Details -->
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="text-slate-500 block mb-1 font-medium">Text title / name</span>
                            <span class="text-sm font-bold text-slate-900 block"><?php echo htmlspecialchars($targetDetails['name'] ?? ('Text #' . $id), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="text-slate-500 block mb-1 font-medium">Detected language</span>
                            <span class="text-sm font-semibold text-slate-800"><?php echo htmlspecialchars($targetDetails['language'] ?? 'Not specified', ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>

                    <?php if (!empty($targetDetails['snippet'])): ?>
                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-xs">
                            <span class="text-slate-500 block mb-1 font-medium">Content snippet</span>
                            <p class="text-slate-700 italic">“<?php echo htmlspecialchars($targetDetails['snippet'], ENT_QUOTES, 'UTF-8'); ?>”</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Deletion Impact Assessment Card -->
        <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-6 mb-6">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3 mb-4">
                <i class="fas fa-project-diagram text-slate-400 text-sm"></i>
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700">Deletion impact assessment</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Cascade Removal Box -->
                <div class="p-4 rounded-xl bg-red-50/50 border border-red-200/80">
                    <div class="flex items-center gap-2 mb-2.5">
                        <i class="fas fa-minus-circle text-red-600 text-sm"></i>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-red-900">What will be removed</h3>
                    </div>
                    <ul class="space-y-2 text-xs text-slate-700">
                        <?php if ($ntype === 'anno'): ?>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check text-red-500 mt-0.5 shrink-0 text-[10px]"></i>
                                <span>The unique <strong>Annotation node #<?php echo $id; ?></strong><?php echo !empty($targetDetails['span']) ? ' (“' . htmlspecialchars($targetDetails['span'], ENT_QUOTES, 'UTF-8') . '”)' : ''; ?> and its span relationship in the text.</span>
                            </li>
                            <?php if (!empty($isolatedEntityDetails)): ?>
                                <?php foreach ($isolatedEntityDetails as $iso): ?>
                                    <li class="flex items-start gap-2">
                                        <i class="fas fa-check text-red-500 mt-0.5 shrink-0 text-[10px]"></i>
                                        <span><strong><?php echo htmlspecialchars($iso['type'], ENT_QUOTES, 'UTF-8'); ?> node #<?php echo $iso['id']; ?></strong><?php echo !empty($iso['name']) ? ' (“' . htmlspecialchars($iso['name'], ENT_QUOTES, 'UTF-8') . '”)' : ''; ?> &mdash; isolated entity with no other annotations.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <i class="fas fa-check text-red-500 mt-0.5 shrink-0 text-[10px]"></i>
                                        <span>All <strong>knowledge base and spelling variant connections</strong> (<?php echo !empty($iso['variants']) ? htmlspecialchars(implode(', ', $iso['variants']), ENT_QUOTES, 'UTF-8') : 'none'; ?>) attached to the <?php echo htmlspecialchars($iso['type'], ENT_QUOTES, 'UTF-8'); ?> node.</span>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-check text-red-500 mt-0.5 shrink-0 text-[10px]"></i>
                                    <span>Reference link between this annotation and the entity (the entity itself will remain intact).</span>
                                </li>
                            <?php endif; ?>

                        <?php elseif ($ntype === 'entity'): ?>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check text-red-500 mt-0.5 shrink-0 text-[10px]"></i>
                                <span>This entity node from the global knowledgebase.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check text-red-500 mt-0.5 shrink-0 text-[10px]"></i>
                                <span>All <strong><?php echo (int)($impactStats['annotations_count'] ?? 0); ?> annotation(s)</strong> pointing to this entity across all texts.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check text-red-500 mt-0.5 shrink-0 text-[10px]"></i>
                                <span>All associated spelling variants and see-also references.</span>
                            </li>

                        <?php elseif ($ntype === 'text'): ?>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check text-red-500 mt-0.5 shrink-0 text-[10px]"></i>
                                <span>This entire text record and its raw text content.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check text-red-500 mt-0.5 shrink-0 text-[10px]"></i>
                                <span>All <strong><?php echo (int)($impactStats['annotations_count'] ?? 0); ?> annotations</strong> connected to this text.</span>
                            </li>
                            <?php if (!empty($isolatedEntityDetails)): ?>
                                <?php foreach ($isolatedEntityDetails as $iso): ?>
                                    <li class="flex items-start gap-2">
                                        <i class="fas fa-check text-red-500 mt-0.5 shrink-0 text-[10px]"></i>
                                        <span><strong><?php echo htmlspecialchars($iso['type'], ENT_QUOTES, 'UTF-8'); ?> node #<?php echo $iso['id']; ?></strong><?php echo !empty($iso['name']) ? ' (“' . htmlspecialchars($iso['name'], ENT_QUOTES, 'UTF-8') . '”)' : ''; ?> &mdash; uniquely connected to this text.</span>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check text-red-500 mt-0.5 shrink-0 text-[10px]"></i>
                                <span>External silo links (<strong><?php echo (int)($impactStats['silos_count'] ?? 0); ?> connection(s)</strong>).</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Preserved Graph Data Box -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="flex items-center gap-2 mb-2.5">
                        <i class="fas fa-shield-alt text-slate-500 text-sm"></i>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">What remains intact</h3>
                    </div>
                    <ul class="space-y-2 text-xs text-slate-600">
                        <?php if ($ntype === 'anno'): ?>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-shield-alt text-emerald-500 mt-0.5 shrink-0 text-[10px]"></i>
                                <span>The parent text content and all other annotations in it remain completely preserved.</span>
                            </li>
                            <?php if (empty($isolatedEntityDetails)): ?>
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-shield-alt text-emerald-500 mt-0.5 shrink-0 text-[10px]"></i>
                                    <span>The referenced entity is shared with other annotations and will remain in the knowledgebase.</span>
                                </li>
                            <?php endif; ?>

                        <?php elseif ($ntype === 'entity'): ?>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-shield-alt text-emerald-500 mt-0.5 shrink-0 text-[10px]"></i>
                                <span>All connected texts (<strong><?php echo (int)($impactStats['texts_count'] ?? 0); ?> text(s)</strong>) will remain in the database; only their annotations to this entity are removed.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-shield-alt text-emerald-500 mt-0.5 shrink-0 text-[10px]"></i>
                                <span>All other entities and their relations are unchanged.</span>
                            </li>

                        <?php elseif ($ntype === 'text'): ?>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-shield-alt text-emerald-500 mt-0.5 shrink-0 text-[10px]"></i>
                                <span>Shared entities that also appear in other texts will remain in the database.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-shield-alt text-emerald-500 mt-0.5 shrink-0 text-[10px]"></i>
                                <span>All other texts, users, and global configurations remain untouched.</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Confirmation and Submission Card -->
        <div class="bg-rose-50/50 rounded-xl shadow-xs border border-rose-200 p-6">
            <h3 class="text-sm font-bold text-slate-900 mb-1">Confirmation required</h3>
            <p class="text-xs text-slate-600 mb-4">
                To prevent accidental loss of graph data, check the confirmation box below to enable deletion.
            </p>

            <form action="delete_action.php" method="post" id="deleteForm">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="ID" value="<?php echo $id; ?>">
                <input type="hidden" name="referrer" value="<?php echo htmlspecialchars($cancelUrl, ENT_QUOTES, 'UTF-8'); ?>">

                <label for="confirmbox" class="flex items-start gap-3 p-3.5 bg-white hover:bg-rose-50/60 rounded-lg border border-rose-200 cursor-pointer transition select-none">
                    <input type="checkbox" required id="confirmbox" name="confirmbox" value="userconfirmation" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-red-600 focus:ring-red-500 cursor-pointer">
                    <div class="text-xs text-slate-700">
                        <span class="font-semibold text-slate-900 block mb-0.5">
                            I understand the consequences and confirm that I want to delete node #<?php echo $id; ?><?php
                                if (!empty($isolatedEntityDetails)) {
                                    $isoLabels = [];
                                    foreach ($isolatedEntityDetails as $iso) {
                                        $isoLabels[] = $iso['type'] . ' node #' . $iso['id'] . (!empty($iso['name']) ? ' (“' . $iso['name'] . '”)' : '');
                                    }
                                    echo ' and ' . htmlspecialchars(implode(', ', $isoLabels), ENT_QUOTES, 'UTF-8');
                                }
                            ?>
                        </span>
                        <span class="text-slate-500">This operation will execute a cascading delete transaction in the Neo4j database.</span>
                    </div>
                </label>

                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <button type="submit" name="deleteButton" id="deleteBtn" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium text-sm transition shadow-xs cursor-pointer opacity-50 pointer-events-none" disabled>
                        <i class="fas fa-trash-alt"></i>
                        <span>Delete permanently</span>
                    </button>
                    <a href="<?php echo htmlspecialchars($cancelUrl, ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-white hover:bg-slate-50 text-slate-700 font-medium text-sm border border-slate-300 transition shadow-2xs">
                        <i class="fas fa-times"></i>
                        <span>Cancel</span>
                    </a>
                </div>
            </form>
        </div>
    </main>

    <footer class="mt-auto py-6 text-center text-xs text-slate-400">
        <span><?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?> &bull; Node deletion manager</span>
    </footer>

    <script>
        (function() {
            var confirmBox = document.getElementById('confirmbox');
            var deleteBtn = document.getElementById('deleteBtn');
            var deleteForm = document.getElementById('deleteForm');

            if (confirmBox && deleteBtn) {
                confirmBox.addEventListener('change', function() {
                    if (this.checked) {
                        deleteBtn.disabled = false;
                        deleteBtn.classList.remove('opacity-50', 'pointer-events-none');
                    } else {
                        deleteBtn.disabled = true;
                        deleteBtn.classList.add('opacity-50', 'pointer-events-none');
                    }
                });
            }

            if (deleteForm && deleteBtn) {
                deleteForm.addEventListener('submit', function() {
                    deleteBtn.disabled = true;
                    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Deleting...</span>';
                });
            }
        })();
    </script>
</body>
</html>
<?php
    $graph = null; 
?>
