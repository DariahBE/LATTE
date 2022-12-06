<?php
//include_once("../config/config.inc.php");
include_once(ROOT_DIR."/includes/client.inc.php");

/*// IDEA:
  use GET statement to collect a tripple: Nodetype, NodeProperty, NodeValue.
  Build a query of that which results in:
  MATCH (n:<<Nodetype>> {<<NodeProperty>>:<<NodeValue>>}) RETURN n

DOCS:
// https://neo4j.com/developer-blog/connect-to-neo4j-with-php/
// https://github.com/neo4j-php/neo4j-php-client
*/

/*
  this is a program wide default; if there's no key defined to be the primary key of a node;
  the app is going to fall back to the uid-property present in all nodes. That key is autoamtically
  generated and of the UUIDV4-type.
*/

function helper_extractPrimary($keyName){
  //return PRIMARIES[$keyName] ? PRIMARIES[$keyName] : 'uid';   //ternary operator not working as it should
  if(array_key_exists($keyName, PRIMARIES)){
    return PRIMARIES[$keyName];
  }else{
    return 'uid';
  }
}

function helper_parseEntityStyle(){
  var_dump(CORENODES);
  foreach (CORENODES as $key => $value) {
    echo '.'.$key.'{background-color:'.$value.';}';
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

function process_relationshipNodes($nodeIn){
  //data is controlled: only a single node.
  // BUG:
  /*
      Flawed datamodel: code should use dynamic properties as they are set by
      the config.inc.php file. Do not rely on static hardcoded models!
      SAME bugfix as in ghet process_entityNodes method!
  */
  $id = $nodeIn['id'];
  $label = $nodeIn['labels'][0];
  $data = array(
    'label'=>'LINK',
    'name'=>$nodeIn['properties']['partner'],
    'id'=>$nodeIn['properties']['partner_id'],
    'uri'=>$nodeIn['properties']['partner_uri'],
  );
  return array($id, $label, $data);
}

function process_variant($nodeIn){
  $id = $nodeIn['id'];
  $label = $nodeIn['labels'][0];
  $variant = $nodeIn['properties']['variant'];
  $data = array(
    'label'=>'VARIANT',
    'name'=>$variant,
    'uuid'=>$nodeIn['properties']['uid']
  );
  return array($id, $label, $data);
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
  $label = $nodeIn['labels'][0];
  $data = array();
  $model = NODEMODEL[$label];
  foreach ($model as $key => $value) {
    $data[$key] = array(
      'value' => valueExtract($nodeIn['properties'], $key),
      'DOMString' => $value[0]
    );
  }
  return(array($id, $label, $data));
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
  private $client;
  function __construct($client)  {
    $this->client = $client;
  }

  function getDistinctLabels(){
    $result = $this->client->run('MATCH (n) RETURN DISTINCT labels(n) AS label');
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

  function getDistinctProperties($ofLabel){
    $label = $ofLabel;//needs sanitation!!
    $result = $this->client->run('MATCH(n:'.$label.') UNWIND keys(n) AS keys RETURN DISTINCT keys');
    $data = array();
    foreach($result as $record){
      $key = $record['keys'];
      if(array_key_exists($key, NODEMODEL[$ofLabel])){
        $keyTranslation = NODEMODEL[$ofLabel][$key][0];
      }else{
        $keyTranslation = false;
      }
      array_push($data, array($key, $keyTranslation));
    }
    return $data;
  }

  function countNodesByLabel(){
    $result = $this->client->run('MATCH (n) RETURN DISTINCT count(labels(n)) as f, labels(n) as label ORDER BY f DESC;');
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
    $result = $this->client->run("MATCH ()-[r]->() RETURN TYPE(r) AS label, COUNT(r) AS f ORDER BY f DESC;");
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

  function matchSingleNode($type, $key, $value){
    if (is_numeric($value)){
      $value = $value + 0;   //can be float too . adding +0 will allow php to automatically set the correct type.
    }
    if(boolval($type)){
      $type = ':'.$type;
    }
    //$result = $this->client->run("MATCH (node:".$type."{".$key.":".$value."}) RETURN node, id(node) AS ID LIMIT 1");
    $result = $this->client->run('MATCH (node'.$type.'{'.$key.': $nodeval}) RETURN node, id(node) AS ID LIMIT 1', ['nodeval'=>$value]);
    // A row is a \Laudis\Neo4j\Types\CypherMap
    $node = false;
    //var_dump($result);
    foreach ($result as $record) {
        // Returns a \Laudis\Neo4j\Types\Node
        //$core = PRIMARIES[$type];
        $core = helper_extractPrimary($type);
        $node = array(
          //get the name of the text PK:
          //'coreID'=>$record->get($core),
          'coreID'=>$record['node']->getProperty($core),
          'model'=>array_key_exists($type, NODES) ? NODES[$type] : null
        );
    }
    if(boolval($result)){
      $node['data'][]=$result;
    }
    return $node;
  }

  function getNeighbours($id){
    //use the built in node ID (not the UUID) to extract neighbouring nodes from a core node.
    //query is undirected!!
    // UPDATED FOR PATCH:
    /*
      - do not return priv_user nodes
      - patch for nodes that have no neighbours: (n)-[r]-(t) is an exact pattern match
          ==> FIX: use optional match [r]-(t) for exact match (n)
    */
    $result = $this->client->run('
    MATCH (n) WHERE id(n) = $providedID AND NOT n:priv_user
    OPTIONAL MATCH (n)-[r]-(t)
    RETURN n,r,t', ['providedID'=>(int)$id]);
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
    $query = "MATCH (n)<-[q:references]-(a:Annotation{$constraintOnAnnotationLevel})<-[s:contains]-(t:Text) where id(n)=$nodeId return t";
    $result = $this->client->run($query, [$nodeId]);
    return $result;
  }



  function getEntities($entityType, $entityValue, $caseSensitive=false, $limit=100, $offset=0){
    //case sensitive ==> Very Fast:
    if(boolval($entityType)){
      $entityType = ':'.$entityType;
    }
    if($caseSensitive){
      $cypherQuery = '
          OPTIONAL MATCH (p'.$entityType.' {label:$nameValue1})
          OPTIONAL MATCH (v:Variant {variant:$nameValue2})-[r1:same_as]-(q'.$entityType.')
          OPTIONAL MATCH (p)-[r2:see_also]->(i:See_Also)
          OPTIONAL MATCH (q)-[r3:see_also]->(j:See_Also)
          return p,v,q,r1,r2,r3,i,j
          limit 10000';
      $placeholders = array(
          'nameValue1'=>$entityValue,
          'nameValue2'=>$entityValue
      );
    }else{
      //case Insensitive ==> using regex
      $entityValueCleaned = ignoreRegex($entityValue);
      $cypherQuery = '
          OPTIONAL MATCH (p'.$entityType.') WHERE p.label =~ $nameValue1
          OPTIONAL MATCH (v:Variant)-[r1:same_as]-(q'.$entityType.') WHERE v.variant =~ $nameValue2
          OPTIONAL MATCH (p)-[r2:see_also]->(i:See_Also)
          OPTIONAL MATCH (q)-[r3:see_also]->(j:See_Also)
          return p,v,q,r1,r2,r3,i,j
          limit 10000';
      $placeholders = array(
          'nameValue1'=>'(?i)'.$entityValueCleaned,
          'nameValue2'=>'(?i)'.$entityValueCleaned
      );
    }
    //var_dump($cypherQuery);
    $resultRaw = $this->client->run($cypherQuery,$placeholders);
    $formattedResults = array(
      'nodes'=>array(),
      'edges'=>array(),
      'labelvariants'=>array(),
      'meta'=>array('entities'=>0)
    );
    $registeredNodes = array();
    $registeredEdges = array();
    $entities = 0;
    foreach ($resultRaw as $result){
      //nodes:
        //      $result =>p, v = entity
        //      $result =>q = entity: link to external project
        //      $result =>i,j
        if(!(is_null($result['i']))){
          $iPartner = process_relationshipNodes($result['i']);
          if(!(in_array($iPartner[0], $registeredNodes))){
            $registeredNodes[] = $iPartner[0];
            $formattedResults['nodes'][] = $iPartner;
          }
        }
        if(!(is_null($result['j']))){
          $jPartner = process_relationshipNodes($result['j']);
          if(!(in_array($jPartner[0], $registeredNodes))){
            $registeredNodes[] = $jPartner[0];
            $formattedResults['nodes'][] = $jPartner;
          }
        }
        if(!(is_null($result['v']))){
          $variant = process_entityNodes($result['v']);
          if(!(in_array($variant[0], $registeredNodes))){
            $registeredNodes[] = $variant[0];
            $formattedResults['labelvariants'][] = $variant;
          }
        }
        if(!(is_null($result['p']))){
          $entity = process_entityNodes($result['p']);
          if(!(in_array($entity[0], $registeredNodes))){
            $registeredNodes[] = $entity[0];
            $entities+=1;
            $formattedResults['nodes'][] = $entity;
          }
        }
        if(!(is_null($result['q']))){
          $entity = process_entityNodes($result['q']);
          if(!(in_array($entity[0], $registeredNodes))){
            $registeredNodes[] = $entity[0];
            $entities+=1;
            $formattedResults['nodes'][] = $entity;
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
      if(!(is_null($result['r2']))){
        $edge = process_edge($result['r2']);
        if(!(in_array($edge['edgeID'], $registeredEdges))){
          $registeredEdges[] = $edge['edgeID'];
          $formattedResults['edges'][] = $edge;
        }
      }
      if(!(is_null($result['r3']))){
        $edge = process_edge($result['r3']);
        if(!(in_array($edge['edgeID'], $registeredEdges))){
          $registeredEdges[] = $edge['edgeID'];
          $formattedResults['edges'][] = $edge;
        }
      }
    }
    $formattedResults['meta']['entities'] = $entities;
    return $formattedResults;
    //$result should be processed in a NODE ; EDGE list
  }

  function createSingleNode($type, $argumentList){

  }

  function generateURI($id){
    //finds the node by it's NEO-id, returns a stable identifier;
    $query = 'MATCH (n) WHERE id(n) = $providedID return n';
    $result = $this->client->run($query, ['providedID'=>(int)$id]); 
    //if the resultset has at least one row; get the row ==> the first row is also the only row!!
    if(boolval($result->count())){
      $result = $result->first()->get('n');
      //var_dump($result);
      $label = $result['labels'][0]; 
      $model = NODEMODEL[$label];
      //look for access to the primary key data. What key in the returned NODE type is the Primary Key.
      //var_dump(array_column($model, 2));
      $found_key = array_search(true, array_column($model, 2), true);
      //var_dump($found_key); // can be 0, but 0 does not equal false. Do a type sensitive comparison: 
      if ($found_key === false){
        //there's no primary key defined: so fallback to the default uid property: 
        $primaryKeyName = 'uid';
      }else{
        $keys = array_keys($model);
        $primaryKeyName = $keys[$found_key];
      }
      //var_dump($result['properties'][$primaryKeyName]);
      $URLString = trim(WEBURL, '/').'/'.$label.'/'.$result['properties'][$primaryKeyName];
      $result = array($URLString); 
      //die();
    }else{
      //if the id requested is not in the DB: return an empty array. 
      $result = array();
    }
    //var_dump($result['labels'][0]);
    return $result; 
  }



}

?>
