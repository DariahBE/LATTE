<?php
header('Content-Type: application/json; charset=utf-8');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/client.inc.php');
$user = new User($client);


if (isset($_POST['mail']) AND isset($_POST['password'])){
    $repl = $user->login($_POST['mail'], $_POST['password']);
    //var_dump($repl);
    switch ($repl[0]) {
      case 0:
        // code...  ==> email not found. or PW incorrect.
        echo json_encode(array('msg'=>'Login failed: password or e-mail incorrect.', 'status'=>0));
        break;
      case 1:
        // code...  ==> allow the session to be set.
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
