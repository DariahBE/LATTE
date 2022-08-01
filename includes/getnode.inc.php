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
  $label = $nodeIn['labels'][0];    //Person or Place?
  $data = array();
  if($label == "Person"){
    $data['sex'] = valueExtract($nodeIn['properties'], 'sex');
    $data['min_date'] = valueExtract($nodeIn['properties'], 'mindate');
    $data['max_date'] = valueExtract($nodeIn['properties'], 'maxdate');
    $data['uuid']=valueExtract($nodeIn['properties'], 'uid');
  }else if($label == "Place"){
    $data['label'] = "PLACE";
    $data['name'] = valueExtract($nodeIn['properties'], 'name');
    $data['region'] = valueExtract($nodeIn['properties'], 'region');
    $data['uuid']=valueExtract($nodeIn['properties'], 'uid');
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
      if(boolval(NODEKEYSTRANSLATIONS) AND array_key_exists($key, NODEKEYSTRANSLATIONS[$ofLabel])){
        $keyTranslation = NODEKEYSTRANSLATIONS[$ofLabel][$key];
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
    //$result = $this->client->run("MATCH (node:".$type."{".$key.":".$value."}) RETURN node, id(node) AS ID LIMIT 1");
    $result = $this->client->run('MATCH (node:'.$type.'{'.$key.': $nodeval}) RETURN node, id(node) AS ID LIMIT 1', ['nodeval'=>$value]);
    // A row is a \Laudis\Neo4j\Types\CypherMap
    $node = false;
    foreach ($result as $record) {
        // Returns a \Laudis\Neo4j\Types\Node
        $node = array(
          'data'=>$record->get('node'),
          'coreID'=>$record->get('ID'),
          'model'=>NODES[$type]
        );
    }
    return $node;
  }

  function getNeighbours($id){
    //use the built in node ID (not the UUID) to extract neighbouring nodes from a core node.
    //query is undirected!!
    $result = $this->client->run('MATCH (n)-[r]-(t) WHERE id(n) = $providedID RETURN n,r,t', ['providedID'=>$id]);
    return $result;
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



  function getEntities($entityType, $entityValue, $caseSensitive=false, $limit=100, $offset=0){
    if($caseSensitive){
      //case sensitive ==> Very Fast:
      if(boolval($entityType)){
        $entityType = ':'.$entityType;
      }
      $cypherQuery = '
          OPTIONAL MATCH (p'.$entityType.' {name:$nameValue1})
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
          OPTIONAL MATCH (p'.$entityType.') WHERE p.name =~ $nameValue1
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
    $resultRaw = $this->client->run($cypherQuery,$placeholders);
    $formattedResults = array(
      'nodes'=>array(),
      'edges'=>array(),
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
          $variant = process_variant($result['v']);
          if(!(in_array($variant[0], $registeredNodes))){
            $registeredNodes[] = $variant[0];
            $formattedResults['nodes'][] = $variant;
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
    //return $resultRaw;
    //echo json_encode($resultRaw['result']);

    $formattedResults['meta']['entities'] = $entities;

    return $formattedResults;
    //$result should be processed in a NODE ; EDGE list
  }

  function createSingleNode($type, $argumentList){

  }

  function generateURIBox($type, $id){

  }


}

?>
