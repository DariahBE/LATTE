<?php
/*
  resolve.php will show you the entities present in the database based on multiple entrypoints;
    1) get data for an entity based on a partner's URI.
    2) get entities that match a query based on given string value.
    3) get a single entity based on the unique identifier.

  return format of data:
    JSON

  As a response: give an option of modes:
    1) Consise: API returns the entity/ies it thinks are connected to the entrypoint.
    2) extended: API returns the entity/ies, links, mentions and text nodes that are connected to the entrypoint.
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

if (array_key_exists('coreID', $core)){
  $coreId = $core['coreID'];
  //BUG: crossreferenceSilo method is not capable of returning both the original silo as all siloNodes that are connected to the common entity
  $references = $graph->crossreferenceSilo($coreId);
  $view = new View('Silos', $references);
  echo json_encode(array($view->datasilos));
  die();
}else{
  echo json_encode(array('Message'=>'No results'));
}


?>
