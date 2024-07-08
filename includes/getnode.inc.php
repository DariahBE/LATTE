<?php
//include_once("../config/config.inc.php");
include_once(ROOT_DIR."/includes/client.inc.php");
/*
  this is a program wide default; if there's no key defined to be the primary key of a node;
  the app is going to fall back to the uid-property present in all nodes. That key is automatically
  generated and of the UUIDV4-type.
*/

function helper_extractPrimary($keyName){
  //return PRIMARIES[$keyName] ? PRIMARIES[$keyName] : 'uid';   //ternary operator not working as it should
  if(array_key_exists($keyName, PRIMARIES)){
    if(boolval(PRIMARIES[$keyName])){
      return PRIMARIES[$keyName];
    }
  }
    return 'uid';
}

function helper_parseEntityStyle(){
  foreach (CORENODES as $key => $value) {
    echo '.'.$key.'{background-color:'.$value.';}';
  }
  if (boolval(NERCOLOR)){
    echo '.app_automatic{background-color:'.NERCOLOR.';}';
  }
}



function ignoreRegex($strIn){
  $strOut = str_replace('[', '\[', $strIn);
  $strOut = str_replace('(', '\(', $strOut);
  $strOut = str_replace(']', '\]', $strOut);
  $strOut = str_replace(')', '\)', $strOut);
  $strOut = str_replace('{', '\}', $strOut);
  $strOut = str_replace('}', '\}', $strOut);

  return $strOut;
}

function valueExtract($node, $key){
  try {
    return $node[$key];
  } catch (\Exception $e) {
    return null;
  }
}

function process_entityNodes($nodeIn){
  $id = $nodeIn['id'];
  $wdProp = null; 
  $label = $nodeIn['labels'][0];
  $data = array();
  $model = NODEMODEL[$label];
  foreach ($model as $key => $value) {
    $data[$key] = array(
      'value' => valueExtract($nodeIn['properties'], $key),
      'DOMString' => $value[0],
      'vartype' => $value[1]
    );
    if($value[1]==='wikidata'){
      $wdProp = valueExtract($nodeIn['properties'], $key);
    }
  }
  return(array($id, $label, $data, $wdProp));
}

function process_edge($edgeIn){
  $id = $edgeIn['id'];
  $startnode = $edgeIn['startNodeId'];
  $endnode = $edgeIn['endNodeId'];
  $data = array(
    'edgeID'=>$id,
    'startNodeId'=>$startnode,
    'endNodeId'=>$endnode
  );
  return($data);
}


class Node{
  protected $client;
  function __construct($client) {
    $this->client = $client;
  }


  function getIndexes(){
    /**returns an overview of all Label.properties that have 
     * an index in the linked database. 
     */
    $indexedProperties = array(); 
    $query = 'SHOW INDEXES'; 
    $result = $this->client->run($query); 
     foreach($result as $key=> $value){ 
      #Composite indexes aren't used so [0] is safe to use. 
      $nodeLabel = $value['labelsOrTypes'][0]; 
      $property = $value['properties'][0]; 
      $idxname = $value['name'];
      // if label does not exist in return value: add it
      if(!(array_key_exists($nodeLabel, $indexedProperties))){
        $indexedProperties[$nodeLabel] = array(); 
      }
      // for existing node labels add the properties. 
      if(!(array_key_exists($property, $indexedProperties[$nodeLabel]))){
        $indexedProperties[$nodeLabel][$property] = $idxname; 
      }
     }
    return $indexedProperties; 
  }

  function modifyIndex($label, $prop, $action, $idxname){
    /*adds or removes the index on a $label.prop from a node. 
      $label = labelname
      $prop = propertyname
      $action = add or drop (str)
    */
    $allowed_actions = ['add', 'drop']; 
    if (in_array($action, $allowed_actions)){
      if($action === 'add'){
        //NEO4J does not give you the index name directly in a create statemnt :(
        $query = 'CREATE INDEX IF NOT EXISTS FOR (p:'.$label.') ON (p.'.$prop.');';
      }else{
        if($action === 'drop'){
          if($idxname === null){
            die(); 
          }else{
            $query = 'DROP INDEX '.$idxname.' IF EXISTS;';
          }
        }
      }

      $result =  $this->client->run($query); 
      $added = $result->getSummary()->getCounters()['indexesAdded']; 
      $removed = $result->getSummary()->getCounters()['indexesRemoved']; 
      $idx_name = Null;
      //To get the name we need an extra query if a new index was $added
      if($added === 1 ){
        $extra_query = 'CALL db.indexes() YIELD name, labelsOrTypes, properties
        WHERE labelsOrTypes = ["'.$label.'"] AND properties = ["'.$prop.'"]
        RETURN name;'; 
        $extra_result = $this->client->run($extra_query); 
        if(boolval($extra_result)){
          $idx_name = $extra_result->getResults()[0]['name']; 
        }
      }
      return array('added'=>$added, 'removed'=>$removed, 'name'=>$idx_name); 
    }

  }

  //Text order methods: 
  function getFirstText(){
    /**   PART OF TEXT NAVIGATION LOGIC
     * Looks for the primary key used in TEXT nodes and returns
     * the lowest value found. 
     */
    $nodeType = TEXNODE;
    $propKey = helper_extractPrimary($nodeType);
    $query = 'MATCH(n:'.TEXNODE.') RETURN n ORDER BY n.'.$propKey.' LIMIT 1'; 
    $result = $this->client->run($query); 
    if (boolval(count($result))){
      $recordID = $result[0]['n']->getProperty($propKey);
      return($recordID);
    }
    return False; 
    }

  function getPreviousText($current){
    /**   PART OF TEXT NAVIGATION LOGIC
     * Looks for the primary key used in TEXT nodes and returns
     * the lowest value found which is higher than the given $current value. 
     */
    $nodeType = TEXNODE;
    $propKey = helper_extractPrimary($nodeType);
    $query = 'MATCH (n:'.TEXNODE.')
    WHERE n.'.$propKey.'  < $curval
    WITH n
    ORDER BY n.'.$propKey.' DESC 
    LIMIT 1
    return n; '; 
    $result = $this->client->run($query, array('curval'=>$current)); 
    if (boolval(count($result))){
      $recordID = $result[0]['n']->getProperty($propKey);
      return($recordID);
    }
    return False; 
  }

  function getNextText($current){
    /**   PART OF TEXT NAVIGATION LOGIC
     * Looks for the primary key used in TEXT nodes and returns
     * the highest value found which is lower than the given $current value. 
     */
    $nodeType = TEXNODE;
    $propKey = helper_extractPrimary($nodeType);
    $query = 'MATCH (n:'.TEXNODE.')
    WHERE n.'.$propKey.'  > $curval
    WITH n
    ORDER BY n.'.$propKey.' 
    LIMIT 1
    return n; '; 
    $result = $this->client->run($query, array('curval'=>$current)); 
    if (boolval(count($result))){
      $recordID = $result[0]['n']->getProperty($propKey);
      return($recordID);
    }
    return False; 
  }

  function getLastText(){
    /**   PART OF TEXT NAVIGATION LOGIC
     * Looks for the primary key used in TEXT nodes and returns
     * the highest value found. 
     */
    $nodeType = TEXNODE;
    $propKey = helper_extractPrimary($nodeType);
    $query = 'MATCH (n:'.TEXNODE.')
    RETURN n
    ORDER BY n.'.$propKey.' DESC
    LIMIT 1; ';
    $result = $this->client->run($query);
    if (boolval(count($result))){
      $recordID = $result[0]['n']->getProperty($propKey);
      return($recordID);
    }
    return False; 
  }


function executePremadeParameterizedQuery($query, $parameters){
  /*
    takes a query and parameters argument. the query is a paramaterized cypher query
    the parameters are made to fit the query. The query can then be run as part of 
    the Node Object. 
  */
  $result = $this->client->run($query, $parameters); 
  return $result; 
}

  function countTextsConnectedToEntityWithID($value){
    //this function starts from the automatically generated NEO ID and counts all TEXT nodes that are related to it. 
    $connectedAnnotations = $this->client->run('MATCH (x)--(n:'.ANNONODE.') WHERE id(x) = $nodeval RETURN COUNT(n) AS result', ['nodeval'=>$value]);
    //echo $connectedAnnotations[0]->get('result');
    //var_dump($connectedAnnotations);
    $connectedTexts = $this->client->run('MATCH (x)--(n:'.ANNONODE.')--(t:'.TEXNODE.') WHERE id(x) = $nodeval RETURN COUNT(DISTINCT t) AS result', ['nodeval'=>$value]);
    //b7ba61b4-0985-489f-86af-6d60c206ac5e
    return array('Annotations'=> (int)$connectedAnnotations[0]->get('result'), 'Texts'=>(int)$connectedTexts[0]->get('result'));
  }
  function listTextsConnectedToEntityWithID($value){
    //this function start form the automatically generated NEO ID and lists all TEXT nodes that are related to it.
    $connectedTexts = $this->client->run('MATCH (x)--(n:'.ANNONODE.')--(t:'.TEXNODE.') WHERE id(x) = $nodeval RETURN x, t, n', ['nodeval'=>$value]);
    $result = array(
      'annotations'=>array(),
      'entities'=>array(),
      'texts'=>array()
    ); 
    $primaryForText = helper_extractPrimary(TEXNODE);
    $primaryForAnnotation = helper_extractPrimary(ANNONODE); 
    //return the PK of each Text and Annotation. 
    foreach($connectedTexts as $key => $value){
      $primaryForEt = helper_extractPrimary($value['x']['labels'][0]);
      $entitityValue = $value['x']->getProperty($primaryForEt);
      if (!in_array($entitityValue, $result['entities'])){
        $result['entities'][] = $entitityValue;
      }
      $textValue = $value['t']->getProperty($primaryForText);
      if (!in_array($textValue, $result['texts'])){
        $result['texts'][] = $textValue;
      }
      $annotationValue = $value['n']->getProperty($primaryForAnnotation);
      if (!in_array($annotationValue, $result['annotations'])){
        $result['annotations'][] = $annotationValue;
      }
    }
    return $result;
  }

  function getDistinctLabels(){
    /**   Returns a list of labels used in the database that match a key present in the NODETRANSLATIONS  constant 
     *    matching is required to prevent leaking amount of priv_users. 
     */
    $result = $this->client->run('MATCH (n) WHERE NOT (n:priv_user) RETURN DISTINCT labels(n) AS label');
    //return a translated dict:
    $data = array();
    foreach ($result as $record){
      $label = $record->get('label')[0];
      if(boolval(NODETRANSLATIONS) AND array_key_exists($label, NODETRANSLATIONS)){
        $labelTranslated = NODETRANSLATIONS[$label];
      }else{
        $labelTranslated = false;
      }
      array_push($data, array($label,$labelTranslated));
    }
    return $data;
  }

  function getConnections($label){
    $result = $this->client->run('MATCH(n:'.$label.')-[r]-() UNWIND(r) AS relations RETURN DISTINCT type(relations) AS relationtype'); 
    $data = array();
    foreach($result as $record){
      $reltype = $record['relationtype'];
      //var_dump(str_starts_with($reltype, 'priv_'));
      if (!(str_starts_with($reltype, 'priv_'))){
        $humanReadable = false; 
        if (array_key_exists($reltype, EDGETRANSLATIONS)){
          $humanReadable = EDGETRANSLATIONS[$reltype];
        }
        array_push($data, array($reltype, $humanReadable)); 
      }
    }
    return $data;
  }

  function findEntitiesWithVariantValue($entityLabel, $variantValue){
    //looks for entities with a known label that have a connection to a given variant!
    //Variant.variant is a hardcoded node/propery name. It is is required to be in the datamodel!
    $query = "OPTIONAL MATCH (e:$entityLabel)-[]-(v:Variant {variant: '$variantValue'}) RETURN e";
    $results = $this->client->run($query);
    return $results;
}

  function countNodesByLabel(){
    $result = $this->client->run('MATCH (n) where not (n:priv_user) RETURN DISTINCT count(labels(n)) as f, labels(n) as label ORDER BY f DESC;');
    $data = array();
    $total = 0;
    foreach ($result as $record){
      $count = $record->get('f');
      $label = $record->get('label')[0];
      if(boolval(NODETRANSLATIONS) AND array_key_exists($label, NODETRANSLATIONS)){
        $data[NODETRANSLATIONS[$label]] = $count;
      }else{
        $data[$label] = $count;
      }
      $total +=$count;
    }
    $data['total'] = $total;
    return $data;
  }

  function countEdgesByLabel(){
    $result = $this->client->run("MATCH (n)-[r]-(a) where not (n)-[r:priv_created]-(a) RETURN TYPE(r) AS label, COUNT(r) AS f ORDER BY f DESC;");
    $data = array();
    $total = 0;
    foreach($result as $record){
      $count = $record->get('f');
      $label = $record->get('label');
      if(boolval(EDGETRANSLATIONS) AND array_key_exists($label, EDGETRANSLATIONS)){
        $label = EDGETRANSLATIONS[$label];
      }
      $data[$label] = $count;
      $total +=$count;
    }
    $data['total'] = $total;
    return $data;
  }

  function matchTextByNeo($id){
    /**
     * Returns a textnode by the given Neo ID()
     *  */
    $neo = (int)$id; 
    //var_dump($neo); 
    $result = $this->client->run('MATCH (n:'.TEXNODE.') WHERE id(n) = $nodeval RETURN n LIMIT 1', ['nodeval'=>$neo]);
    if (count($result) == 0){return false;} 
    //$this->$neoid = (int)$neo;  
    return $result->first()['n']['properties'];
  }

  function fetchKnowledgebase($id){
    /**
     * expects the NEOID of a node, then fetches all nodes connected 1 hop away with the see_also edge in between.
     * Returns all connected knowledgebases.
     */
    $data = array(); 
    $query = 'MATCH (n)-[:see_also]-(k) WHERE id(n) = $givenId return k'; 
    $result = $this->client->run($query, ['givenId'=>$id]);
    //don't echo $result directly; it'll leak database details!
    foreach($result as $kb){
      $data[]=$kb; 
    }
    return $data; 
    
  }

  function buildSilos($id){
    $crossrefdata = $this->crossreferenceSilo($id);
    $siloData = array();
    foreach ($crossrefdata as $record) {
      $row = array();
      foreach (NODES["See_Also"] as $p){
        try{
          $v = $record->get("t")->getProperty($p);
        }catch(e){
          $v = null;
        }
        $row[$p] = $v;
      }
      $siloData[] = $row;
    }
    return $siloData;
  }

  function fetchLabelByUUID($uuid){
    /**
     * returns the label of a node that has a specific UUID; only required for the ANNONODE on Annotation_auto-node
     * ANNONODE is config dependent, Annotation_auto is based on application logic. So can be coded in. 
    */
    $allowed_labels = array(ANNONODE, 'Annotation_auto');
    $query = 'MATCH (n) WHERE n.uid = $uuid RETURN labels(n) as label';
    $data = $this->client->run($query, array('uuid' => $uuid));
    if(count($data)===0){
      return False;
    }
    try{
      $label = $data[0]->get('label')[0]; 
    }catch(Exception){
      return False; 
    }
    if(in_array($label, $allowed_labels)){
      return $label;
    }else{
      return False; 
    }
  }

  function extractCoreID($nodedata, $coreproperty, $fallback = 'uid'){
    if(array_key_exists($coreproperty, array($nodedata['properties']))){
      // By default if possible, return the coreproperty as defined
      // by the config.inc.php nodesmodel that matches the nodetype
      // requested by the enduser. 
      return $nodedata->getProperty($coreproperty); 
    }else{
      // UID is a hardcoded property that should be in ALL nodes.
      // it is a good fallbackproperty that guarantees uniqueness
      // in case $coreproperty is missing. Catches bugs that are 
      // caused by a missing coreproperty (e.g. adding new properties
      // after project start); 
      return $nodedata->getProperty($fallback); 
    }
  }


  function matchSingleNode($type, $key, $value){
    if (is_numeric($value)){
      $value = $value + 0;   //can be float too . adding +0 will allow php to automatically set the correct type.
    }
    if(boolval($type)){
      $typeQuery = ':'.$type;
    }else{
      $typeQuery = $type;
    }
    //$result = $this->client->run("MATCH (node:".$type."{".$key.":".$value."}) RETURN node, id(node) AS ID LIMIT 1");
    $result = $this->client->run('MATCH (node'.$typeQuery.'{'.$key.': $nodeval}) RETURN node, id(node) AS ID LIMIT 1', ['nodeval'=>$value]);
    // A row is a \Laudis\Neo4j\Types\CypherMap
    $node = false;
    //var_dump($result);
    foreach ($result as $record) {
        // Returns a \Laudis\Neo4j\Types\Node
        $core = helper_extractPrimary($type);
        $node = array(
          //get the name of the text PK:
          'coreID'=> $this->extractCoreID($record['node'], $core),
          'model'=>array_key_exists($type, NODES) ? NODES[$type] : null, 
          'neoID'=>$record['ID']
        );
    }
    if(boolval($result)){
      $node['data'][]=$result;
    }
    return $node;
  }


  function getNeighbours($id, $relation = false, $exclude = false){
    //use the built in node ID (not the UUID) to extract neighbouring nodes from a core node.
    //query is undirected!!
    // UPDATED FOR PATCH:
    /*
     * - do not return priv_user nodes
     * - patch for nodes that have no neighbours: (n)-[r]-(t) is an exact pattern match
     *     ==> FIX: use optional match [r]-(t) for exact match (n)
    */
    //exclude a single relationtype as part of the optional match
    if(boolval($exclude)){
      $excludedPart = ' AND NOT r:'.$exclude.' ';
    }
    else{
      $excludedPart = ''; 
    }
    if(!boolval($relation)){
      $result = $this->client->run('
      MATCH (n) WHERE id(n) = $providedID AND NOT n:priv_user 
      OPTIONAL MATCH (n)-[r]-(t) WHERE NOT t:priv_user '.$excludedPart.'
      RETURN n,r,t', ['providedID'=>(int)$id]);  
    }else{
      $result = $this->client->run('
      MATCH (n) WHERE id(n) = $providedID AND NOT n:priv_user 
      OPTIONAL MATCH (n)-[r:'.$relation.']-(t) WHERE NOT t:priv_user '.$excludedPart.'
      RETURN n,r,t', ['providedID'=>(int)$id]);

    }

    return $result;
  }

  function crossreferenceSilo($id){
    //uses the built in node ID from ONE See_Also node to find all other See_Also nodes that share the same entity.
    //query is directed!
    $result = $this->client->run('MATCH (n:See_Also)<--(b) WHERE id(n) = $providedID RETURN id(b) AS id', ['providedID'=>(int)$id]);
    $result2 = $this->client->run('MATCH (n)-->(t:See_Also) WHERE id(n) = $providedID2 RETURN t',['providedID2' => (int)$result->first()->get('id')]);
    return $result2;
  }

  function getEdges($nodeId, $ofOptionalType=''){
    //coreNode by $nodeId:    MATCH (n) WHERE ID(n) = $nodeId RETURN n
    //      MATCH (n) MATCH (n)-[r]-() WHERE ID(n) = $nodeId RETURN n,r
    /*METHOD HAS TO BE UPDATED TO DEAL WITH CYPHERLISTS WHERE RELATIONS VARY FROM
    ONE-TO-NONE to ONE-TO-MANY
    */
    $edgeLabel = $ofOptionalType ? ":$ofOptionalType" :  '';
    $matchOnCoreNodeID = 'MATCH (n)-[r'.$edgeLabel.']-(b) WHERE ID(n) = $nodeId RETURN n,r,b';
    $result = $this->client->run($matchOnCoreNodeID, ['nodeId'=>(int)$nodeId]);
    //return($result);
    return $result;
  }


  function getTextsSharingEntity($nodeId, $publicOnly=true){
    /*
      Method that takes as an input the internal NEO ID entity (Place/Person/Event)
      And outputs all texts that have the given entity as a related annotation.
      Entities and texts are two hops away in the datamodel. Query can be sped up by making it directional:
      a laudis/summarizedresult databag is returned
    */
    $nodeId = (int)$nodeId;
    $constraintOnAnnotationLevel = '';
    if ($publicOnly){
      $constraintOnAnnotationLevel = '{private:false}';
    }
    $query = "MATCH (n)<-[q:references]-(a:".ANNONODE."{$constraintOnAnnotationLevel})<-[s:contains]-(t:".TEXNODE.") where id(n)=$nodeId return t";
    $result = $this->client->run($query, [$nodeId]);
    return $result;
  }



  function getEntities($entityType, $entityValue, $caseSensitive=false, $limit=100, $offset=0, $allow_levenshtein=false, $levenshtein_hits = 5){
    // All spelling variants are linked to an entity, you should look at the variants
    // track them back to the related entity and see if that matches the option type given!
    // Levenshtein lookups are only allowed if there is no direct match found. 
    // with the option levenshtein_hits parameter you can increase the amount of extra nodes returned. 
    $lim = (int)$limit; 
    $offset = (int)$offset;   // intended to bese used as pagination!!! So multiply with $limit. 
    boolval($offset) ? $extraOffset = ' SKIP '.$lim*$offset : $extraOffset = '';
    $limString = ' ORDER BY collectiveScore '.  $extraOffset.' LIMIT '.$lim; 
    //$extraOffset = ''
    //case sensitive ==> Very Fast:
    if(boolval($entityType)){
      $entityType = ':'.$entityType;
    }
  if($allow_levenshtein){
    $cypherQuery = '
      // Match the variant :$nameValue2 and its relationships
      MATCH (v {variant:$nameValue2})-[r1:same_as]-(q'.$entityType.')
      OPTIONAL MATCH (q)-[r3:see_also]->(j:See_Also)
      
      // Collect direct matches
      WITH v, q, r1, r3, j, size((q)--()) AS qcount, 
          COALESCE(size((q)--()), 0) AS collectiveScore
      RETURN v, q, r1, r3, j, qcount, collectiveScore, 1 as distance
      UNION
      // Handle cases where there is no direct match by using Levenshtein distance
      CALL {
          WITH $nameValue2 AS target
          // Find nodes that could be potential matches
          MATCH (v)
          WHERE NOT EXISTS { MATCH (v {variant:$nameValue2}) }
          WITH v, apoc.text.levenshteinDistance(v.variant, target) AS distance
          // Sort by distance and take the top 5 closest matches
          ORDER BY distance
          LIMIT '.(int)$levenshtein_hits.' 
          RETURN v, distance
      }
      MATCH (v)-[r1:same_as]-(q'.$entityType.')
      OPTIONAL MATCH (q)-[r3:see_also]->(j:See_Also)
      WITH COALESCE(size((q)--()), 0) AS collectiveScore, 
          v, q, r1, r3, j, size((q)--()) AS qcount, distance
      RETURN v, q, r1, r3, j, qcount, collectiveScore, distance
      '.$limString;
  
    $placeholders = array(
      'nameValue2'=>$entityValue
    );
  }else if($caseSensitive){
      // $cypherQuery = '
      //     //OPTIONAL MATCH (p'.$entityType.')
      //     MATCH (v {variant:$nameValue2})-[r1:same_as]-(q'.$entityType.')
      //     // OPTIONAL MATCH (p)-[r2:see_also]->(i:See_Also)
      //     OPTIONAL MATCH (q)-[r3:see_also]->(j:See_Also)
      //     WITH COALESCE(size((p)--()), 0)+COALESCE(size((q)--()), 0) AS collectiveScore, p AS p, v AS v, q AS q, r1 AS r1, r2 AS r2, r3 AS r3, i AS i, j AS j
      //     RETURN p,v,q,r1,r2,r3,i,j, size((p)--()) AS pcount, size((q)--()) AS qcount, collectiveScore
      //      '.$limString;
        $cypherQuery = '
          MATCH (v {variant:$nameValue2})-[r1:same_as]-(q'.$entityType.')
          OPTIONAL MATCH (q)-[r3:see_also]->(j:See_Also)
          WITH COALESCE(size((q)--()), 0) AS collectiveScore, v AS v, q AS q, r1 AS r1, r3 AS r3, j AS j
          RETURN v,q,r1,r3,j, size((q)--()) AS qcount, collectiveScore
          '.$limString;
      $placeholders = array(
          'nameValue2'=>$entityValue
      );
    }else{
      //case Insensitive ==> using regex
      $entityValueCleaned = ignoreRegex($entityValue);
      // $cypherQuery = '
          // //OPTIONAL MATCH (p'.$entityType.')
          // MATCH (v)-[r1:same_as]-(q'.$entityType.') WHERE v.variant =~ $nameValue2
          // // OPTIONAL MATCH (p)-[r2:see_also]->(i:See_Also)
          // OPTIONAL MATCH (q)-[r3:see_also]->(j:See_Also)
          // WITH COALESCE(size((p)--()), 0)+COALESCE(size((q)--()), 0) AS collectiveScore, p AS p, v AS v, q AS q, r1 AS r1, r2 AS r2, r3 AS r3, i AS i, j AS j
          // RETURN p,v,q,r1,r2,r3,i,j, size((p)--()) AS pcount, size((q)--()) AS qcount, collectiveScore
          //  '.$limString;

        $cypherQuery = '
        MATCH (v)-[r1:same_as]-(q'.$entityType.') WHERE v.variant =~ $nameValue2
        OPTIONAL MATCH (q)-[r3:see_also]->(j:See_Also)
        WITH COALESCE(size((q)--()), 0) AS collectiveScore, v AS v, q AS q, r1 AS r1, r3 AS r3, j AS j
        RETURN v,q,r1,r3,j, size((q)--()) AS qcount, collectiveScore
        '.$limString;
      $placeholders = array(
          'nameValue2'=>'(?i)'.$entityValueCleaned
      );
    }


  
    $resultRaw = $this->client->run($cypherQuery,$placeholders);
    $formattedResults = array(
      'nodes'=>array(),
      'silo'=>array(),
      'edges'=>array(),
      'labelvariants'=>array(),
      'meta'=>array('entities'=>0), 
      'weights'=>array(), 
      'levenshtein_dist' => array()
    );
    $registeredNodes = array();
    $registeredEdges = array();
    $entities = 0;
    $lev_dists = array(); 
    foreach ($resultRaw as $result){
      //var_dump($result['distance']); 
      //nodes:  $result -> v = entity variant
        //      DROPPED YOU CAN NOT MATCH DIRECTLY: -----$result =>p, = entity------
        //      $result =>q = entity: equals P when matchted against a label variant.
        //      $result =>i,j entity that link to external project
        // processing i-nodes is obsolete: reduced query complexity. 
        // /*if(!(is_null($result['i']))){
        //   $iPartner = process_entityNodes($result['i']);
        //   $iPartner['siloForEt'] = 0;

        //   if(!(in_array($iPartner[0], $registeredNodes))){
        //     $registeredNodes[] = $iPartner[0];
        //     $formattedResults['silo'][] = $iPartner;
        //   }
        // }*/
        if(!(is_null($result['j']))){
          $jPartner = process_entityNodes($result['j']); 
          $jPartner['siloForEt'] = $result['q']['id'];
          if(!(in_array($jPartner[0], $registeredNodes))){
            $registeredNodes[] = $jPartner[0];
            $jPartner['uuid'] = (array_key_exists('uid', $result['j']['properties']->toArray())) ? $result['j']['properties']['uid'] : null; 
            $formattedResults['silo'][] = $jPartner;
          }
        }
        if(!(is_null($result['v']))){
          $variant = process_entityNodes($result['v']);
          if(!(in_array($variant[0], $registeredNodes))){
            $registeredNodes[] = $variant[0];
            $formattedResults['labelvariants'][] = $variant;
          }
        }
        // processing p-nodes is obsolete: reduced query complexity. 
        // /*
        // if(!(is_null($result['p']))){
        //   $entity = process_entityNodes($result['p']);
        //   $etWeight = $result['pcount'];
        //   if(!(in_array($entity[0], $registeredNodes))){
        //     $formattedResults['weights'][$entity[0]] = $etWeight; 
        //     if($allow_levenshtein){
        //       $distance = $result['distance']; 
        //       var_dump($entity[0], $distance); 
        //       $formattedResults['levenshtein_dist'][$entity[0]] = $distance; 
        //     }
        //     $registeredNodes[] = $entity[0];
        //     $entities+=1;
        //     $formattedResults['nodes'][] = $entity;
        //   }
        // }*/
        if(!(is_null($result['q']))){
          $entity = process_entityNodes($result['q']);
          $etWeight = $result['qcount'];
          if(!(in_array($entity[0], $registeredNodes))){
            $formattedResults['weights'][$entity[0]] = $etWeight; 
            $registeredNodes[] = $entity[0];
            $entities+=1;
            $formattedResults['nodes'][] = $entity;
          }
          if($allow_levenshtein){
            $distance = $result['distance']; 
            $lev_dists[$entity[0]][] = $distance; 
          }
        }

      //EDGES:
      if(!(is_null($result['r1']))){
        $edge = process_edge($result['r1']);
        if(!(in_array($edge['edgeID'], $registeredEdges))){
          $registeredEdges[] = $edge['edgeID'];
          $formattedResults['edges'][] = $edge;
        }
      }
      
      //Obsolete
      // processing r2-edges is obsolete: reduced query complexity. 
      // /*
      // if(!(is_null($result['r2']))){
      //   $edge = process_edge($result['r2']);
      //   if(!(in_array($edge['edgeID'], $registeredEdges))){
      //     $registeredEdges[] = $edge['edgeID'];
      //     $formattedResults['edges'][] = $edge;
      //   }
      // }*/
      if(!(is_null($result['r3']))){
        $edge = process_edge($result['r3']);
        if(!(in_array($edge['edgeID'], $registeredEdges))){
          $registeredEdges[] = $edge['edgeID'];
          $formattedResults['edges'][] = $edge;
        }
      }

    }
    $lev_dists = array_map('min', $lev_dists);
    $formattedResults['meta']['entities'] = $entities;
    $formattedResults['levenshtein_dist'] = $lev_dists; 
    //HIGH WEIGHTS AND LOW LEVENSHTEIN_DIST ARE BEST MATCHING NODES. 
    return $formattedResults;
    //$result should be processed in a NODE ; EDGE list
  }

  function generateURI($id){
    // GENERATES THE STABLE URI
    //finds the node by it's NEO-id, returns a stable identifier;
    //The NEO-id is unstable and should not be used in the frontend to identify a node.
    $query = 'MATCH (n) WHERE id(n) = $providedID return n';
    $result = $this->client->run($query, ['providedID'=>(int)$id]); 
    //if the resultset has at least one row; get the row ==> the first row is also the only row!!
    if(boolval($result->count())){
      $result = $result->first()->get('n');
      //var_dump($result);
      $label = $result['labels'][0]; 
      if(!in_array($label, array_keys(NODEMODEL))){return array();}
      $model = NODEMODEL[$label];
      //look for access to the primary key data. What key in the returned NODE type is the Primary Key. (unique field)
      $found_key = array_search(true, array_column($model, 2), true);
      //var_dump($found_key); // can be 0, but 0 does not equal false. Do a type sensitive comparison: 
      if ($found_key === false){
        //there's no primary key defined: so fallback to the default uid property: 
        $primaryKeyName = 'uid';
      }else{
        $keys = array_keys($model);
        $primaryKeyName = $keys[$found_key];
      }
      //TEXT is the core component: make this unique.
      //any other node should be prefixed with /URI/
      if(strtolower($label)==='text'){
        $insert = '';
      }else{
        $insert = 'URI/';
      }
      $URLString = trim(WEBURL, '/').'/'.$insert.$label.'/'.$result['properties'][$primaryKeyName];
      $result = array($URLString); 
      //die();
    }else{
      //if the id requested is not in the DB: return an empty array. 
      $result = array();
    }
    //var_dump($result['labels'][0]);
    return $result; 
  }

  public function checkOwnershipOfNode($id, $userid){
    /** takes the internal NEO id of the node and the internal 
     * neo id of the user. Then checks if the two are connected
     * by an edge. 
     */
    $query = 'MATCH (u:priv_user)-[r:priv_created]->(n) WHERE id(n) = $node AND id(u) = $user return n, r, u'; 
    $result = $this->client->run($query, ['node'=>(int)$id, 'user'=>(int)$userid]);
    //if the resultset has at least one row; get the row ==> the first row is also the only row!!
    if(boolval($result->count())){
      return True;
    }
    return False; 
  }

  public function countConnections($id){
    /* takes the NEO ID of a node and returns the count of public edges to the user
    * only edges between nodes in the datamodel are counted.
    */
    $query = 'MATCH (n)-[r]-(t) WHERE id(n) = $id return n,r,t '; 
    $data = array('id' => $id); 
    $result = $this->client->run($query, $data);
    $i=0;
    foreach($result as $row){
      if (array_key_exists($row['t']['labels'][0], NODEMODEL)){
        $i+=1;
      }
    }
    return ($i); 

  }


  public function findEntity($id){
    $result = array('entity'=> array(), 'labelVariants'=>array());
    $query = 'match(n)-[r:references]-(p) where n.uid = $graphid return p, id(p) as entityID'; 
    $data = $this->client->run($query, ['graphid'=>$id]); 
    $result['entity']['neoID'] = $data[0]['entityID'];
    $etStableUri = $this->generateURI($data[0]['entityID']); 
    foreach($data as $row){
      $node = $row['p']; 
      $nodeType = $node['labels'][0];
      $model = NODEMODEL[$nodeType]; 
      $result['entity']['type'] = $nodeType;
      $showAs = array();
      foreach($node['properties'] as $property => $value){
        if(array_key_exists($property, $model)){
          $showAs = array($model[$property][0], $node['properties'][$property], $model[$property][1]);
          $showAs = array($property, $value, true, null);
          $result['entity']['properties'][$property] = $showAs;
        }
      }
      $result['entity']['stableURI'] = $etStableUri; 
    }
    return $result; 
  }



  public function findVariants($id){
    /**
     *      PROBLEM > Solved
     * findvariants should return the variant property. This is a required property, so it can be hardcoded.
     * also return the NEOID
     * and the uid. 
     */
    //finds the variants of a node when given the internal NEO ID: 
    $result = array();
    $query2 = 'match(v)-[r:same_as]-(n) where id(n) = $entityid return v, id(v) as neoid' ;
    $data2 = $this->client->run($query2, ['entityid'=> (int)$id]);
    foreach($data2 as $labelvariant){
      $variantRow = $labelvariant['v'];
      $neoID = (int)$labelvariant['neoid']; 
      $rowProperties = $variantRow['properties']; 
      //var_dump($rowProperties['variant'] ?? Null); 
      $result['labelVariants'][] = ['DOMstring'=>'Label', 'value'=>$rowProperties['variant'] ?? Null, 'uid'=>$rowProperties['uid'] ?? Null, 'neoID'=>$neoID]; 
    }
    return $result;
  }

  public function checkUniqueness($label, $property, $value, $castTo){
    if($castTo == 'int'){
      $value = (int)$value;
    }elseif($castTo == 'bool'){
      if(strtolower($value)=='true'){
        $value = true;
      }else{
        $value = false;
      }
    }

    $query = 'MATCH (n:'.$label.' {'.$property.': $value}) RETURN (n)';
    $data = $this->client->run($query, ['value'=>$value]); 
    return $data;

  }

  public function getwikidataValue($neoid){
    //looks up a node by NEOID
    // sees if there's a wikidata field defined for it.
    // if true, returns QID else, return false. 
    $query = 'MATCH (n) WHERE id(n) = $graphid RETURN n'; 
    $data = $this->client->run($query, ['graphid'=>$neoid]); 
    //var_dump($data); 
    foreach($data as $row){
      //get nodeLabel:
      $selectedLabel = $row['n']->getLabels()[0];
      if (array_key_exists($selectedLabel, NODEMODEL)){
        $model = NODEMODEL[$selectedLabel];
        $x = array_filter($model, function ($value, $key){
          //var_dump($value); 
          if($value[1]==='wikidata'){
            return $key;
          } return false;     //no wd property for this node!!
        }, ARRAY_FILTER_USE_BOTH);
        if(boolval($x)){
          foreach ($row['n']['properties'] as $prop=> $assumedQID){
            if ($prop ===  key($x)){
              return $assumedQID; 
              //validate Qid: should match regex ^Q[0-9]*$
              if(preg_match("/^Q[0-9]*$/", $assumedQID)){
                return $assumedQID; 
              }
            }
          }
          
        }
      }
      

    }
    return false;     //default!
  }

  public function fetchWikidataFromAnyPossibleEt($qid){
    //scans the entire nodesmodel variable, 
    //extracts all fields that have  a wikidatavariable set
    //returns nodes matching the provided Qid. 
    //var_dump(NODEMODEL);
    $hits = array();
    foreach(NODEMODEL as $labelName => $properties){
      //echo $labelName;
      $x = array_filter($properties, function($propconfig, $propname){
        if($propconfig[1] === 'wikidata'){
          return $propname;
        }
      }, ARRAY_FILTER_USE_BOTH );
      if (boolval($x)){
        $hits[]= array($labelName, array_keys($x)[0]);
      }
    }
    $results = array(); 
    if(boolval(count($hits))){
      //now iterate over the $hits list and find any matching entity, where the property equals $qid.; 
      foreach($hits as $pair){
        //var_dump($pair); 
        $labelName = $pair[0]; 
        $property = $pair[1]; 
        $query = 'MATCH (n:'.$labelName.') WHERE n.'.$property.' = $id RETURN id(n) as neoID';
        $data = $this->client->run($query,array('id'=>$qid));
        foreach($data as $row){
          $results[] = $row->get('neoID'); 
        }
        
        //var_dump($data['']);
        //echo 'NEXT: ';
      }
    }
    //no hits => return empty array
    return $results; 
  }

  public function fetchLabelById($id){
    /**
     * Uses the internal NEO id to fetch the label of one node!
     */
    $query = 'MATCH (n) WHERE id(n)= $neoid AND NOT n:priv_user RETURN n'; 
    $data = $this->client->run($query, array('neoid'=>$id)); 
    foreach($data as $row){
      $et = $row->get('n'); 
      $etlabel = $et['labels'][0]; 
      return $etlabel; 
    }
  }

  public function fetchModelByLabel($label){
    if(array_key_exists($label, NODEMODEL)){
      return NODEMODEL[$label]; 
    }else{
      return false;
    }
  }





  // OBSOLETE CODE was used for testing purposes.
        // public function testNewQuery($nodeId, $userid){
        //   $query = 'MATCH (n1)
        //   WHERE id(n1) = $neoid
        //   OPTIONAL MATCH (n1)-[r:priv_created]-(p:priv_user)
        //   RETURN
        //   CASE 
        //       WHEN n1.private = false THEN n1
        //       WHEN n1.private is null THEN n1
        //       WHEN n1.private = true and p.user_sqlid = $user THEN n1
        //   END AS n; ';
        //   $queryData = array(
        //     'neoid' => (int)$nodeId, 
        //     'user' => $userid
        //   ); 
        //   $data = $this->client->run($query, $queryData); 

        //   foreach($data as $row){
        //     $et = $row->get('n'); 
        //     var_dump($et); 
        //   }

        // }
  //END OF obsolete code. 

  public function fetchRawEtById($id){
    //returns the raw node data by neoid (neo4J property)
    //with node label.
    //the byUser variable defaults to 0, which will only return the public nodes
    //if an id is provided it will return the public nodes and nodes created by the associated user id which are marked private. 
    $queryData = array(
      'neoid' => $id
    );
    //REQUIRED: relation priv_created is required to determine priv_user owner of node n1 
    //REQUIRED: property .private is required for nodes to be marked as private
    $query = 'MATCH (n1)
    WHERE id(n1) = $neoid
    OPTIONAL MATCH (n1)-[r:priv_created]-(p:priv_user)
    RETURN
    CASE 
        WHEN n1.private = false THEN n1
        WHEN n1.private is null THEN n1
        WHEN n1.private = true and p.user_sqlid = $user THEN n1
    END AS n;'; 
    //user sqlid!!
    $uid = isset($_SESSION['userid']) ? $_SESSION['userid'] : -1; 
    $queryData = array('neoid' => (int)$id, 'user' => $uid); 

    $data = $this->client->run($query, $queryData); 
    $repl = array(
      'label' => null, 
      'properties' => null
    );
    foreach($data as $row){
      $et = $row->get('n'); 
      if($et === null){continue;}
      $etlabel = $et['labels'][0]; 
      $repl['label'] = $etlabel;
      $etprops = $et['properties']; 
      $etModel = NODEMODEL[$etlabel];
      foreach($etprops as $k => $v){
        if (array_key_exists($k, $etModel)){
          $humanReadableKey = $etModel[$k][0];      //human readable key
          $value = $etprops[$k];    
          //minor update $k is the NEO4J propertye name                //value; 
          $repl['properties'][$k]=array($humanReadableKey, $value, $k); 
        }
      }
    }
    return $repl;
  }


  public function checkNodeVisibility($nodeObject, $owner, $userId){
    //TODO (Document): this has to be documented. 
    //    DOCUMENT: the 'private' key is a reserved keyword by application logic
    //    It should be present for all nodes that need to have privacy levels set. 
    if (isset($nodeObject['properties']['private']) && $nodeObject['properties']->get('private') === true && $userId != $owner){
      return True; 
    }
    return False; 
  }

  public function fetchEtById($id){ 
    //returns the properties of a node with the human readable labels provided!
    $query = 'MATCH (n) WHERE id(n)= $neoid AND NOT n:priv_user RETURN n'; 
    $query = 'MATCH (n) 
    WHERE id(n) = $neoid
    AND NOT n:priv_user
    OPTIONAL MATCH (n)-[r:priv_created]-(p)
    RETURN n, p.user_sqlid AS owner'; 
    $data = $this->client->run($query, array('neoid'=>$id)); 

    //call userID here and pass as an argument, better than calling it x times
    //for x records.
    $userId = -1; 
    //user sqlid
    if (isset($_SESSION['userid'])) {
      $userId = $_SESSION['userid'];
    }

    
    $repl = array();
    //If a node is marked as private and it does not belong to the user who created it:
    // then do not process it further. 
    foreach($data as $row){
      $et = $row->get('n'); 
      $owner = $row->get('owner'); 
      //if(isset($et['properties']['private']) && $et['properties']->get('private') === true && $userId != $owner){
      if($this->checkNodeVisibility($et, $owner, $userId)){
        die('private node detected'); 
      }
      $etlabel = $et['labels'][0]; 
      $etprops = $et['properties']; 
      $etModel = NODEMODEL[$etlabel];
      foreach($etprops as $k => $v){
        if (array_key_exists($k, $etModel)){
          $humanReadableKey = $etModel[$k][0];      //human readable key
          $value = $etprops[$k];                    //value; 
          $repl[$k] = array(
            "value" => $value,
            "DOMString" => $humanReadableKey,
            "vartype" => $etModel[$k][1]
          ); 
        }
      }
    }
    return array($repl, $etprops);
  }

  public function distinctSilosForText($id){
    /**Takes the NEO ID of a text and returns a list of NEO IDS of all
     * nodes that are connected using the see_also relation. 
     */
    $query = 'MATCH(n)-[r:see_also]-(a) WHERE id(n) = $neoid RETURN id(a) '; 
    $data = $this->client->run($query, array('neoid'=>(int)$id));
    return $data;
  }

  public function distinctEntitiesInText($id){
    /**Takes the NEO ID of a text and returns a list of NEO ID of all unique
     * entities that are connected to the text. 
    */
    $query = 'match(n)-[r:contains]-()-[t:references]-(p) where id(n) = $neoid return id(p)'; 
    $data = $this->client->run($query, array('neoid'=>(int)$id));
    return $data; 
  }

  public function distinctAnnotationsInText($id){
    /** Takes the NEO ID of a text and returns a list of NEO IDs of all related 
     * annotations in the text. RETURNS A PHP ARRAY OF ANNOTATION IDS
     */
    $query = 'match(n)-[r:contains]-(p) where id(n) = $neoid return id(p)'; 
    $data = $this->client->run($query, array('neoid'=>(int)$id))->getResults();
    $repl = array(); 
    foreach($data as $row){
      $repl[] = $row['id(p)']; 
    }
    return array_unique($repl); 
  }

  public function countConnectionsOver($id, $label){
    //takes an INT neo ID and label. counts how many label-relations the node with a given ID has. 
    $query = 'MATCH (n)-[r:'.$label.']-(m) WHERE id(n) = $neoid RETURN count(r) AS count';
    $data = $this->client->run($query, array('neoid'=>(int)$id));
    return $data;
  }

  public function countConnectionsBetweenAndOver($id1, $id2, $label){
    $query = 'MATCH (n)-[r:'.$label.']-(m) WHERE id(n) = $neoid AND id(m) = $neoid2 RETURN count(r) AS count';
    $data = $this->client->run($query, array('neoid'=>(int)$id1, 'neoid2'=>(int)$id2));
    return $data;
  }


  public function fetchAltSpellingsById($id){
    $query = 'MATCH (n)-[r:same_as]-(v:Variant) WHERE id(n)= $neoid AND NOT n:priv_user RETURN v, id(v) AS variantNeoID'; 
    $data = $this->client->run($query, array('neoid'=>(int)$id)); 
    $repl = array(); 
    foreach($data as $row){
      $varid = (int)$row->get('variantNeoID');
      $var = $row->get('v'); 
      //should be //var anyway! 
      $etprops = $var['properties']; 
      $varModel = NODEMODEL['Variant']; 
      //var_dump($varModel); 
      //the Variant node is a static node type; properties should always be
      // PK and valuestring
      //variant has embedded UID property as fallback.  otherwise use the assigned property!
      $pk = helper_extractPrimary('Variant');
      foreach($etprops as $k => $v){
        if ($k === $pk){
          $pkval = $etprops[$pk];
          $pkdata = array($pk, $pkval);
          //var_dump($pk);
          //var_dump($pkval);
          $field = array('label'=> $etprops['variant'], 'primary'=> $pkdata, 'neoid'=>$varid);
          $repl[] = $field;
          //pk = getprimaries
          //valuestring => encoded in 'variant' property. 

        }
      }

    }
    return $repl; 
  }

  public function executionOfParameterizedQuery($query, $parameters){
    /**     READ OPERATIONS
     *  Executes a given query with parameter placeholders, then assigns the $parameters during execution.
     *  Returns to you all rows of the query!
     */
    $data = $this->client->run($query, $parameters); 
    return $data;
  }

  public function annotationsWithThisEntity($etid){
    /**
     * Takes a single entity ID and returns a list of internal NEO4J IDS of annotations 
     * where the given entity is linked to. 
     */
    $relatedAnnoIds = array(); 
    $query = 'MATCH (n)-[r:references]-(p) WHERE id(n) = $etid
    WITH collect(DISTINCT id(p)) AS connected_annotations
    return connected_annotations; '; 
    $result = $this->client->run($query, array('etid'=>$etid))->getResults(); 
    foreach($result as $anno){
      $relatedAnnoIds = $relatedAnnoIds + $anno->get('connected_annotations')->toArray(); 
    }
    return array_unique($relatedAnnoIds); 
  }


}

?>
