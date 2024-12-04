<?php 
/* AJAX portal to add a new user when the registration policy === 1 
* ONLY open for admins
*/

 header('Content-Type: application/json; charset=utf-8');


include_once($_SERVER["DOCUMENT_ROOT"]."/config/config.inc.php");
include_once(ROOT_DIR."/includes/client.inc.php");
include_once(ROOT_DIR."/includes/user.inc.php");
include_once(ROOT_DIR."/includes/csrf.inc.php");
include_once(ROOT_DIR."/includes/mail.inc.php");

$mail_interface = new Mail(); 

$token = $_POST['token']; 
 //check token: 
$tokenManager = new CsrfTokenManager();
$tokenIsValid = $tokenManager->checkToken($token);
$tokenManager->revokeToken(); 

if(!($tokenIsValid)){
    echo json_encode(array('msg'=>'request rejected.'));
    die();
}

//check user admin level: 
$user = new User($client);
$user_id = $user->checkSession();
    //only allow admins here; No admin = kill process. 
// $adminMode = False;
if($user->myRole !== "Admin"){
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
        //TODO test this!. 
        $url = WEBURL."/user/register.php?invitetoken=$usertoken&mail=$mail";  
        $url = str_replace($url, '//', '/'); 
        $msg = "An account has been made for you for the ".PROJECTNAME." project. The last step in this proces is to verify your account using following link: "; 
        $msg .= $url; 
        $mail_interface->setMessageContent($msg, True); 
        $mail_interface->send(); 
    }else{
        echo json_encode(array('msg'=>'request failed.')); 
    }
}else{
    echo json_encode(array('msg'=>'request rejected.')); 
}

?>