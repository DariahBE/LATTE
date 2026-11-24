<?php
header('Content-Type: application/json; charset=utf-8');

include_once(dirname(__DIR__, 2) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');
include_once(ROOT_DIR . '/includes/nodes_extend_cud.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');

$user = new User($client);
$user_id = $user->checkSession();
if(!$user_id){
    $redir = '?redir=' . urlencode(BASE_URL . '/create.php');
    header('Location: ' . BASE_URL . '/user/login.php' . $redir);
    exit;
}

$submitdata = $_POST;
$token = $submitdata['token']; 
$form = $submitdata['formdata']; 
// Force capitalization of node names (e.g. Text); and force lowercasing of node properties)
$entity_type = ucfirst(preg_replace('/\s+/', '', (string)($submitdata['etype'] ?? ''))); 
//check if the token is valid; if so: use it once and invallidate it. 
$tokenManager = new CsrfTokenManager();
$node = new CUDNode($client);
//check the token ==> die if invalid
if(!($tokenManager->checkToken($token))){
    die(json_encode(array('error'=>'Invalid request.'))); 
}

if(!(array_key_exists($entity_type,NODEMODEL))){
    die(json_encode(array('error'=>'Invalid request.'))); 
}

if(!array_key_exists('formdata', $submitdata)){
    die(json_encode(array('error'=>'Invalid request.'))); 
}

// if token is accepted and ETType is valid: insert it as ETTYpe with $form as properties: 
$cleanFormData = [];
foreach ($submitdata['formdata'] as $key => $value) {
    // Force capitalization of node names (e.g. Text); and force lowercasing of node properties)
    $cleanKey = strtolower(preg_replace('/\s+/', '', (string)$key));
    $cleanFormData[$cleanKey] = $value;
}
$submitdata['formdata'] = $cleanFormData;

foreach ($submitdata['formdata'] as $key => $value) {
    if(!(array_key_exists($key, NODEMODEL[$entity_type]))){
        die(json_encode(array('error'=>'Invalid request.')));
    }
}


//foreach loop has passed ==> new to be created node is definitely valid: SO create it. 
foreach ($submitdata['formdata'] as $key => $value) {
    $propMeta = NODEMODEL[$entity_type][$key];
    if (!empty($propMeta[2]) && !is_bool($value) && trim((string)$value) === '') {
        http_response_code(400);
        die(json_encode(array('error' => 'Required property "' . $propMeta[0] . '" cannot be empty.')));
    }
}

$node->startTransaction();
try {
    $graphResult = $node->createNewNode($entity_type, $submitdata['formdata'], true);       //returns ID() of created node
    //connect the user who created the node to $graphResult: 
    $connection = $node->connectCreatorToNode($_SESSION['neoid'], $graphResult); 
    $node->commitTransaction(); 
} catch (\Throwable $th) {
    $node->rollbackTransaction();
    http_response_code(500);
    echo json_encode(array('stable' => false, 'success' => false, 'error' => $th->getMessage()));
    exit;
}

if (boolval($graphResult)){
    //get the stable identifier of the element. 
    $uri = $node->generateURI($graphResult); 
    echo json_encode(array('stable'=> array($uri), 'uri' => $uri, 'success' => true)); 
}else{
    echo json_encode(array('stable'=> false, 'success' => false)); 
}
?>