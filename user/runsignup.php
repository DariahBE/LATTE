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
$graph = new Node($client);



/**
 *          WARNING: 
 *  some actions go to the database and could slow down the process
 *  this slowdown is significant enough to allow for timing attacks
 *  to prevent these, force the script into a 'slow' calculation. 
 *  we don't want to tell which generic 'invalid request' message
 *  is being returned.
 */
$hashedPassword = password_hash('prevent', PASSWORD_DEFAULT);
password_verify('timingattack', $hashedPassword); 

/**
 * CORRECT REGISTRATION PROCEDURE NEEDS TO CHECK A FEW THINGS
 * 
 * 1: WHAT IS THE REGISTRATIONPOLICY DEFINED BY THE CONSTANT
 *  // proceed to check POST completeness
 * 2: IS THE EMAIL UNIQUE (IF POLICY === 2) ==> If policy === 1 admins have to register the user. Check should happen there. 
 *  // proceed to check password complexity and validity (is the repeat password == first entry)? 
 * 3: IS THE TOKEN TIED TO THE PROVIDED EMAIL (IF POLICY === 1)
 * 4: IS THE SESSION TOKEN LEGIT
 * 5: WHAT IS THE CAPTCHA RESULT
 */
 
//1: check the registration policy > kill it if registrations aren't open: 
if(REGISTRATIONPOLICY === 0){
    //Kill request: you're not allowed to continue!
    echo json_encode(array('status'=>0, 'msg' => 'Invalid request'));
    die();
}

$required = ["captcha", "fullname", "email", "password", "password_confirmation", "token"];
//$provided can contain extra keys; this will not populate $missing!! So this works with the optional presence of the invitationtoken
$provided = array_keys($_POST);
$missing = array_diff($required, $provided);



if(!empty($missing)) {
    // form is not complete: reject 
    echo json_encode(array('msg' => 'Invalid request'));
    die();
}else{


    //2: check email uniqueness ONLY if registrationpolicy === 2: Registration is open to all. 
    if(REGISTRATIONPOLICY === 2){
        if(!($user->checkUniqueness($_POST['email']))){
            echo json_encode(array('msg' => 'The provided e-mail account is already in use.'));
            die();
        }
    }
    //registration is open only to invited users. 
    if(REGISTRATIONPOLICY === 1){
        if(!(array_key_exists('invitetoken', $_POST))){
            echo json_encode(array('msg' => 'Invalid invitecode'));
            die();
        }

        //3: does the invitetoken belong to the provided e-mail.
        //check that inviteToken and e-mail addres are a valid pair: 
        $result = $user->checkTokenRequest($_POST['email'], $_POST['invitetoken']); 
        if(count($result) === 0){
            echo json_encode(array('msg' => 'Invalid invitecode'));
            die();
        }else{
            $existingNodeId = $data[0]['n']['id'];
        }
        // $parameters = array(
        //   'token' => $_POST['invitetoken'], 
        //   'mail' => $_POST['email']
        // ); 
        // $data = $graph->executionOfParameterizedQuery($query, $parameters); 
        // if($data->count() === 1){
        //     $existingNodeId = $data[0]['n']['id'];
        // }else{
        //     echo json_encode(array('msg' => 'Invalid invitecode'));
        //     die();
        // }
    }
    //var_dump($existingNodeId); 
    
    // check password complexity: 


    //chack password-repeat correctness:
    $passOne = $_POST['password'];
    $passTwo = $_POST['password_confirmation'];
    if($passOne !== $passTwo){
        echo json_encode(array('msg' => 'Repeat password doesn\'t match the original password.'));
        die();
    }
    //see if password policy is met: 
    if (!($user->passwordPolicyCheck($passOne))){
        die("Error: Password does not meet requirements. A minimum of 8 characters is needed and at least two of the following must be true: Uppercase, Lowercase, Special symbols, numbers");
    }


    //4 Check the session token: 
    $sessionTruth = $_SESSION['registrationtoken'];
    $sessionAnswer = $_POST['token'];
    if(hash_equals($sessionTruth, $sessionAnswer)){
        $validSession = True;
    }else{
        echo json_encode(array('status'=>0, 'msg' => 'Invalid request'));
        die();
    }

    //5 Check against captcha: 
    $captchaTruth = $_SESSION['captcha_token'];
    $captchaAnswer = $_POST['captcha']; 
    if(strtolower($captchaAnswer) === strtolower($captchaTruth)){
        $captchaCorrect = True;
    }else{
        echo json_encode(array('status'=>0, 'msg' => 'Invalid Captcha'));
        die();
    }


    //6: all signup checks have passed, insert the user in the DB: 
    //hash the password here!!
    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
    if (REGISTRATIONPOLICY === 2){
        //POLICY2: open registrations 
        $createData = array(
            $_POST['email'], 
            $_POST['fullname'], 
            'contributor',
            $hashedPassword,
            False, 
            1,
            False
        );
    }else if(REGISTRATIONPOLICY === 1){
        //POLICY 1 (invite based registrations)
        //      set the $confirmation_phase flag to skip usermail check.
        $createData = array(
            $_POST['email'], 
            $_POST['fullname'], 
            'contributor',
            $hashedPassword,
            False, 
            1, 
            True
        );
    }
    $backend_repl = $user->createUser(...$createData); 
    if($backend_repl[0] == 'ok'){
        echo json_encode(array('status'=>1, 'msg'=> 'user registration completed.'));
    }else{

    }
}


?>
