<?php
/*
        adds CREATE UPDATE AND DELETE functionality 
        to the nodes class. READ operations are in 
        a separate file (getnode.inc.php)

        UNIQUENESS:: https://neo4j.com/docs/cypher-manual/current/constraints/examples/#constraints-create-a-node-uniqueness-constraint 
        CAVEAT: NEO4J Community Edition has NO support for built in uniqueness constraints.
        So you need to be carefull how to implement uniqueness checks. For integers you can
        fall back on id() or calculate a field. For strings, you can check, but generate is
        more difficult!
*/

class CUDNode extends Node {


    /*
        DO NOT PUT CONSTRUCTOR IN HERE, it's inherited from the Node object!
        client property is passed down as a protected prop!!!
    */

    /**
     * Determines the level of the requested operation and returns it. 
     * If the level matches or exceeds the minimum requirement, an operation 
     * can continue, otherwise it is rejected. 
     * Returns a simple True/False
     */

    private function helper_enforceType($type, $inputvariable){
        if($type === 'string'){
            return strval($inputvariable);
        }elseif($type === 'int'){
            //use regex to remove everything thats not a number. 
            $inputvariable = preg_replace('~\D~', '', $inputvariable);
            //then cast it to an int and return
            return (int)$inputvariable;
        }elseif($type === 'bool'){
            // IN php the string 'false' is not evaluated as false!!
            //use JSON!
            //$value = json_decode($inputvariable);
            //then cast to boolval!
            return boolval($inputvariable === 'on'); 
        }elseif($type === 'uri'){
            $inputvariable = trim($inputvariable); 
            $parsed = parse_url($inputvariable); //returns the components. Should include a host!!!
            //if there's no host: reject! Can't do anything with this. 
            if(!(array_key_exists('host', $parsed))){
                throw new Exception('No host defined, URI rejected'); 
            }
            $filtered = filter_var($inputvariable, FILTER_VALIDATE_URL);
            if($filtered){
                return $inputvariable;
            }else{
                throw new Exception('Invalid URI provided: entry was rejected.'); 
            };
        }elseif($type === 'float'){
          // different standards to write floats: 123,123.23 vs 123.123,23  ::: should catch both!
          $inputvariable = str_replace(",",".",$inputvariable);
          $inputvariable = preg_replace('/\.(?=.*\.)/', '', $inputvariable);
          return floatval($inputvariable);
        }else{
            return $inputvariable; 
        }
    }
  private function is_entity($neoID){
    //helper function that checks if the given $neoID has a label that belongs to an entity. 
    //needs access to transaction scope!
    $labelResult =  $this->tsx->run('MATCH (n) WHERE id(n) = $id RETURN labels(n) AS labels;', array('id'=>$neoID)); 
    if($labelResult->isempty()){return false;}
    $label = $labelResult->first()->get('labels')[0];
    return $label; 
  }

  public function gettsx(){
    return $this->tsx;
  }

  //transaction management.
  public $tsx;
  public function startTransaction(){
    $this->tsx = $this->client->beginTransaction();
  }
  public function rollbackTransaction(){
    $this->tsx->rollback();
  }
  public function commitTransaction(){
    $this->tsx->commit();
  }

  public function connectNodes($sourceNodeNeoId, $targetNodeNeoId, $edgeName) {
    $query = "
        MATCH (sourceNode)
        WHERE ID(sourceNode) = $sourceNodeNeoId
        MATCH (targetNode)
        WHERE ID(targetNode) = $targetNodeNeoId
        CREATE (sourceNode)-[:$edgeName]->(targetNode)
    ";
    $this->tsx->run($query); 
  }

  public function checkKeyUniqueness($label, $property, $value){
    /**         //TEST Passed. 
     * Checks if a given key is Unique for $label and $property combination. 
     * Returns BOOL
     */
    $query = '
      MATCH (p:'.$label.' {'.$property.': $checkValue})
      RETURN p
    ';
    $data = array('checkValue' => $value);
    $result = $this->tsx->run($query, $data); 
    //var_dump($result); 
    return count($result); 
  }

  public function generateUniqueKey($label, $property){
    /*           //TEST Passed. 
     * Generates a new Unique integer for the given $property of a $labelnode. 
     * The value is returned to the calling instance and inserted there.
     */
    //var_dump($label, $property); 
     $query = "
      MATCH (n:".$label.")
      RETURN n.".$property." AS highest
      ORDER BY n.".$property." DESC
      LIMIT 1"; 
    $result = $this->tsx->run($query); 
    //var_dump($result); 
    if(boolval(count($result))){
      return $result[0]['highest']+1; 
    }else{
      return 1; 
    }
  }


  //Transactional model: OK
  public function createVariantRelation($label, $entitySource){
    //create a variant or connection between an entity and a variant:
    //if the variant is not yet in de DB, the variant is created.
    //if the variant already exists, a relation is created connecting it to the entity. 
    // You determine the existence of a given variant by matching the string against all nodes
    // that carry the variant label. The variant label is a hardcoded property of the application. 
    //$label          =string   = string label to be inserted in the database and used as spellingvariant for the node. 
    //$entitySource   =int      = neoID of the entity-node(multilabel)
    //1: check variant.
    $existingVariantId = -1;                //THE NEO ID OF THE VARIANT 
    $variant_uuid = -1;                     //THE APOC GENERATED UUID OF THE VARIANT
    $query = 'MATCH (n:Variant) WHERE n.variant = $varlabel RETURN id(n) as id, n.uid as uuid';
    $existsResult = $this->tsx->run($query, array('varlabel'=>$label));
    $hasResult = !($existsResult->isempty()); 
    //2: check if the entity is an actual entity.
    //Label exists AND is not yet connected to et: SO connect it ==> verify that $entitySource is an $entity.
    $labelCheck = $this->is_entity($entitySource);      // NEEDS TO BE IN TRANSACTIONAL SCOPE TO WORK!!
    if($hasResult){
      if(array_key_exists($labelCheck, CORENODES)){
        //there is a matching request!
        $existingVariantId = $existsResult->first()->get('id'); 
        $variant_uuid = $existsResult->first()->get('uuid'); 
        //3: Check that there is no connection between $entitySource and $existingVariantId: 
        $checkResult = $this->tsx->run('MATCH (n:Variant)-[r:same_as]-(t) WHERE id(n) = $varid AND id(t) = $etid RETURN count(r) AS relations;', array('varid'=>$existingVariantId, 'etid'=>$entitySource));
        //is empty when there is no node found ==> otherwise it will have a property set where relations could be 0 or more. 
        if ($checkResult->isempty()){
          return array('msg'=>'Invalid request: one or more nodes do not exists.'); 
        }
        //check how many relations the query returned: 
        if($checkResult->first()->get('relations') === 0){
          //required to make a new relation
          $matchAndConnectResult = $this->tsx->run('MATCH (n), (t) WHERE id(n) = $varid AND id(t) = $etid CREATE (n)-[r:same_as]->(t)', array('varid'=> $existingVariantId, 'etid'=>$entitySource));
          return array('msg'=> 'New relation created', 'node' => $matchAndConnectResult, 'data' => ['uuid'=> $variant_uuid, 'nid'=> $existingVariantId] ); 
        }else{
          //do not modify anything: a relation already exists
          return (array('msg'=>'A relation already exists, no changes made to the database.', 'data' => ['uuid'=> $variant_uuid, 'nid'=> $existingVariantId])); 
        }
      }else{
        return array('msg'=> 'Invalid entity node.');
      }
    }else{
      // this is currently the case for auto node converts. 
      //the variant has no label registered in the DB that matches the request: create one. 
      //and create the relationship IF the related entity node exists: 
      if(array_key_exists($labelCheck, CORENODES)){
        $createAndConnectResult = $this->tsx->run('MATCH (e) WHERE id(e) = $etid CREATE (n:Variant {variant: $varname, uid: apoc.create.uuid()})-[:same_as]->(e) return id(n) as id, n.uid as uuid', array('etid'=>$entitySource, 'varname'=>$label));
        $existingVariantId = $createAndConnectResult->first()->get('id'); 
        $variant_uuid = $createAndConnectResult->first()->get('uuid'); 
        return array('msg'=>'New variant and link created.', 'data' => ['uuid'=> $variant_uuid, 'nid'=> $existingVariantId]); 
      }else{
        //the variant label is not found in the DB and the entity node doesn't have a valid ID:
        return array('msg'=> 'Invalid entity node.');
      }
    } 
  }

  //transactions OK
  public function dropVariant($variantID, $entityID, $detachQuery){
    //drops a variant or connection between a variant and entity:
    //$variantID  = int   = neoID of the Variant-node.
    //$entityID   = int   = neoID of the entity-node(multilabel)
    //$detachQuery= bool  = if true ==> runs a detach delete query; if false remove all relations between $variantID and $entityID that have the same_as-label
    if($detachQuery){
      //deletes all relationships where n is part of and deletes the node. 
      $result = $this->tsx->run('MATCH (n) WHERE id(n)=$varid DETACH DELETE n;', array('varid'=>$variantID));
    }else{
      //deletes all relationships with the same_as label between n and m;
      $result = $this->tsx->run('MATCH (n)-[r:same_as]-(m) WHERE id(n) = $varid AND id(m) = $etid DELETE(r);', array('varid'=>$variantID, 'etid'=>$entityID)); 
    }
    return $result;
  }


  public function createNewKnowledgebase($name, $address, $etNeoId){
    /**
     * //OK tests passed: Node to new KB; Node to exising KB; existing relation! UID works!
     * Connects a new kb to an exiting et, or connects two exisiting nodes (KB -- ET)
     * Using the see_also edg 
     * Edges and properties are hardcoded in this model!!
     * DON'T rely on the uid attribute during merge: create it after the new node was made!!
     */

    //either the newly created edge, or the new node and edge should be 
    //connected to $etNeoId; 
    //verify the address: 
      if (filter_var($address, FILTER_VALIDATE_URL)) {
        //once verified create/connect what is necessary: 
        $query = '
          MATCH (n) WHERE id(n) = $nid
          MERGE (s:See_Also {partner: $name, partner_uri: $partneruri})
          ON CREATE SET s.uid = apoc.create.uuid()
          MERGE (n)-[:see_also]->(s)
          RETURN n, s
        '; 
        $querydata = array('nid'=> (int)$etNeoId, 'name' => $name, 'partneruri'=> $address); 
        $create = $this->tsx->run($query, $querydata); 
        $kbNode = $create[0]['s']; 
        $kbneoid = $kbNode['id']; 
        $kblabel = $kbNode['properties']['partner']; 
        $kburi = $kbNode['properties']['partner_uri']; 
        $kbuuid = $kbNode['properties']['uid']; 
        $summary = $create->getSummary()->getCounters(); 

        $result = array(
          //'summary' => $summary, 
          'new_kb_nodes' => $summary['nodesCreated'],
          'new_edges' => $summary['relationshipsCreated'],
          'kb_uuid' => $kbuuid, 
          'kb_neo_id' => $kbneoid, 
          'kb_label' => $kblabel, 
          'kb_uri' => $kburi
        );
        return $result; 
    } else {
        return array('status' => 'rejected', 'reason' => 'Invalid URL');
    }
  }


  public function determineRightsSet($requestedLevel, $neoid){
    include_once(ROOT_DIR.'/includes/user.inc.php');
    $user = new User($this->client); 
    //var_dump($user->myRole); 
    $ownerShip = $this->checkOwnershipOfNode($neoid, $user->neoId);

    $whatRightSetApplies = $user->hasEditRights($user->myRole); 
    //ownerShip override: if requestlevel = 3, userright = 2 but ownership == true;
    //then the user has the right to delete (lift the userright up)
    if ($requestedLevel === 3 && $ownerShip === true && $whatRightSetApplies === 2) {
      //increase right of 'researcher' level users to delete self-created nodes. 
      //lift user up to allow them to edit self-created nodes. 
      $whatRightSetApplies = 3;
    }
    if ($whatRightSetApplies >= $requestedLevel){
        return True;
    }else{
        return False;
    }
  }



    /** Takes the NEO ID and deletes the node. 
     * Any connected edge will be removed. 
     * the dryRun argument will report how many
     * edges are to be deleted in addition to the node.
    */
    //test implementation of transactions!
      //    1) AJAX/crud/delete.php ==> OK
      //    2) AJAX/fetch_kb.php ==> OK
      //    3) crud/delete.php ==> //NA
    public function delete($id, $dryRun=False){
      //function WILL cast $id to int!
        if($dryRun){
            $query_EdgesToBeRemoved = 'MATCH (n)-[r]-() WHERE id(n) = $nodeid RETURN count(r) as count'; 
            $query_NodesToBecomeIsolated = 'MATCH (n)--(p) WHERE id(n) = $nodeid OPTIONAL MATCH (n)--(p)--(i) RETURN n,  p as directlyConnected, i as nullIfIsolated';        //if i == null, then p is only connected to the n-node which will be deleted!
            $deletedEdges = $this->tsx->run($query_EdgesToBeRemoved, ['nodeid'=>(int)$id]); 
            $isolationDetection = $this->tsx->run($query_NodesToBecomeIsolated, ['nodeid'=>(int)$id]);
            $returnData = array('impactedEdges'=> 0, 'disconnectedNodes' => 0);
            foreach($deletedEdges as $record){
                $returnData['impactedEdges'] = $record->get('count'); 
            }
            foreach($isolationDetection as $record){
                if ($record['nullIfIsolated'] === null){
                    $returnData['disconnectedNodes']+=1;
                }
            }
            return $returnData; 
        }else{
            //die('defer Transactional model!');
            $query = 'MATCH (n) WHERE id(n) = $nodeid DETACH DELETE (n)';
            $this->tsx->run($query, ['nodeid'=>(int)$id]); 
        }
    }

    public function disconnect($leftNeoId, $rightNeoId, $edgelabel){
      //function WILL cast $id to int! Method only used by KnowledgeBase code 
      //but the code is portable to be used with other tripples:
      // (n:leftNeoId)-[r:edgelabel]-(m:rightNeoId)
      $query = 'MATCH (n)-[r:'.$edgelabel.']-(m) WHERE id(n) = $neoleft AND id(m) = $neoright DELETE r;'; 
      $querydata = array('neoleft'=> (int)$leftNeoId, 'neoright'=> (int)$rightNeoId); 
      $deletedEdges = $this->tsx->run($query, $querydata); 
      return $deletedEdges; 
    }

    /**Create a new blank node based on JS input!
     * 
     */
    function createNewNode($label, $data, $createUID = true){
        // is the user even allowed to create a node??
        // check authorization!
        $user = new User($this->client); 
        //checks if a user session exists! 
        // only users can create a node. In theory any user can create a node, so you don't need to validate any further. 
        if($user->checkSession()){
            //once authorized: check validity of the nodetype!
            if(in_array($label, array_keys(NODEMODEL))){
                $nodeAttributes = array(); 
                $query = 'CREATE (n:'.$label.')'; 
                //for a valid nodetype: check validity of the data-attributes!
                //filter out the attributes: remove any attribute that is not written down in the NODEMODEL: 
                // if an attribute has an integer type and is unique, ensure you generate it when missing!!! this is a primary key!
                $placeholder=1; 
                $placeholderValues = array();
                //var_dump($data); 
                foreach($data as $key => $value){
                    //empty uri triggers fatal error: empty values should not be parsed as data!! Unless it's for PK field: 
                    //LOGIC: 
                    //do not store empty values in the database! drop them from data: 
                    //if the empty value is of type INT and a primary key, autogenerate it!
                    //if it is empty and a primary key, but NO integer type, throw error: 
                    if($value === '' && NODEMODEL[$label][$key][2]){
                      if(NODEMODEL[$label][$key][1]== 'int'){
                        $value = $this->generateUniqueKey($label, $key);
                        $data[$key] = $value;
                      }else{
                        //return array('ERR'=> 'Empty value given for unique attribute ('.NODEMODEL[$label][$key][0].'). Request rejected.');
                        echo json_encode(array('ERR'=> 'Empty value given for unique attribute ('.NODEMODEL[$label][$key][0].'). Request rejected.'));
                        //throw new Exception();
                        //rollback the transaction before calling DIE to revert all pending changes!
                        $this->rollbackTransaction();
                        die();
                      }
                    }
                    if($value !== ''){
                        if(array_key_exists($key, NODEMODEL[$label])){
                            $nodeAttributes[] = ' n.'.$key.' = $placeholder_'.$placeholder;
                            //enforce the correct type of the $value!
                            $reformattedValue = $this->helper_enforceType(NODEMODEL[$label][$key][1],$value); 
                            $placeholderValues['placeholder_'.$placeholder] = $reformattedValue; 
                        }else{
                            echo json_encode(array('ERR'=>"Data does not match node definition. Request rejected." ));
                            $this->rollbackTransaction(); 
                            die();
                        }
                        $placeholder++; 
                    }
                }
                if($createUID){
                    $nodeAttributes[] = ' n.uid = apoc.create.uuid() ';
                }
                $query .= ' SET '. implode(', ', $nodeAttributes);
                $query .= ' return id(n) as id';
                // var_dump($query);
                // var_dump($placeholderValues); 
                $data = $this->tsx->run($query, $placeholderValues); 
                $id = $data->first()->get('id');
                //node is created; now connect it to the user that created it: 
                return $id; 

            }else{
                throw new Exception('Rejected nodetype.');
            }
        }else{
            throw new Exception('Insufficient permissions');
        }

    }



    public function connectCreatorToNode($userid, $neoidOfCreatedNode){
      /**
       * Takes the internal NEO ID of a user who created a node. And connects it to a created node referenced
       * by it's internal NEO ID. The edge between the two nodes is a directed edge 'priv_created'
      */
      $query = 'MATCH (u:priv_user), (n)
      WHERE id(u) = $userid AND id(n) = $nodeid
      CREATE (u)-[:priv_created]->(n)
      RETURN n, u'; 
      $result = $this->tsx->run($query, array('userid'=>(int)$userid, 'nodeid' => (int)$neoidOfCreatedNode));
      return $result; 
    }



    /*        DELETE OPERATIONS!!!! */

    public function deleteText($id){
      /*takes the NEO ID of a text and deletes it from the DB
      * returns the IDS' of annotation nodes that should be deleted too
      * returns the IDS' of entities that should be deleted too. 
      */
      $repl = []; 
      $relatedAnnoIds = []; 
      $allowedToDelete = $this->determineRightsSet(3, $id); 
      $repl['permission'] = $allowedToDelete; 
      if($allowedToDelete){
        $annosToKillQuery = 'MATCH (n)-[r:contains]-(a) WHERE id(n) = $neoid RETURN id(a) AS killthis;'; 
        $annosToKill = $this->tsx->run($annosToKillQuery, array('neoid'=>$id));
        $annosToKill = $annosToKill->getResults();
        foreach($annosToKill as $anno){
          $relatedAnnoIds[] = $anno->get('killthis'); 
        }
      }
      //var_dump($relatedAnnoIds);
      $repl['affectedAnnotations'] = $relatedAnnoIds; 
      return $repl; 
    }

    public function find_floats_over_connection($id_array, $connectionLabel){
      /*$takes a list of NEO4J Ids of nodes and a string representing the edge label. 
      Returns a list of all ids() of nodes which are connected to a node in in the id_array and
      have the edge label $connectionLabel. 
      */
      // Create a comma-separated string of IDs for the Cypher query

      if (empty($id_array)) {
        return [];
      }

      // Create a comma-separated string of IDs for the Cypher query
      $idList = implode(',', $id_array);

      // Prepare the Cypher query to find connected nodes
      // The MATCH clause finds nodes connected by the specified edge label
      // the $connectionLabel is required to be there. 
      // relationshipCount is then automatically 1 or higher. 
      // if one of the relations is 'created' (application logic) then the count increases to two.
      // if there's more than 2 relations, the node is part of other clusters and should remain! 
      // OK: what if there's two creation labels going from User to Node. Then relationshipCount becomes 3
      // and the node might end up floating. the propper check would be: 
      // relationshipCount - createdcount == 1
      $query = "
        MATCH (n)-[r]->(m)
        WHERE id(n) IN [$idList] AND type(r) = '$connectionLabel'
        WITH m, COUNT(r) AS relationshipCount
        MATCH (m)
        OPTIONAL MATCH (m)-[c:priv_created]->()
        WITH m, relationshipCount, COUNT(c) AS createdCount
        WHERE relationshipCount - createdCount = 1
        RETURN DISTINCT id(m) AS connectedNodeId

      ";

      // Execute the query
      $result = $this->tsx->run($query);
      // Collect the results
      foreach ($result as $row){
        $connectedNodeIds[] = $row->get('connectedNodeId'); 
      }
      return $connectedNodeIds;
    }

    public function find_floating_entity_connections($id_array){
    /**Takes a list of NEO4J Ids of entity nodes and returns a list of all ids() of nodes connected
     *  over the see_also and same_as edge relation that have no connections to entities with id's
     * which are not in id_array() 
     * (i.e. returns a list of ids of nodes that would end up floating if you remove all nodes 
     * with an id in $id_array). 
     */
      $entities_to_delete = array(); 
      $query = 'MATCH (e)-[:same_as|see_also]-(f)
        WHERE id(e) IN $entity_ids
        WITH collect(DISTINCT id(f)) AS connected_floatees
        MATCH (a2)-[:same_as|see_also]-(f)
        WHERE id(f) IN connected_floatees
        RETURN id(f) as float_id, collect(id(a2)) as connected_entities'; 

      $result = $this->tsx->run($query, array('entity_ids'=> $id_array)); 
      foreach ($result as $row) {
        $floater = $row['float_id'];
        $float_connections = $row['connected_entities']; 
        //https://www.php.net/manual/en/function.array-diff.php 
        // array_diff returns everything that is in the first array but not in the second!!
        // you can use it to figure out if an entity is connected to annotations which are not flagged for deletion!
        $diff = array_diff($float_connections->toArray(), $id_array); 
        if(!(boolval($diff))){
          $entities_to_delete[] = $floater;
        }
    }

      return array_unique($entities_to_delete);
    }

    public function bulk_delete_by_ids($id_array){
      if (boolval($id_array) && count($id_array) > 0){
        $query = 'WITH $deleteThese AS ids
        MATCH (n) WHERE id(n) IN ids
        DETACH DELETE n';
        $result = $this->tsx->run($query, array('deleteThese'=> $id_array)); 
        return $result->getSummary(); 
      }else{
        return null; 
      }
    }

    public function find_isolated_entities($annotation_array){
      /**Takes a list of annotation ids and checks if the connected
       * entity has at least one other annotation which is not present 
       * in the annotation_array. If so, it is not deleted. 
       * If not, it is added to an array of entities to be deleted. 
       */
          // Initialize an array to store entities to be deleted
        $entities_to_delete = array();
        
        // Construct and execute the Cypher query
        $query = 'MATCH (a)-[:references]->(e)
        WHERE id(a) IN $annotation_ids
        WITH collect(DISTINCT id(e)) AS connected_entities
        MATCH (a2)-[:references]-(e)
        WHERE id(e) IN connected_entities
        RETURN id(e) as entity_id, collect(id(a2)) as related_annotations';
        
        $result = $this->tsx->run($query, ['annotation_ids' => $annotation_array]);
        // Process the query result and add entities to the delete array
        foreach ($result as $row) {
          $entity = $row['entity_id'];
          $annosOfEntity = $row['related_annotations']; 
          //var_dump($entity);
          //var_dump($annosOfEntity->toArray());
          //https://www.php.net/manual/en/function.array-diff.php 
          // array_diff returns everything that is in the first array but not in the second!!
          // you can use it to figure out if an entity is connected to annotations which are not flagged for deletion!
          $diff = array_diff($annosOfEntity->toArray(), $annotation_array); 
          if(!(boolval($diff))){
            $entities_to_delete[] = $entity;
          }
        }
        return array_unique($entities_to_delete);
    }

    public function findConnectedTexts($entity_id){
      /**
       * Takes the neo id of an entity and returns an array of all 
       * neo IDS of texts where this entity is linked to and the 
       * neo IDS of all annotations making the link. You always have
       * a maximum of one row in $result! 
       */
      $texids = array(); 
      $annoids = array(); 
      $query = 'MATCH (n)-[r:references]-(a)-[q:contains]-(m:'.TEXNODE.')
      WHERE id(n) = $etid RETURN collect(DISTINCT id(m)) AS texts, collect(DISTINCT id(q)) AS annotations
      ';
      $result = $this->tsx->run($query, array('etid'=> (int)$entity_id));
      foreach($result as $row){
        $texids = $row['texts']->toArray();
        $annoids = $row['annotations']->toArray();
      }
      return array($texids, $annoids);
    }

  

    public function updateNode($neo_id, $data, $dropbools){
      /**
       * Takes the neo id of a node and the new data that has to be stored
       * strings which are empty "" will have the relate property deleted
       * from the node. Properties where data is set will receive an update.
       * Bools that are listed in the $dropbools array will be deleted from
       * the property. 
       */
      //iterator to distinguish query placeholders. 
      $ph = 1; 
      $placeholders = array(); 
      function ph_generator($ph){
        $ph_name = 'PH_'.$ph; 
        return $ph_name; 
      }

      //handle the actual given data: 
      $remove_command = array();
      $update_command = array(); 
      foreach ($data as $key => $value) {
        if ($value == '') {
            $remove_command[] = 'n.'.$key;
        } else {
            $placeholder = ph_generator($ph); 
            $ph++; 
            $placeholders[$placeholder] = $value; 
            // $phs[$placeholders[0]] -> $value; 
            $update_command[] = 'n.'.$key.' = $'.$placeholder;
        }
      }

      //attach $dropbools to the $remove_command here to handle delete of 'false' values!
      // i.e. ==> empty(POST) === false(LOGIC) === null(DATABASE). 
      foreach ($dropbools as $val) {
        $remove_command[] = 'n.'.$val; 
      }
      if (boolval($remove_command)){
        $remove_command = implode(', ', $remove_command);
        $remove_query = 'MATCH (n) WHERE id(n) = $neoid REMOVE '.$remove_command;
      }
      if (boolval($update_command)){
        $update_command = implode(', ', $update_command);
        $update_query = 'MATCH (n) WHERE id(n) = $neoid  SET '.$update_command.'; '; 
      }
      $placeholders['neoid'] = (int)$neo_id;
      try{
        if(boolval($remove_query)){
          $result_delete = $this->tsx->run($remove_query, array('neoid' => (int)$neo_id));
        }
        if(boolval($update_query)){
          $result_update = $this->tsx->run($update_query, $placeholders);
        }
        $result = array('success'=> true); 
      } catch (\Throwable $th) {
        $result = array('success'=> false);
      }
      echo json_encode($result); 
    }



}
?>