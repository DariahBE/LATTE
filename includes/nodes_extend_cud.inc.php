<?php
/*
        adds CREATE UPDATE AND DELETE functionality 
        to the nodes class. READ operations are in 
        a separate file (getnode.inc.php)
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
            $value = json_decode($inputvariable);
            //then cast to boolval!
            return boolval($value); 
        }elseif($type === 'uri'){
            //URIS are tricky: you need a valid scheme to begin with. But not all users give this. 
            // use parse_url() to detect the scheme, if it is missing, prepend with https://
            $parsed = parse_url($inputvariable); //returns the components. Should include a host!!!
            //if there's no host: reject! Can't do anything with this. 
            if(!(array_key_exists('host', $parsed))){
                throw new Exception('No host defined, URI rejected'); 
            }
            //If there's no scheme: prepend it with 'https://
            if(!(array_key_exists('scheme', $parsed))){
                $parsed['scheme']='https://'; 
            }
            //re-assemble $parsed into a URI: 
            $scheme   = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
            $host     = isset($parsed['host']) ? $parsed['host'] : '';
            $port     = isset($parsed['port']) ? ':' . $parsed['port'] : '';
            $user     = isset($parsed['user']) ? $parsed['user'] : '';
            $pass     = isset($parsed['pass']) ? ':' . $parsed['pass']  : '';
            $pass     = ($user || $pass) ? "$pass@" : '';
            $path     = isset($parsed['path']) ? $parsed['path'] : '';
            $query    = isset($parsed['query']) ? '?' . $parsed['query'] : '';
            $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
            $url =  "$scheme$user$pass$host$port$path$query$fragment"; 
            throw new Exception('Encoding of URIs has not been tested (nodes_extended_cud.inc.php::helper_enforceType)'); 
            //todo: figure out how to validate URIS!
            $filtered = filter_var($url, FILTER_VALIDATE_URL);
            if($filtered){
                return $url;
            }else{
                throw new Exception('Invalid URI provided: entry was rejected.'); 
            };
        }else{
            //if no typecasting is defined, return whatever is given. 
            return $inputvariable; 
        }
    }
  private function is_entity($neoID){
    //helper function that checks if the given $neoID has a label that belongs to an entity. 
    $labelResult =  $this->client->run('MATCH (n) WHERE id(n) = $id RETURN labels(n) AS labels;', array('id'=>$neoID)); 
    if($labelResult->isempty()){return false;}
    $label = $labelResult->first()->get('labels')[0];
    return $label; 
  }

  //transaction management.
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

//TODO: createVariantRelation needs to have transactional model!
  public function createVariantRelation($label, $entitySource){
    //create a variant or connection between an entity and a variant:
    //if the variant is not yet in de DB, the variant is created.
    //if the variant already exists, a relation is created connecting it to the entity. 
    //$label    =string   = string label to be inserted in the database and used as spellingvariant for the node. 
    //$entitySource =int  = neoID of the entity-node(multilabel)
    //1: check variant.
    $query = 'MATCH (n:Variant) WHERE n.variant = $varlabel RETURN id(n) as id';
    $existsResult = $this->client->run($query, array('varlabel'=>$label));
    $hasResult = !($existsResult->isempty()); 
    //2: check if the entity is an actual entity.
    //Label exists AND is not yet connected to et: SO connect it ==> verify that $entitySource is an $entity.
    $labelCheck = $this->is_entity($entitySource);      
    if($hasResult){
      if(array_key_exists($labelCheck, CORENODES)){
        //there is a matching request!
        $existingVariantId = $existsResult->first()->get('id'); 
        //3: Check that there is no connection between $entitySource and $existingVariantId: 
        $checkResult = $this->client->run('MATCH (n:Variant)-[r:same_as]-(t) WHERE id(n) = $varid AND id(t) = $etid RETURN count(r) AS relations;', array('varid'=>$existingVariantId, 'etid'=>$entitySource));
        //is empty when there is no node found ==> otherwise it will have a property set where relations could be 0 or more. 
        if ($checkResult->isempty()){
          return array('msg'=>'Invalid request: one or more nodes do not exists.'); 
          die(); 
        }
        //check how many relations the query returned: 
        if($checkResult->first()->get('relations') === 0){
          //required to make a new relation
          //var_dump(array('varid'=> $existingVariantId, 'etid'=>$entitySource));
          $matchAndConnectResult = $this->tsx->run('MATCH (n), (t) WHERE id(n) = $varid AND id(t) = $etid CREATE (n)-[r:same_as]->(t)', array('varid'=> $existingVariantId, 'etid'=>$entitySource));
          //var_dump($matchAndConnectResult); 
          return array('msg'=> 'New relation created'); 
          die(); 
        }else{
          //do not modify anything: a relation already exists
          return (array('msg'=>'A relation already exists, no changes made to the database.')); 
          die();
        }
      }else{
        return array('msg'=> 'Invalid entity node.');
        die();
      }
    }else{
      //the variant has no label registered in the DB that matches the request: create one. 
      //and create the relationship IF the related entity node exists: 
      if(array_key_exists($labelCheck, CORENODES)){
        $createAndConnectResult = $this->client->run('MATCH (e) WHERE id(e) = $etid CREATE (n:Variant {variant: $varname, uid: apoc.create.uuid()})-[:same_as]->(e)', array('etid'=>$entitySource, 'varname'=>$label));
        return array('msg'=>'New variant and link created.'); 
        //var_dump($createAndConnectResult); 
      }else{
        //the variant label is not found in the DB and the entity node doesn't have a valid ID:
        return array('msg'=> 'Invalid entity node.');
      }
    }
    

    
  }
  //TODO: dropVariant needs transactional model!!
  public function dropVariant($variantID, $entityID, $detachQuery){
    //drops a variant or connection between a variant and entity:
    //$variantID  = int   = neoID of the Variant-node.
    //$entityID   = int   = neoID of the entity-node(multilabel)
    //$detachQuery= bool  = if true ==> runs a detach delete query; if false remove all relations between $variantID and $entityID that have the same_as-label
    if($detachQuery){
      //deletes all relationships where n is part of and deletes the node. 
      $result = $this->client->run('MATCH (n) WHERE id(n)=$varid DETACH DELETE n;', array('varid'=>$variantID));
    }else{
      //deletes all relationships with the same_as label between n and m;
      $result = $this->client->run('MATCH (n)-[r:same_as]-(m) WHERE id(n) = $varid AND id(m) = $etid DELETE(r);', array('varid'=>$variantID, 'etid'=>$entityID)); 
    }
    return $result;
  }


  public function determineRightsSet($requestedLevel){
    include_once(ROOT_DIR.'\includes\user.inc.php');
    $user = new User($this->client); 
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
    //TODO: delete requires transactional model!
    public function delete($id, $dryRun=False){
        if($dryRun){
            $query_EdgesToBeRemoved = 'MATCH (n)-[r]-() WHERE id(n) = $nodeid RETURN count(r) as count'; 
            $query_NodesToBecomeIsolated = 'MATCH (n)--(p) WHERE id(n) = $nodeid OPTIONAL MATCH (n)--(p)--(i) RETURN n,  p as directlyConnected, i as nullIfIsolated';        //if i == null, then p is only connected to the n-node which will be deleted!
            $deletedEdges = $this->client->run($query_EdgesToBeRemoved, ['nodeid'=>$id]); 
            $isolationDetection = $this->client->run($query_NodesToBecomeIsolated, ['nodeid'=>$id]);
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
            die('defer');
            $query = 'MATCH (n) WHERE id(n) = $nodeid DETACH DELETE (n)';
            $this->client->run($query, ['nodeid'=>$id]); 
        }
    }

    //TODO: update needs transactional model!
    public function update($id, $data, $changePrivateProperties=False){
        /**
         * changePrivateProperties IS IT EVEN NEEDED??
         */
        //data is a dictionary: it holds keys-values for the node that should be updated.
        //extend the query with kv-pairs to run the update. 
        //Foreach KV in $data ==> extend $query with kvpair. 
        //step1: validate the ID; get the label and valid propertiekeys. 
        $validationQuery = 'MATCH (n) WHERE id(n) = $nodeid RETURN labels(n)[0] AS label'; 
        $label = $this->client->run($validationQuery, array('nodeid'=>(int)$id)); 
        if(boolval(count($label))){
            $label = $label->first()->get('label');
        }else{
            die('no node matches request');
        }
        //once validation of the node has passed: get the model!
        //only allow models that are defined in the config file: 
        if(array_key_exists($label, NODEMODEL)){
            $model = NODEMODEL[$label];
        }else{
            throw new Exception("Missing model");
        }
        //verify the keys in the $data variable, with the keys in the $model variable. 
        //you should have a system where $data can differ from $model, but it shouldn't introduce new kvpairs
        foreach($data as $key => $value){
            if (!(in_array($key, array_keys($model)))){
                throw new Exception("Invalid data provided to the backend. No changes commited to the database.");
            }
        }
        if (boolval(count($data))){
            $query = "MATCH (n) WHERE id(n) = ".(int)$id." SET ";
            $counter = 1;
            $values = array();
            $parameters = array(); 
            foreach ($data as $key => $value) {
                $parameters[] = " n.".$key.' = $somevar_'.$counter.' ';
                $values['somevar_'.$counter] = $value;
                $counter+=1;
            }
            $query.=implode(', ', $parameters);
            if(boolval($values)){
                $this->client->run($query, $values);
                return array('status'=>'Changes committed to the database.'); 
            }
        }else{
            return array('status'=>'No changes sent to the database');
        }
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
                $placeholder=1; 
                $placeholderValues = array();
                foreach($data as $key => $value){
                    //empty uri triggers fatal error: empty values should not be parsed as data!!
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
                //var_dump($query);
                //var_dump($placeholderValues);
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



}

/*
                IMPORTANT
there are a few todos in this file to make all nodes
transactionally safe!                

*/


?>