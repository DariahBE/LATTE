<?php
header('Content-Type: application/json; charset=utf-8');
include_once(dirname(__DIR__) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');
include_once(ROOT_DIR . '/includes/annotation.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');

$user = new User($client);
$user_id = $user->checkSession();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(array('error' => 'Login required'));
    exit();
}

if ($user->hasEditRights($user->myRole) < 1) {
    http_response_code(403);
    echo json_encode(array('error' => 'Insufficient permissions to reject suggestions'));
    exit();
}

$tokenManager = new CsrfTokenManager();
$csrfToken = $_POST['csrf'] ?? '';
if (!$tokenManager->checkToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(array('error' => 'Invalid session token'));
    exit();
}

$texid = isset($_POST['texid']) ? (int)$_POST['texid'] : 0;
$start = isset($_POST['start']) ? (int)$_POST['start'] : -1;
$stop = isset($_POST['stop']) ? (int)$_POST['stop'] : -1;
$surfaceText = isset($_POST['text']) ? trim((string)$_POST['text']) : '';

if ($texid <= 0 || $start < 0 || $stop < $start) {
    http_response_code(400);
    echo json_encode(array('error' => 'Invalid span boundaries or text identifier'));
    exit();
}

$annotation = new Annotation($client);
try {
    $result = $annotation->recordRejectedAnnotation($texid, $start, $stop, $surfaceText, (int)$user_id);
    if ($result['success']) {
        echo json_encode(array(
            'success' => true,
            'msg' => 'Suggestion rejected and recorded.',
            'uid' => $result['uid'],
            'start' => $start,
            'stop' => $stop
        ));
    } else {
        http_response_code(500);
        echo json_encode(array('error' => 'Failed to record rejection'));
    }
} catch (\Throwable $th) {
    http_response_code(500);
    echo json_encode(array('error' => 'Database error: ' . $th->getMessage()));
}
exit();
?>
