<?php
header('Content-Type: application/json; charset=utf-8');
include_once(dirname(__DIR__, 2) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');
include_once(ROOT_DIR . '/includes/nodes_extend_cud.inc.php');
include_once(ROOT_DIR . '/includes/annotation.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');
include_once(ROOT_DIR . '/includes/latte_queue_manager.inc.php');

$user = new User($client);
$user_id = $user->checkSession();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(array('error' => 'Login required'));
    exit();
}

if ($user->hasEditRights($user->myRole) < 1) {
    http_response_code(403);
    echo json_encode(array('error' => 'Insufficient permissions to link entities'));
    exit();
}

$tokenManager = new CsrfTokenManager();
$token = $_POST['csrf'] ?? '';
if (!$tokenManager->checkToken($token)) {
    http_response_code(403);
    echo json_encode(array('error' => 'Invalid or expired session token'));
    exit();
}

$texID = isset($_POST['texNeoid']) ? (int)$_POST['texNeoid'] : 0;
$entityID = isset($_POST['sourceNeoID']) ? (int)$_POST['sourceNeoID'] : 0;
$selection = isset($_POST['selection']) ? trim((string)$_POST['selection']) : '';
$matchCase = isset($_POST['matchCase']) ? filter_var($_POST['matchCase'], FILTER_VALIDATE_BOOLEAN) : true;
$wholeWord = isset($_POST['wholeWord']) ? filter_var($_POST['wholeWord'], FILTER_VALIDATE_BOOLEAN) : true;

if ($texID <= 0 || $entityID <= 0 || $selection === '') {
    http_response_code(400);
    echo json_encode(array('error' => 'Invalid parameters provided'));
    exit();
}

// fetch text string and entity details from neo4j
$node = new CUDNode($client);
$annotation = new Annotation(false, $node->gettsx());

try {
    $textProp = defined('TEXNODETEXT') ? TEXNODETEXT : 'text';
    $textQuery = 'MATCH (t) WHERE id(t) = $texid RETURN t.' . $textProp . ' AS textContent LIMIT 1';
    $resText = $client->run($textQuery, ['texid' => $texID]);
    if ($resText->count() === 0) {
        http_response_code(404);
        echo json_encode(array('error' => 'Text node not found'));
        exit();
    }
    $textContent = (string)($resText->first()->get('textContent') ?? '');

    $etQuery = 'MATCH (e) WHERE id(e) = $entityid RETURN labels(e)[0] AS etLabel LIMIT 1';
    $resEt = $client->run($etQuery, ['entityid' => $entityID]);
    if ($resEt->count() === 0) {
        http_response_code(404);
        echo json_encode(array('error' => 'Entity node not found'));
        exit();
    }
    $entityLabel = (string)($resEt->first()->get('etLabel') ?? '');

    // get confirmed/rejected annotations on this text to avoid overwriting them (automated suggestions can be promoted)
    $startProp = defined('ANNOSTART') ? ANNOSTART : 'starts';
    $stopProp = defined('ANNOSTOP') ? ANNOSTOP : 'stops';
    $existingQuery = 'MATCH (t)-[:contains]->(a) WHERE id(t) = $texid AND (a:Annotation OR a:Annotation_rejected)
                      RETURN a.' . $startProp . ' AS s, a.' . $stopProp . ' AS e';
    $resExisting = $client->run($existingQuery, ['texid' => $texID]);
    $occupiedRanges = [];
    foreach ($resExisting as $rec) {
        $occupiedRanges[] = [
            's' => (int)$rec->get('s'),
            'e' => (int)$rec->get('e')
        ];
    }

    // find all occurrences in multibyte character offsets
    $chars = mb_str_split($textContent, 1, 'UTF-8');
    $totalChars = count($chars);
    $searchChars = mb_str_split($selection, 1, 'UTF-8');
    $searchLen = count($searchChars);
    $searchLower = mb_strtolower($selection, 'UTF-8');

    $candidates = [];
    for ($i = 0; $i <= $totalChars - $searchLen; $i++) {
        $slice = array_slice($chars, $i, $searchLen);
        $sliceStr = implode('', $slice);

        $matched = false;
        if ($matchCase) {
            $matched = ($sliceStr === $selection);
        } else {
            $matched = (mb_strtolower($sliceStr, 'UTF-8') === $searchLower);
        }

        if ($matched) {
            $startPos = $i;
            $endPos = $i + $searchLen - 1;

            // check word boundaries if requested
            if ($wholeWord) {
                if ($startPos > 0) {
                    $prevChar = $chars[$startPos - 1];
                    if (!preg_match('/[\s\p{P}]/u', $prevChar)) {
                        continue;
                    }
                }
                if ($endPos < $totalChars - 1) {
                    $nextChar = $chars[$endPos + 1];
                    if (!preg_match('/[\s\p{P}]/u', $nextChar)) {
                        continue;
                    }
                }
            }

            // verify non-overlapping with any existing annotation
            $overlaps = false;
            foreach ($occupiedRanges as $range) {
                if ($startPos <= $range['e'] && $endPos >= $range['s']) {
                    $overlaps = true;
                    break;
                }
            }

            if (!$overlaps) {
                $candidates[] = [
                    'start' => $startPos,
                    'stop' => $endPos,
                    'matched_text' => $sliceStr
                ];
            }
        }
    }

    if (empty($candidates)) {
        echo json_encode(array(
            'success' => true,
            'created_count' => 0,
            'msg' => 'No additional unannotated occurrences found.',
            'annotations' => []
        ));
        exit();
    }

    // batch insert all candidates in a single transaction
    $node->startTransaction();
    $userNeoId = (int)$user->neoId;
    $createdAnnotations = [];

    foreach ($candidates as $cand) {
        $cStart = $cand['start'];
        $cStop = $cand['stop'];

        $annoData = [
            $startProp => $cStart,
            $stopProp => $cStop
        ];

        $createAnnotation = $node->createNewNode(ANNONODE, $annoData, true);
        $annoUid = $annotation->fetchAnnotationUUID($createAnnotation);

        $node->connectNodes($userNeoId, $createAnnotation, 'priv_created');
        $node->connectNodes($createAnnotation, $entityID, 'references');
        $node->connectNodes($texID, $createAnnotation, 'contains');

        $createdAnnotations[] = [
            'uuid' => $annoUid,
            'neoid' => $createAnnotation,
            'start' => $cStart,
            'stop' => $cStop,
            'type' => $entityLabel,
            'text' => $cand['matched_text']
        ];
    }

    // ensure variant relation is established
    try {
        $node->createVariantRelation($selection, $entityID);
    } catch (\Throwable $ignored) {}

    $node->commitTransaction();

    // Promote any matching automatic suggestions or legacy Annotation_auto nodes covered by newly linked spans.
    foreach ($createdAnnotations as $created) {
        try {
            LatteQueueManager::promoteSuggestion($client, $texID, (int)$created['start'], (int)$created['stop']);
        } catch (\Throwable $ignored) {}
        try {
            $client->run(
                'MATCH (t)-[:contains]->(aa:Annotation_auto)
                 WHERE id(t) = $texid AND aa.' . $startProp . ' <= $stop AND aa.' . $stopProp . ' >= $start
                 DETACH DELETE aa',
                ['texid' => $texID, 'start' => (int)$created['start'], 'stop' => (int)$created['stop']]
            );
        } catch (\Throwable $ignored) {}
    }

    echo json_encode(array(
        'success' => true,
        'created_count' => count($createdAnnotations),
        'msg' => 'Successfully linked ' . count($createdAnnotations) . ' occurrences to ' . $entityLabel . '.',
        'entity_type' => $entityLabel,
        'annotations' => $createdAnnotations
    ));

} catch (\Throwable $th) {
    if (isset($node) && $node->gettsx()) {
        $node->rollbackTransaction();
    }
    http_response_code(500);
    echo json_encode(array('error' => 'Batch linking failed: ' . $th->getMessage()));
}
exit();
?>
