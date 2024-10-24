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
$embeddedPropsFull = $graph->fetchEtById($id);
$extendedProps = $embeddedPropsFull[0]; 
$embeddedProps = $embeddedPropsFull[1]; 

$relatedVariants = $graph->fetchAltSpellingsById($id);

$stableLink = $graph->generateURI($id); 

$reformattedVariants = array(); 
foreach ($relatedVariants as $variant) {
    $varid = $variant['neoid']; 
    $internalObject = array(
        "variant" => array(
            "value" => $variant['label'], 
            "DOMString" => "Label",
            "vartype" => "string"
        ),
        "remark"=>array(),
        "variantOfEntity" => array($id)
    ); 
    $one_variant = array($varid, "Variant", $internalObject, null); 
    $reformattedVariants[] = $one_variant;
}


$replData = array(
    //'props'=> $embeddedProps[0], 
    'preprocessed_props'=> $embeddedProps, 
    'props'=> $extendedProps,
    'variantSpellings' => $relatedVariants, 
    'variantsReformat' => $reformattedVariants, 
    'stable' => $stableLink
);

if(isset($_GET['extended']) && ($_GET['extended']==1)){
    //add the label of the node
    $label = $graph->fetchLabelById($id);
    $replData['extra']['label'] = $label;

    //add the relational model
    $replData['extra']['model']  = $graph->fetchModelByLabel($label);
}

echo json_encode($replData ); 



?>