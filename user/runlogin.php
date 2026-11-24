<?php
header('Content-Type: application/json; charset=utf-8');
if (!defined('ROOT_DIR')) {
  include_once(dirname(__DIR__).'/config/config.inc.php');
}
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/client.inc.php');
include_once(ROOT_DIR.'/includes/csrf.inc.php');

$tokenManager = new CsrfTokenManager();
$submittedToken = $_POST['csrf_token'] ?? '';
if (!$tokenManager->checkToken($submittedToken)) {
  echo json_encode(array('msg' => 'Invalid session token. Please reload the page.', 'status' => 0));
  die();
}

$user = new User($client);

if (isset($_POST['mail']) AND isset($_POST['password'])){
    $repl = $user->login($_POST['mail'], $_POST['password']);
    switch ($repl[0]) {
      case 0:
        // code...  ==> email not found. or PW incorrect.
        echo json_encode(array('msg'=>'Login failed: password or e-mail incorrect.', 'status'=>0));
        break;
      case 1:
        // code...  ==> allow the session to be set.
        session_regenerate_id();  //prevent session fixation!
        echo json_encode(array('msg'=>'Login succeeded.', 'status'=>1));
        break;
      case 3:
        // code... ==> too many attempts have been made: reset required.
        echo json_encode(array('msg'=>'Login refused: Too many attempts have been made. Reset your password.', 'status'=>0));
        break;
      default:  //case2 ==> incorrect, but still within valid attemptrange.
        echo json_encode(array('msg'=>'Login failed: password or e-mail incorrect.', 'status'=>0));
        break;
    }
}

?>
