<?php
//session_start(); //not needed to access captche, it's already included by the csrf call
header('Content-Type: application/json; charset=utf-8');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/csrf.inc.php');
include_once(ROOT_DIR.'/includes/client.inc.php');
$csrf_name = 'registrationtoken';
$user = new User($client);
$graph new Node($client);

/**
 * CORRECT REGISTRATION PROCEDURE NEEDS TO CHECK A FEW THINGS
 * 
 * 1: WHAT IS THE REGISTRATIONPOLICY DEFINED BY THE CONSTANT
 * 2: IS THE EMAIL UNIQUE (IF POLICY === 1 || 2)
 * 3: IS THE TOKEN TIED TO THE PROVIDED EMAIL (IF POLICY === 1)
 * 4: IS THE SESSION TOKEN LEGIT
 * 5: WHAT IS THE CAPTCHA RESULT
 */

//1: check the registration policy > kill it if registrations aren't open: 
if(REGISTRATIONPOLICY === 0){die();}


//2: check uniqueness of e-mail && 
//3: does the invitetoken belong to the provided e-mail.
$required = ["captcha", "fullname", "email", "password", "password_confirmation", "token"];
$provided = array_keys($_POST);
//$provided can contain extra keys; this will not populate $missing!! So this works with the optional presence of the invitationtoken
$missing = array_diff($required, $provided);
if(!empty($missing)) {
    // form is not complete: reject 
    die("Missing parameters. ");
}else{

    if(REGISTRATIONPOLICY === 1){
        if(!(array_key_exists('invitetoken', $_POST))){
            die("Missing parameters. "); 
        }
        //check that inviteToken and e-mail adres are a valid pair: 
        $query = 'MATCH (n:priv_user) WHERE n.invitationcode = $token AND n.mail = $mail RETURN n'; 
        $parameters = array(
          'token' => $_POST['invitetoken'], 
          'mail' => $_POST['email']
        ); 
        $graph->executionOfParameterizedQuery($query, $data); 
    }

    //5 Check against captcha: 
    $captchaTruth = $_SESSION['captcha_token'];
    $captchaAnswer = $_POST['captcha']; 
    var_dump(strtolower($captchaAnswer) === $captchaTruth); 

        $repl = $user->login($_POST['mail'], $_POST['password']);
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
