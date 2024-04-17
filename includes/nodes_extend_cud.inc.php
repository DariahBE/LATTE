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
          die(); 
        }
        //check how many relations the query returned: 
        if($checkResult->first()->get('relations') === 0){
          //required to make a new relation
          $matchAndConnectResult = $this->tsx->run('MATCH (n), (t) WHERE id(n) = $varid AND id(t) = $etid CREATE (n)-[r:same_as]->(t)', array('varid'=> $existingVariantId, 'etid'=>$entitySource));
          return array('msg'=> 'New relation created', 'node' => $matchAndConnectResult, 'data' => ['uuid'=> $variant_uuid, 'nid'=> $existingVariantId] ); 
          die(); 
        }else{
          //do not modify anything: a relation already exists
          return (array('msg'=>'A relation already exists, no changes made to the database.', 'data' => ['uuid'=> $variant_uuid, 'nid'=> $existingVariantId])); 
          die();
        }
      }else{
        return array('msg'=> 'Invalid entity node.');
        die();
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
        $create = $this->client->run($query, $querydata); 
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


  public function determineRightsSet($requestedLevel){
    //TODO: incomplete method
    include_once(ROOT_DIR.'\includes\user.inc.php');
    $user = new User($this->client); 
    //var_dump($user->myRole); 
    $nodetype = '';         //TODO: determine the nodetype! Some nodes require lower level deletes than others. 
    $ownerShip = False;     //TODO: determine whether or not the user owns the node!!
    $whatRightSetApplies = $user->hasEditRights($user->myRole, $ownerShip); 
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
    //TODO: test implementation of transactions!
      //    1) AJAX/crud/delete.php ==> OK
      //    2) AJAX/fetch_kb.php ==> OK
      //    3) crud/delete.php ==> //TODO
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
      //function WILL cast $id to int!
      //TODO return value has to be made more explicit in interface!
      $query = 'MATCH (n)-[r:'.$edgelabel.']-(m) WHERE id(n) = $neoleft AND id(m) = $neoright DELETE r;'; 
      $querydata = array('neoleft'=> (int)$leftNeoId, 'neoright'=> (int)$rightNeoId); 
      $deletedEdges = $this->tsx->run($query, $querydata); 
      return $deletedEdges; 
    }

    // //TO DO: update needs transactional model!
    // // BU G: code is not even being called, does it make sense to keep??
    // public function update($id, $data, $changePrivateProperties=False){
    //     /**
    //      * changePrivateProperties IS IT EVEN NEEDED??
    //      */
    //     //data is a dictionary: it holds keys-values for the node that should be updated.
    //     //extend the query with kv-pairs to run the update. 
    //     //Foreach KV in $data ==> extend $query with kvpair. 
    //     //step1: validate the ID; get the label and valid propertiekeys. 
    //     $validationQuery = 'MATCH (n) WHERE id(n) = $nodeid RETURN labels(n)[0] AS label'; 
    //     $label = $this->client->run($validationQuery, array('nodeid'=>(int)$id)); 
    //     if(boolval(count($label))){
    //         $label = $label->first()->get('label');
    //     }else{
    //         die('no node matches request');
    //     }
    //     //once validation of the node has passed: get the model!
    //     //only allow models that are defined in the config file: 
    //     if(array_key_exists($label, NODEMODEL)){
    //         $model = NODEMODEL[$label];
    //     }else{
    //         throw new Exception("Missing model");
    //     }
    //     //verify the keys in the $data variable, with the keys in the $model variable. 
    //     //you should have a system where $data can differ from $model, but it shouldn't introduce new kvpairs
    //     foreach($data as $key => $value){
    //         if (!(in_array($key, array_keys($model)))){
    //             throw new Exception("Invalid data provided to the backend. No changes commited to the database.");
    //         }
    //     }
    //     if (boolval(count($data))){
    //         $query = "MATCH (n) WHERE id(n) = ".(int)$id." SET ";
    //         $counter = 1;
    //         $values = array();
    //         $parameters = array(); 
    //         foreach ($data as $key => $value) {
    //             $parameters[] = " n.".$key.' = $somevar_'.$counter.' ';
    //             $values['somevar_'.$counter] = $value;
    //             $counter+=1;
    //         }
    //         $query.=implode(', ', $parameters);
    //         if(boolval($values)){
    //             $this->client->run($query, $values);
    //             return array('status'=>'Changes committed to the database.'); 
    //         }
    //     }else{
    //         return array('status'=>'No changes sent to the database');
    //     }
    // }

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
                  //var_dump($key, NODEMODEL[$label][$key][2], $value);
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
                        throw new Exception('Empty value given for unique attribute ('.NODEMODEL[$label][$key][0].'). Request rejected.');
                      }
                    }
                    if($value !== ''){
                        if(array_key_exists($key, NODEMODEL[$label])){
                            $nodeAttributes[] = ' n.'.$key.' = $placeholder_'.$placeholder;
                            //enforce the correct type of the $value!
                            $reformattedValue = $this->helper_enforceType(NODEMODEL[$label][$key][1],$value); 
                            $placeholderValues['placeholder_'.$placeholder] = $reformattedValue; 
                        }else{
                            throw new Exception("Data does not match node definition. Request rejected.");
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


    /*        DELETE OPERATIONS!!!! */

    public function deleteEntities($id_array){
      /**
       * Takes a list of NEO ids of entities to be deleted from the database. 
       */
    }

    public function deleteAnnotation($id_array){
      /**
       * Takes a list of NEO id's of annotations to be deleted from the database. 
       */
    }

    public function deleteText($id){
      /*takes the NEO ID of a text and deletes it from the DB
      * returns the IDS' of annotation nodes that should be deleted too
      * returns the IDS' of entities that should be deleted too. 
      */
      $repl = []; 
      $relatedAnnoIds = []; 
      $allowedToDelete = $this->determineRightsSet(3); 
      $repl['permission'] = $allowedToDelete; 
      if($allowedToDelete){
        $annosToKillQuery = 'MATCH (n)-[r:contains]-(a) WHERE id(n) = $neoid RETURN id(a) AS killthis;'; 
        $annosToKill = $this->client->run($annosToKillQuery, array('neoid'=>$id));
        $annosToKill = $annosToKill->getResults();
        foreach($annosToKill as $anno){
          $relatedAnnoIds[] = $anno->get('killthis'); 
        }
      }
      //var_dump($relatedAnnoIds);
      $repl['affectedAnnotations'] = $relatedAnnoIds; 
      return $repl; 
    }


}
?>