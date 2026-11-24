<?php 
/* AJAX portal to add a new user when the registration policy === 1 
* ONLY open for admins
*/

 header('Content-Type: application/json; charset=utf-8');


include_once(dirname(__DIR__, 2) . "/config/config.inc.php");
include_once(ROOT_DIR . "/includes/client.inc.php");
include_once(ROOT_DIR . "/includes/user.inc.php");
include_once(ROOT_DIR . "/includes/csrf.inc.php");
include_once(ROOT_DIR . "/includes/mail.inc.php");

$mail_interface = new Mail(); 

$token = isset($_POST['token']) ? $_POST['token'] : ''; 
// Check token: 
$tokenManager = new CsrfTokenManager();
$tokenIsValid = $tokenManager->checkToken($token);
$tokenManager->revokeToken(); 

if(!($tokenIsValid)){
    echo json_encode(array('msg'=>'request rejected.'));
    die();
}

// Check user admin level: 
$user = new User($client);
$user_id = $user->checkSession();
if (!$user->isAdmin()) {
    header("HTTP/1.0 403 Forbidden");
    die("Insufficient rights, forbidden access");
}

//check if the registration policy is set to 1 or 2. 
if(in_array(REGISTRATIONPOLICY, array(1,2))){
    $mail = $_POST['email']; 
    $role = $_POST['role'];
    $name = $_POST['name'];
    $backend_repl = $user->createUser($mail, $name, $role, NULL, True, 0); 
    if($backend_repl[0] == 'ok'){
        echo json_encode(array('msg'=>'request completed.', 'success'=>true)); 
        $usertoken = $user->setToken; 
        $mail_interface->setSubjectOfMail('Your '.PROJECTNAME.' invitecode.'); 
        $mail_interface->setRecipient($mail);
        // Build clean invitation url.
        $url = rtrim(WEBURL, '/') . "/user/register.php?invitetoken=" . urlencode($usertoken) . "&mail=" . urlencode($mail);
        $msg = "<p>An account has been made for you for the <a href='".WEBURL."' target='_blank'>".PROJECTNAME."</a> project. The last step in this proces is to verify your account using following link: </p>"; 
        $msg .= "<br><a href= '$url' target='_blank'>$url</a>"; 
        $mail_interface->setMessageContent($msg, True); 
        $mail_interface->send(); 
    }else{
        echo json_encode(array('msg'=>'request failed.')); 
    }
}else{
    echo json_encode(array('msg'=>'request rejected.')); 
}

?>