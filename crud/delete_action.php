<?php
/**
 * Deletes a node from the database and returns the user to the referring page.
 */
include_once(dirname(__DIR__) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');
include_once(ROOT_DIR . '/includes/nodes_extend_cud.inc.php');

// Determine return destination safely.
$fallbackUrl = (defined('BASE_URL') && BASE_URL !== '') ? BASE_URL : '/';
$redirectUrl = $fallbackUrl;

$rawReferrer = null;
if (!empty($_POST['referrer'])) {
    $rawReferrer = $_POST['referrer'];
} elseif (!empty($_COOKIE['referrer'])) {
    $rawReferrer = $_COOKIE['referrer'];
} elseif (!empty($_SERVER['HTTP_REFERER'])) {
    $rawReferrer = $_SERVER['HTTP_REFERER'];
}

if ($rawReferrer) {
    $refHost = parse_url($rawReferrer, PHP_URL_HOST);
    $serverHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $serverHostName = explode(':', $serverHost)[0];
    
    if (!$refHost) {
        if (str_starts_with($rawReferrer, '/')) {
            $redirectUrl = $rawReferrer;
        }
    } elseif ($refHost === $serverHostName || $refHost === 'localhost' || $refHost === '127.0.0.1') {
        $redirectUrl = $rawReferrer;
    }
}

// Validate required post inputs.
if (!isset($_POST['ID']) || !isset($_POST['token']) || !isset($_POST['confirmbox'])) {
    header("Location: " . $redirectUrl);
    exit;
}

$confirmed = $_POST['confirmbox']; 
if ($confirmed !== 'userconfirmation') {
    header("Location: " . $redirectUrl);
    exit;
}

$id = (int)$_POST['ID'];
$token = $_POST['token']; 

$tokenManager = new CsrfTokenManager(); 
$validToken = $tokenManager->checkToken($token); 
if (!$validToken) {
    header("Location: " . $redirectUrl);
    exit;
} else {
    $tokenManager->revokeToken();
}

// Check if delete rights are granted.
$user = new User($client); 
$user->checkSession();
$allowedDelete = $user->hasEditRights($user->myRole);
if ($allowedDelete < 3) {
    header("Location: " . $redirectUrl);
    exit;
}

$crudNode = new CUDNode($client);
$crudNode->startTransaction(); 

// Fetch target node label.
$egoLabel = $crudNode->fetchLabelById($id); 
if ($egoLabel === null) {
    $crudNode->rollbackTransaction();
    header("Location: " . $redirectUrl);
    exit;
}

$delete = []; 

// Determine deletion cascade based on node type.
if ($egoLabel == TEXNODE) {
    $ntype = 'text';    
    $annos = $crudNode->distinctAnnotationsInText($id);
    $ets = $crudNode->find_isolated_entities($annos);
    $delete['text'] = array($id); 
    $delete['annotations'] = $annos;
    $delete['entities'] = $ets; 
    $floating_ets = $crudNode->find_floating_entity_connections($ets);
    $delete['et_floaters'] = $floating_ets;  
    $delete['see_alsos'] = $crudNode->find_floats_over_connection($floating_ets, 'see_also');
} elseif (($egoLabel == ANNONODE) || ($egoLabel === 'Annotation_auto')) {
    $ntype = 'anno'; 
    $ets = $crudNode->find_isolated_entities(array($id));
    $delete['text'] = array();
    $delete['annotations'] = array($id); 
    $delete['entities'] = $ets;
    $floating_ets = $crudNode->find_floating_entity_connections($ets);
    $delete['et_floaters'] = $floating_ets;     
    $delete['see_alsos'] = $crudNode->find_floats_over_connection($floating_ets, 'see_also');
} elseif (array_key_exists($egoLabel, CORENODES)) {
    $ntype = 'entity'; 
    $delete['text'] = array();
    $delete['see_alsos'] = array();
    $delete['annotations'] = $crudNode->annotationsWithThisEntity($id); 
    $delete['entities'] = array($id); 
    $floating_ets = $crudNode->find_floating_entity_connections(array($id));
    $delete['et_floaters'] = $floating_ets;     
    $delete['see_alsos'] = $crudNode->find_floats_over_connection($floating_ets, 'see_also');
} else {
    $crudNode->rollbackTransaction();
    header("Location: " . $redirectUrl);
    exit;
}

try {
    $deleteOrder = array('text', 'see_alsos', 'annotations', 'entities', 'et_floaters'); 
    foreach ($deleteOrder as $deletePart) {
        $crudNode->bulk_delete_by_ids($delete[$deletePart]);
    }
} catch (\Throwable $th) {
    $crudNode->rollbackTransaction();
    error_log('Error, delete could not be committed: ' . $th->getMessage());
    header("Location: " . $redirectUrl);
    exit;
}

$crudNode->commitTransaction();

// Clear referrer cookie.
if (isset($_COOKIE['referrer'])) {
    setcookie('referrer', '', time() - 3600, '/');
}

// Redirect back to the referring page.
header("Location: " . $redirectUrl);
exit;
?>
