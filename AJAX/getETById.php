<?php
header('Content-Type: application/json; charset=utf-8');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');

//only open for registered users
$user = new User($client);
$user->checkAccess(False);  //automatically go to session checking.

//fetches an entity by ID and returns the properties and alternate spellings. 
$response = array(); 
$id = (int)$_GET['id']; 

//make a graph object ==> use appropriate method. 
$graph = new Node($client);
$embeddedProps = $graph->fetchEtById($id);
$relatedVariants = $graph->fetchAltSpellingsById($id);


echo json_encode(
    array(
        'props'=> $embeddedProps, 
        'variantSpellings' => $relatedVariants
    )
    ); 



?>