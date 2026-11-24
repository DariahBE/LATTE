<?php
/**
 *  this endpoint creates a new node 
 *  then connects an annotation to it and the text
 *  then all spelling variants including the selected text get added to the node as variant.
 * If the Annotationmode key is set to 'auto', then the application knows it should update the
 * annoation_auto to annotation; otherwise annotation is created. 
 */

    //header:
    header('Content-Type: application/json; charset=utf-8');
    //includes: 
    include_once(dirname(__DIR__) . '/config/config.inc.php');
    include_once(ROOT_DIR . '/includes/user.inc.php');
    include_once(ROOT_DIR.'/includes/csrf.inc.php');
    include_once(ROOT_DIR.'/includes/getnode.inc.php');
    include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php');
    include_once(ROOT_DIR.'/includes/annotation.inc.php');

    //check if the annotation is being created by a valid user: If no user is logged in => die()
    $user = new User($client);
    //check user
    $user_id = $user->checkSession();
    if(!(isset($_POST['data']))){
        echo json_encode(array('msg' => 'Data is missing in request. Your request was rejected.'));
        die(); 
    }

    ////Implement rights-check here: 
    if ($user_id === false || $user->hasEditRights($user->myRole) < 1) {
        echo json_encode(array('msg' => 'Insufficient permissions to create annotations.'));
        die();
    }
    ///end of rights-check. 

    //interpretation of the post request!
    $data = $_POST['data']; 
    //parse parts of the data: 
    //// csrf: 
    $token = array_key_exists('token', $data) ? $data['token'] : false;
    //// annotation: 
    $annotationNode = array_key_exists('annotation', $data) ? $data['annotation'] : false; 
    //// variants: 
    $variants = array_key_exists('variants', $data) ? $data['variants'] : false; 
    //// properties: 
    $properties = array_key_exists('properties', $data) ? $data['properties'] : false; 
    //// Neo id of the text: 
    $texid = array_key_exists('texid', $data) ? $data['texid'] : false; 
    //// Labeltype of the node to be created:
    $nodelabel = array_key_exists('nodetype', $data) ? $data['nodetype'] : false; 
    //// Extract the annotation mode: automated (= convert auto to confirmed) or '' (create confirmed directly)
    ////        Default to manual: only one chain of actions will lead to  automatic-conversion. 
    $annomode = array_key_exists('annotationmode', $data) ? $data['annotationmode'] : '';         //default to manual! 
    ///////////////////////////////////
    // check if token is part of data dict AND for validity: 
    if(!($token)){
        echo json_encode(array('msg' => 'Invalid session token')); 
        die();
    }
    if(($texid === null) || !($nodelabel)){
        echo json_encode(array('msg' => 'Invalid node content'));
        die();
    }
    $tokenManager = new CsrfTokenManager(); 
    $validToken = $tokenManager->checkToken($token); 
    if(!($validToken)){
        echo json_encode(array('msg' => 'Invalid session token')); 
        die();
    }
    $node = new CUDNode($client);
    $node->startTransaction();

    $annotation = new Annotation(False, $node->gettsx()); 

    ///////////////////////////////////
    // check if the provided annotation matches the structure in config. 
    $annotationModel = NODEMODEL[ANNONODE]; 
    $startProperty = ANNOSTART;
    $endProperty = ANNOSTOP; 

    //make database commits a transaction!
    //$annotation->startTransaction(); 
    //entire chain is conditional and should only be committed if all queries succeed!!
    ///////////////////////////////////
    // creates a new node with a shortlived NEOID; properties are checked in the 
    // remote function. Invalid input leads to early termination. 
    // use the properties to create a new node if it does not exist: 

    //set the neo id of the user as a global variable, needed in multiple places. 
    $userNeoId = (int)$user->neoId; 

    /*
     *          enforce values for properties which are set by
     *          model primary keys!!!
    */
    // do a check of the provided properties: 
    // is there a uniqueness key defined in the model? If so: 
    //      check if it is provide, 
    //      if not, generate a unique value. 
    $model = NODEMODEL[$nodelabel];
    //look for access to the primary key data. What key in the returned NODE type is the Primary Key. (unique field)
    // Validate and clean all wikidata properties across the entity:
    if (!is_array($properties)) {
        $properties = array();
    }
    $splitWikidataEntity = !empty($data['split_wikidata_entity']) && filter_var($data['split_wikidata_entity'], FILTER_VALIDATE_BOOLEAN);
    $hasSchemaWikidataProp = false;
    $chosenQid = trim((string)($data['wikidata_qid'] ?? ''));
    if ($chosenQid !== '' && preg_match('/^Q[0-9]+$/', $chosenQid)) {
        foreach ($model as $propKey => $propMeta) {
            if (strtolower($propMeta[1] ?? '') === 'wikidata') {
                $hasSchemaWikidataProp = true;
                if (empty($properties[$propKey])) {
                    $properties[$propKey] = $chosenQid;
                }
                break;
            }
        }
    } else {
        foreach ($model as $propKey => $propMeta) {
            if (strtolower($propMeta[1] ?? '') === 'wikidata') {
                $hasSchemaWikidataProp = true;
                break;
            }
        }
    }

    // Determine effective Wikidata QID from either wikidata_qid or any wikidata-typed property.
    $effectiveQid = ($chosenQid !== '' && preg_match('/^Q[0-9]+$/', $chosenQid)) ? $chosenQid : '';
    if ($effectiveQid === '') {
        foreach ($properties as $pKey => $pVal) {
            $pType = isset($model[$pKey]) ? strtolower($model[$pKey][1] ?? '') : '';
            if (($pType === 'wikidata' || $pKey === 'wikidata_id') && preg_match('/^Q[0-9]+$/', trim((string)$pVal))) {
                $effectiveQid = trim((string)$pVal);
                break;
            }
        }
    }

    // By default (unless split_wikidata_entity is explicitly requested), reuse an existing entity with the same Q-ID
    // and attach the new spelling as a Variant.
    $existingEntityNeoId = null;
    if ($effectiveQid !== '' && !$splitWikidataEntity) {
        $matchingQidNodes = $node->fetchWikidataFromAnyPossibleEt($effectiveQid);
        if (!empty($matchingQidNodes)) {
            $existingEntityNeoId = (int)$matchingQidNodes[0];
        }
    }

    $found_key = array_search(true, array_column($model, 2), true);
    if ($found_key !== false){
        $keys = array_keys($model);
        $primaryKeyName = $keys[$found_key];
        $primaryKeyType = strtolower($model[$primaryKeyName][1] ?? '');

        if ($primaryKeyType === 'wikidata') {
            // Wikidata properties must never be auto-filled with an integer!
            if (array_key_exists($primaryKeyName, $properties) && trim((string)$properties[$primaryKeyName]) !== '') {
                $qVal = trim((string)$properties[$primaryKeyName]);
                if (!preg_match('/^Q[0-9]+$/', $qVal)) {
                    $node->rollbackTransaction();
                    echo json_encode(array('msg' => 'Invalid Wikidata ID format. Must start with Q followed by an integer (e.g. Q123).'));
                    exit;
                }
                if ($existingEntityNeoId === null && !$splitWikidataEntity) {
                    $node->checkKeyUniqueness($nodelabel, $primaryKeyName, $qVal);
                }
                $properties[$primaryKeyName] = $qVal;
            } else {
                unset($properties[$primaryKeyName]);
            }
        } elseif(array_key_exists($primaryKeyName, $properties)){
            //check for uniqueness:
            $value_to_check = $properties[$primaryKeyName]; 
            if ($value_to_check == ''){
                if ($primaryKeyType === 'int') {
                    $value_to_check = $node->generateUniqueKey($nodelabel, $primaryKeyName); 
                    $properties[$primaryKeyName] = $value_to_check; 
                } else {
                    unset($properties[$primaryKeyName]);
                }
            }elseif ($existingEntityNeoId === null){
                $node->checkKeyUniqueness($nodelabel, $primaryKeyName, $value_to_check);
            }
        }else{
            if ($primaryKeyType === 'int') {
                $properties[$primaryKeyName] = $node->generateUniqueKey($nodelabel, $primaryKeyName); 
            }
        }
    }
    if (is_array($properties)) {
        foreach ($properties as $propKey => $propVal) {
            if (isset($model[$propKey])) {
                $pType = strtolower($model[$propKey][1] ?? '');
                if ($pType === 'wikidata') {
                    $trimmedQ = trim((string)$propVal);
                    if ($trimmedQ === '') {
                        unset($properties[$propKey]);
                    } else {
                        if (!preg_match('/^Q[0-9]+$/', $trimmedQ)) {
                            $node->rollbackTransaction();
                            echo json_encode(array('msg' => 'Invalid Wikidata ID format. Must start with Q followed by an integer (e.g. Q123).'));
                            exit;
                        }
                        $properties[$propKey] = $trimmedQ;
                    }
                }
            }
        }
    }
    try {
        if ($existingEntityNeoId !== null) {
            $createdEntity = $existingEntityNeoId;
            $labelRes = $client->run('MATCH (e) WHERE id(e) = $eid RETURN labels(e)[0] AS lbl LIMIT 1', ['eid' => $existingEntityNeoId]);
            if ($labelRes->count() > 0 && $labelRes->first()->get('lbl')) {
                $nodelabel = (string)$labelRes->first()->get('lbl');
            }
        } else {
            //createNewNode needs to automatically generate a primary key for entities where the model uses an integer key as unique value. 
            $createdEntity = $node->createNewNode($nodelabel, $properties, true); 

            if (!$hasSchemaWikidataProp && $effectiveQid !== '') {
                $node->gettsx()->run(
                    'MATCH (e) WHERE id(e) = $eid SET e.wikidata_id = $qid',
                    ['eid' => (int)$createdEntity, 'qid' => $effectiveQid]
                );
            }

            //connect the user ID to $createdEnditity!
            $node->connectNodes($userNeoId, $createdEntity, 'priv_created');
        }

        //connect variant spellings to the $createdentity:
        foreach($variants as $variant){
            $r = $node->createVariantRelation($variant, $createdEntity); 
        }

        //connect the $createdEntity to a text using the text NEOID and the $createdEntity ID
        $hasLegacyAutoNode = ($annomode === 'automated' && !empty($data['neo_id_internal']) && (int)$data['neo_id_internal'] > 0);
        if ($hasLegacyAutoNode){
            $annotation_neo_id = $data['neo_id_internal']; 
            if (!$node->determineRightsSet(2, (int)$annotation_neo_id)) {
                $node->rollbackTransaction();
                echo json_encode(array('msg' => 'Insufficient permissions to convert this annotation.'));
                exit;
            }
            $startAutomated = $annotationNode[ANNOSTART];
            $stopAutomated = $annotationNode[ANNOSTOP];
            unset($annotationNode[ANNOSTART]);
            unset($annotationNode[ANNOSTOP]);
            $createAnnotation = $annotation->convertAutomaticAnnotationToConfirmedAnnotation($annotation_neo_id, $annotationNode); 
        }else{
            $createAnnotation = $node->createNewNode(ANNONODE, $annotationNode, true);
        }

        //$annotations shares the transaction with $node!
        $createdAnnotationUUID = $annotation->fetchAnnotationUUID($createAnnotation); 
        //connect the user to the created annotation: 
        $node->connectNodes($userNeoId, $createAnnotation, 'priv_created'); 

        //connect the entity with the annotation !
        $node->connectNodes($createAnnotation, $createdEntity, 'references');

        //connect the text with the annotation !
        if(!$hasLegacyAutoNode){
            $node->connectNodes($texid, $createAnnotation, 'contains');
        }

        // Promote any automatic suggestion covering this span by removing it from latte_suggestions on the text node.
        include_once(ROOT_DIR . '/includes/latte_queue_manager.inc.php');
        $startPos = (int)($annotationNode[ANNOSTART] ?? ($startAutomated ?? 0));
        $stopPos = (int)($annotationNode[ANNOSTOP] ?? ($stopAutomated ?? 0));
        $propagatedList = array();

        $propagate = !empty($data['propagate']) && filter_var($data['propagate'], FILTER_VALIDATE_BOOLEAN);
        if ($propagate && !empty($texid) && !empty($variants[0])) {
            $needle = trim((string)$variants[0]);
            $matchCase = !isset($data['case_sensitive']) || filter_var($data['case_sensitive'], FILTER_VALIDATE_BOOLEAN);
            $wholeWord = !isset($data['whole_word']) || filter_var($data['whole_word'], FILTER_VALIDATE_BOOLEAN);
            if ($needle !== '') {
                $textProp = defined('TEXNODETEXT') ? TEXNODETEXT : 'text';
                $tRes = $client->run('MATCH (t) WHERE id(t) = $tid RETURN t.' . $textProp . ' AS txt LIMIT 1', ['tid' => (int)$texid]);
                $textBody = ($tRes->count() > 0) ? (string)($tRes->first()->get('txt') ?? '') : '';
                if ($textBody !== '') {
                    $exRes = $client->run(
                        'MATCH (t)-[:contains]->(a) WHERE id(t) = $tid AND (a:Annotation OR a:Annotation_rejected)
                         RETURN a.' . $startProperty . ' AS s, a.' . $endProperty . ' AS e',
                        ['tid' => (int)$texid]
                    );
                    $occupied = [['s' => $startPos, 'e' => $stopPos]];
                    foreach ($exRes as $rec) {
                        $occupied[] = ['s' => (int)$rec->get('s'), 'e' => (int)$rec->get('e')];
                    }
                    $chars = mb_str_split($textBody, 1, 'UTF-8');
                    $totalChars = count($chars);
                    $sChars = mb_str_split($needle, 1, 'UTF-8');
                    $sLen = count($sChars);
                    $needleLower = mb_strtolower($needle, 'UTF-8');

                    for ($idx = 0; $idx <= $totalChars - $sLen; $idx++) {
                        $sliceStr = implode('', array_slice($chars, $idx, $sLen));
                        $isMatch = $matchCase ? ($sliceStr === $needle) : (mb_strtolower($sliceStr, 'UTF-8') === $needleLower);
                        if (!$isMatch) continue;
                        $cStart = $idx;
                        $cStop = $idx + $sLen - 1;
                        if ($wholeWord) {
                            if ($cStart > 0 && !preg_match('/[\s\p{P}]/u', $chars[$cStart - 1])) continue;
                            if ($cStop < $totalChars - 1 && !preg_match('/[\s\p{P}]/u', $chars[$cStop + 1])) continue;
                        }
                        $overlaps = false;
                        foreach ($occupied as $rng) {
                            if ($cStart <= $rng['e'] && $cStop >= $rng['s']) {
                                $overlaps = true;
                                break;
                            }
                        }
                        if ($overlaps) continue;
                        $occupied[] = ['s' => $cStart, 'e' => $cStop];

                        $propAnnoId = $node->createNewNode(ANNONODE, [$startProperty => $cStart, $endProperty => $cStop], true);
                        $propAnnoUid = $annotation->fetchAnnotationUUID($propAnnoId);
                        $node->connectNodes($userNeoId, $propAnnoId, 'priv_created');
                        $node->connectNodes($propAnnoId, $createdEntity, 'references');
                        $node->connectNodes($texid, $propAnnoId, 'contains');
                        $propagatedList[] = [
                            'uuid' => $propAnnoUid,
                            'neoid' => $propAnnoId,
                            'start' => $cStart,
                            'stop' => $cStop,
                            'type' => $nodelabel,
                        ];
                    }
                }
            }
        }

        $node->commitTransaction();

        // Clean up overlapping latte_suggestions and legacy Annotation_auto nodes after commit.
        $rangesToClean = array_merge([['start' => $startPos, 'stop' => $stopPos]], $propagatedList);
        foreach ($rangesToClean as $rng) {
            try {
                if (!empty($texid)) {
                    LatteQueueManager::promoteSuggestion($client, (int)$texid, (int)$rng['start'], (int)$rng['stop']);
                    $client->run(
                        'MATCH (t)-[:contains]->(aa:Annotation_auto)
                         WHERE id(t) = $tid AND aa.' . $startProperty . ' <= $stop AND aa.' . $endProperty . ' >= $start
                         DETACH DELETE aa',
                        ['tid' => (int)$texid, 'start' => (int)$rng['start'], 'stop' => (int)$rng['stop']]
                    );
                }
            } catch (\Throwable $e) {
                error_log('Failed to clean automatic suggestion on text node ' . $texid . ': ' . $e->getMessage());
            }
        }
    } catch (\Throwable $th) {
        $node->rollbackTransaction();
        http_response_code(500);
        echo json_encode(array('msg' => 'Error persisting annotation: ' . $th->getMessage()));
        exit;
    }
    $node_reply = array();
    $node_reply['data'] = array(
        'intid' => $createAnnotation,
        'uuid' => $createdAnnotationUUID,
        'merged_into_existing' => ($existingEntityNeoId !== null),
        'type' => $nodelabel, 
        'start' => $annotationNode[ANNOSTART] ?? $startAutomated, 
        'stop' => $annotationNode[ANNOSTOP] ?? $stopAutomated,
        'propagated' => $propagatedList,
    );
    //PATCH TSX is not used, so we do not pass it to the client. 
    //$node_reply['tsx'] = $node;


    // if database commit was successfull: revoke the token. 
    $tokenManager->revokeToken(); 
    echo json_encode($node_reply); 
    //die('token revoked');
?>