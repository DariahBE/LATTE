<?php
header('Content-Type: application/json; charset=utf-8');

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/csrf.inc.php');
include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php');
include_once(ROOT_DIR.'/includes/customapi.inc.php');
include_once(ROOT_DIR.'\config\CAPI_config.php');

//die('okido'); 

$api_profile = $_GET['profile']; 
if(isset($_GET['secret'])){
    $profile_secret = $_GET['secret']; 
}else{
    $profile_secret = false; 
}
$request_type = $_GET['type']; 

$api = new API($api_settings); 
$node = new Node($client); 


//var_dump($node); 
//die(); 

$api->checkrequestsecret($api_profile, $profile_secret); 
$api->readrequests($request_type); 
$api->restrictNodeByParameters(); 

$api->makeCypherStatement(); 

$data = $node->executePremadeParameterizedQuery(
    $api->getQuery(),
    $api->getParams()
); 

//var_dump($api->getQuery()); 


// $echodata = array(
//     'results' => array(), 
// ); 

// $do_vars = $api->vars_required(); 
// $do_uri = $api->uri_required(); 


// the extra behaviour: 
echo json_encode($api->format_API_response($data, $node));

//  1) is the stable URI required to be returned?
// $record = 0; 
// foreach ($data as $key => $noderecord) {
//     $rowResult = array(); 
//     $neoid = (int)$noderecord['n']['id']; 
//     if($do_uri){
//         $rowResult['URI'] = $node->generateURI($neoid); 
//         //var_dump(); 
//     }
//     if($do_vars){
//         $rowResult['URI'][]=  $node->findVariants($neoid); 

//         // var_dump($node->findVariants($neoid)); 
//         //($neoid); 
//     }
//     $echodata['echodata'][$record] = $rowResult; 
//     $record = $record + 1; 
// }

// var_dump($echodata); 