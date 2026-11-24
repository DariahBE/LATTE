<?php
/**
 * Bulk insert endpoint for pre-annotated corpora (TEI-XML and CoNLL-U).
 *
 * Preserves multibyte character offsets for 1:1 alignment with LATTE text viewer.
 * Only accessible to admin users with valid CSRF token.
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (!defined('ROOT_DIR')) {
    include_once(dirname(__DIR__, 2) . '/config/config.inc.php');
}
include_once(ROOT_DIR . '/includes/csrf.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');
include_once(ROOT_DIR . '/includes/nodes_extend_cud.inc.php');
include_once(ROOT_DIR . '/includes/annotation.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');

$user = new User($client);
$userId = $user->checkSession();
if (!$userId || !$user->isAdmin()) {
    http_response_code(403);
    echo json_encode(array('error' => 'Forbidden', 'msg' => 'Insufficient rights, administrator access required.'));
    exit();
}

$tokenManager = new CsrfTokenManager();
$csrfToken = $_POST['csrf_token'] ?? '';
if (!$tokenManager->checkToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(array('error' => 'CSRF error', 'msg' => 'Invalid or expired session token.'));
    exit();
}

$format = isset($_POST['format']) ? strtolower(trim((string)$_POST['format'])) : '';
$customTitle = isset($_POST['title']) ? trim((string)$_POST['title']) : '';

// Retrieve file content from upload or raw post.
$rawContent = '';
if (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
    $rawContent = file_get_contents($_FILES['file']['tmp_name']);
    if ($customTitle === '') {
        $customTitle = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
    }
} else if (!empty($_POST['content'])) {
    $rawContent = (string)$_POST['content'];
}

if (trim($rawContent) === '') {
    http_response_code(400);
    echo json_encode(array('error' => 'Empty content', 'msg' => 'No content or file provided for import.'));
    exit();
}

/**
 * Parser for TEI-XML corpora.
 */
function parseTeiXml(string $xmlContent, string &$docTitle): array {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = $dom->loadXML($xmlContent, LIBXML_NOENT | LIBXML_NONET | LIBXML_NSCLEAN);
    if (!$loaded) {
        throw new Exception('Invalid XML content: ' . (libxml_get_last_error()->message ?? 'XML parse failure'));
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('tei', 'http://www.tei-c.org/ns/1.0');

    // Extract title from teiHeader if available.
    if ($docTitle === '') {
        $titles = $xpath->query('//tei:titleStmt/tei:title | //titleStmt/title');
        if ($titles && $titles->length > 0) {
            $docTitle = trim($titles->item(0)->textContent);
        }
    }

    // Locate text body.
    $bodyNodes = $xpath->query('//tei:text/tei:body | //text/body');
    if (!$bodyNodes || $bodyNodes->length === 0) {
        $bodyNodes = $xpath->query('//tei:text | //text');
    }
    if (!$bodyNodes || $bodyNodes->length === 0) {
        $rootNode = $dom->documentElement;
    } else {
        $rootNode = $bodyNodes->item(0);
    }

    $plainText = '';
    $annotations = [];
    $charOffset = 0;

    $knownEntityTags = [
        'rs' => true,
        'persname' => 'Person',
        'placename' => 'Location',
        'orgname' => 'Organization',
        'name' => true
    ];

    $traverse = function(DOMNode $node) use (&$traverse, &$plainText, &$annotations, &$charOffset, $knownEntityTags) {
        if ($node->nodeType === XML_TEXT_NODE) {
            $txt = $node->nodeValue;
            $plainText .= $txt;
            $charOffset += mb_strlen($txt, 'UTF-8');
            return;
        }

        if ($node->nodeType === XML_ELEMENT_NODE) {
            $tagName = strtolower($node->localName ?? $node->nodeName);

            // Handle line breaks and paragraphs gracefully.
            if ($tagName === 'lb') {
                $plainText .= "\n";
                $charOffset += 1;
                return;
            }

            $isEntity = isset($knownEntityTags[$tagName]);
            $start = $charOffset;

            // Recurse child nodes.
            foreach ($node->childNodes as $child) {
                $traverse($child);
            }

            if ($isEntity) {
                $stop = $charOffset - 1;
                if ($stop >= $start) {
                    $type = '';
                    if (is_string($knownEntityTags[$tagName])) {
                        $type = $knownEntityTags[$tagName];
                    }
                    if ($node->hasAttributes()) {
                        $typeAttr = $node->attributes->getNamedItem('type');
                        if ($typeAttr && trim($typeAttr->nodeValue) !== '') {
                            $type = trim($typeAttr->nodeValue);
                        }
                        $refAttr = $node->attributes->getNamedItem('ref') ??
                                   $node->attributes->getNamedItem('key') ??
                                   $node->attributes->getNamedItem('corresp');
                        $ref = $refAttr ? trim($refAttr->nodeValue) : '';
                    } else {
                        $ref = '';
                    }

                    // Map entity tag to CoreNodes if known.
                    if (defined('CORENODES') && !empty($type)) {
                        foreach (array_keys(CORENODES) as $cNode) {
                            if (strcasecmp($cNode, $type) === 0) {
                                $type = $cNode;
                                break;
                            }
                        }
                    }

                    $surfaceSlice = mb_substr($plainText, $start, $stop - $start + 1, 'UTF-8');
                    $annotations[] = [
                        'start' => $start,
                        'stop' => $stop,
                        'type' => $type !== '' ? $type : 'Entity',
                        'ref' => $ref,
                        'text' => $surfaceSlice
                    ];
                }
            }
        }
    };

    $traverse($rootNode);

    return [
        'text' => $plainText,
        'annotations' => $annotations
    ];
}

/**
 * Parser for CoNLL-U and CoNLL-2003 corpora.
 */
function parseConll(string $conllContent): array {
    $lines = preg_split('/\r\n|\r|\n/', $conllContent);
    $plainText = '';
    $annotations = [];
    $charOffset = 0;

    $openEntity = null;

    $closeOpenEntity = function() use (&$openEntity, &$annotations, &$plainText) {
        if ($openEntity !== null) {
            $start = $openEntity['start'];
            $stop = $openEntity['stop'];
            if ($stop >= $start) {
                $surface = mb_substr($plainText, $start, $stop - $start + 1, 'UTF-8');
                $annotations[] = [
                    'start' => $start,
                    'stop' => $stop,
                    'type' => $openEntity['type'],
                    'ref' => '',
                    'text' => $surface
                ];
            }
            $openEntity = null;
        }
    };

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '') {
            // Sentence boundary: close any pending entity.
            $closeOpenEntity();
            if ($plainText !== '' && !str_ends_with($plainText, "\n\n")) {
                $plainText .= "\n";
                $charOffset += 1;
            }
            continue;
        }

        if (str_starts_with($trimmed, '#')) {
            continue;
        }

        $cols = preg_split('/\t+|\s+/', $trimmed);
        if (count($cols) < 2) {
            continue;
        }

        // Detect column format.
        // In CoNLL-U: col 0 is index, col 1 is token, col 9 is misc.
        // In CoNLL-2003: col 0 is token, last column is NER tag.
        $token = '';
        $nerTag = 'O';
        $spaceAfter = true;

        if (is_numeric($cols[0]) && count($cols) >= 4) {
            // CoNLL-U style.
            $token = $cols[1];
            $misc = $cols[9] ?? '';
            if (strpos($misc, 'SpaceAfter=No') !== false) {
                $spaceAfter = false;
            }
            // Check for NER in misc or dedicated column.
            if (preg_match('/(?:NER|Entity)=([A-Za-z0-9_\-]+)/', $misc, $m)) {
                $nerTag = $m[1];
            } else if (isset($cols[10]) && preg_match('/^[BIOES]-[A-Za-z0-9_]+$/', $cols[10])) {
                $nerTag = $cols[10];
            }
        } else {
            // CoNLL-2003 style.
            $token = $cols[0];
            $lastCol = end($cols);
            if (preg_match('/^[BIOES]-[A-Za-z0-9_]+$/', $lastCol) || $lastCol === 'O') {
                $nerTag = $lastCol;
            }
        }

        $tokenLen = mb_strlen($token, 'UTF-8');
        $tokenStart = $charOffset;
        $tokenEnd = $charOffset + $tokenLen - 1;

        // Process BIO tag.
        if (str_starts_with($nerTag, 'B-')) {
            $closeOpenEntity();
            $tagType = substr($nerTag, 2);
            $mappedType = mapNerTagToType($tagType);
            $openEntity = [
                'start' => $tokenStart,
                'stop' => $tokenEnd,
                'type' => $mappedType
            ];
        } else if (str_starts_with($nerTag, 'I-')) {
            $tagType = substr($nerTag, 2);
            $mappedType = mapNerTagToType($tagType);
            if ($openEntity !== null && $openEntity['type'] === $mappedType) {
                $openEntity['stop'] = $tokenEnd;
            } else {
                $closeOpenEntity();
                $openEntity = [
                    'start' => $tokenStart,
                    'stop' => $tokenEnd,
                    'type' => $mappedType
                ];
            }
        } else {
            $closeOpenEntity();
        }

        $plainText .= $token;
        $charOffset += $tokenLen;

        if ($spaceAfter) {
            $plainText .= ' ';
            $charOffset += 1;
        }
    }

    $closeOpenEntity();

    return [
        'text' => rtrim($plainText),
        'annotations' => $annotations
    ];
}

function mapNerTagToType(string $tag): string {
    $tagUpper = strtoupper($tag);
    if ($tagUpper === 'PER' || $tagUpper === 'PERSON') {
        return 'Person';
    }
    if ($tagUpper === 'LOC' || $tagUpper === 'LOCATION' || $tagUpper === 'GPE') {
        return 'Location';
    }
    if ($tagUpper === 'ORG' || $tagUpper === 'ORGANIZATION') {
        return 'Organization';
    }
    if ($tagUpper === 'MISC') {
        return 'Entity';
    }
    return ucfirst(strtolower($tag));
}

try {
    if ($format === 'tei' || ($format === '' && str_starts_with(trim($rawContent), '<'))) {
        $parsed = parseTeiXml($rawContent, $customTitle);
    } else {
        $parsed = parseConll($rawContent);
    }

    $textString = $parsed['text'];
    $annotations = $parsed['annotations'];

    if (trim($textString) === '') {
        throw new Exception('Parsed text is empty.');
    }

    if ($customTitle === '') {
        $customTitle = 'Imported document ' . date('Y-m-d H:i');
    }

    $node = new CUDNode($client);
    $annotation = new Annotation(false, $node->gettsx());
    $node->startTransaction();

    // Create the Text node in Neo4j.
    $nodetype = defined('TEXNODE') ? TEXNODE : 'Text';
    $primary = helper_extractPrimary($nodetype);
    $textProp = defined('TEXNODETEXT') ? TEXNODETEXT : 'text';

    $formdata = [
        $primary => $customTitle,
        $textProp => $textString
    ];

    $textNeoId = $node->createNewNode($nodetype, $formdata, true);
    $node->connectCreatorToNode((int)$_SESSION['neoid'], $textNeoId);

    $startProp = defined('ANNOSTART') ? ANNOSTART : 'starts';
    $stopProp = defined('ANNOSTOP') ? ANNOSTOP : 'stops';
    $userNeoId = (int)$_SESSION['neoid'];

    $createdAnnotations = [];

    foreach ($annotations as $anno) {
        $annoData = [
            $startProp => (int)$anno['start'],
            $stopProp => (int)$anno['stop']
        ];

        $createAnnotation = $node->createNewNode(ANNONODE, $annoData, true);
        $node->connectNodes($userNeoId, $createAnnotation, 'priv_created');
        $node->connectNodes($textNeoId, $createAnnotation, 'contains');

        // Check if matching entity exists in Neo4j by primary key or label.
        $targetEntityId = null;
        if (!empty($anno['ref'])) {
            $refStr = $anno['ref'];
            $findEntityQuery = 'MATCH (e) WHERE (e.URI = $ref OR e.pk = $ref OR e.id = $ref OR id(e) = toInteger($ref))
                                RETURN id(e) AS eid LIMIT 1';
            $resEt = $client->run($findEntityQuery, ['ref' => $refStr]);
            if ($resEt->count() > 0) {
                $targetEntityId = (int)$resEt->first()->get('eid');
            }
        }

        if ($targetEntityId === null && !empty($anno['text'])) {
            $surface = $anno['text'];
            $findEtByName = 'MATCH (e) WHERE (e.label = $name OR e.name = $name OR e.primaryKey = $name)
                             RETURN id(e) AS eid LIMIT 1';
            $resEt2 = $client->run($findEtByName, ['name' => $surface]);
            if ($resEt2->count() > 0) {
                $targetEntityId = (int)$resEt2->first()->get('eid');
            }
        }

        if ($targetEntityId !== null) {
            $node->connectNodes($createAnnotation, $targetEntityId, 'references');
        }

        $createdAnnotations[] = [
            'id' => $createAnnotation,
            'start' => $anno['start'],
            'stop' => $anno['stop'],
            'type' => $anno['type'],
            'entity_id' => $targetEntityId
        ];
    }

    $node->commitTransaction();

    echo json_encode([
        'success' => true,
        'msg' => 'Successfully imported pre-annotated text with ' . count($createdAnnotations) . ' annotations.',
        'text_neoid' => $textNeoId,
        'title' => $customTitle,
        'char_count' => mb_strlen($textString, 'UTF-8'),
        'annotation_count' => count($createdAnnotations)
    ]);

} catch (\Throwable $th) {
    if (isset($node) && $node->gettsx()) {
        $node->rollbackTransaction();
    }
    http_response_code(500);
    echo json_encode([
        'error' => 'Import failed',
        'msg' => $th->getMessage()
    ]);
}
exit();
?>
