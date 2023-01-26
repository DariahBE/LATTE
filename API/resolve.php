<?php
/*
   *this public API endpoint accepts URIS of external projects and 
   * ouputs the nodes related to it. At the same time it will output
   * all other related links stored in the database. 


  //Examples: 
      http://entitylinker.test/API/resolve.php?partneruri=https://www.suite.example/60017464
      http://entitylinker.test/API/resolve.php?partneruri=https%3A%2F%2Fwww.suite.example%2F60017464

  resolve.php will show you the entities present in the database based on multiple entrypoints;
    1) get data for an entity based on a partner's URI.

  return format of data:
    JSON
*/
/*
    ISSUE with redirects:
    1) You can not use clean URI when passing an encoded url.
      UNLESS you enable NoEncode (https://stackoverflow.com/questions/51810507/htaccess-mod-rewrite-with-encoded-url-path-not-working-while-unencoded-path-wor)

    Direct access to this page works as long as the partner URL is encoded properly: e.g.
      http://entitylinker.test/API/resolve.php?partneruri=https%3A%2F%2Fwww.google.be%2F123%2Findex.php%3Ftest%3Dok%26val%3Dyes%23ok
*/
header('Content-Type: application/json; charset=utf-8');

$partnerURI = $_GET['partneruri'];
//var_dump($partnerURI);

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/entityviews.inc.php');

$graph = new Node($client);
$core = $graph->matchSingleNode("See_Also", "partner_uri", $partnerURI);

$projectData = array(); 

if (array_key_exists('coreID', $core)){
  $coreId = $core['neoID'];
  $references = $graph->crossreferenceSilo($coreId);
  $view = new View('Silos', $references);
  $projectData['knowledgeBases'][]= array($view->datasilos); 

  //You have to extend this: Show which entity this URI is connected to: 
  $relatedEtResult = $graph->getNeighbours($coreId,'see_also'); 
  $relatedEt = array(); 
  foreach ($relatedEtResult as $key => $value) {
    // get the label of the related entity: 
    $label = $value['t']['labels'][0];
    if(!(array_key_exists($label, NODEMODEL))){
      // if the key is missing in the config file, skip the node. 
      continue;
    }
    // generate the URI of the entity: 
    $entityURI = $graph->generateURI($value['t']['id'])[0];
    //var_dump($entityURI);
    $relatedEt[$entityURI] = [];// = array(); 
    //var_dump($relatedEt);
    // With the label > get the public model: 
    $model = NODEMODEL[$label];
    // get the associated properties: 
    $properties = $value['t']['properties']; 
    //write a translator for each propertykey so that the public key is displayed in the API!
    $relatedEt[$entityURI][] = $label;
    foreach($properties as $subkey => $subvalue){
      if(array_key_exists($subkey, $model)){
        //var_dump($subkey, $subvalue);
        $relatedEt[$entityURI][$subkey] = $subvalue;
      }
    } 
  }
  $projectData['relatedEntities'][] = $relatedEt;
  echo json_encode($projectData);
  //die('Reached Doc end. ');
}else{
  echo json_encode(array('Message'=>'No results'));
}


?>
