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
$hashedPassword = password_hash('prevent', PASSWORD_BCRYPT);
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
    echo json_encode(array('msg' => 'Invalid request'));
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


    //2: check email uniqueness ONLY if registrationpolicy === 2:
    if(REGISTRATIONPOLICY === 2){
        if(!($user->checkUniqueness($_POST['email']))){
            echo json_encode(array('msg' => 'The provided e-mail account is already in use.'));
            die();
        }
    }
    
    if(REGISTRATIONPOLICY === 1){
        if(!(array_key_exists('invitetoken', $_POST))){
            echo json_encode(array('msg' => 'Invalid invitecode'));
            die();
        }

        //3: does the invitetoken belong to the provided e-mail.
        //check that inviteToken and e-mail addres are a valid pair: 
        $query = 'MATCH (n:priv_user) WHERE n.invitationcode = $token AND n.mail = $mail RETURN n'; 
        $parameters = array(
          'token' => $_POST['invitetoken'], 
          'mail' => $_POST['email']
        ); 
        $data = $graph->executionOfParameterizedQuery($query, $parameters); 
        if($data->count() === 1){
            $existingNodeId = $data[0]['n']['id'];
        }else{
            echo json_encode(array('msg' => 'Invalid invitecode'));
            die();
        }
    }
    //var_dump($existingNodeId); 
    
    // check password complexity: 


    //chack password-repeat correctness:
    $passOne = $_POST['password'];
    $passTwo = $_POST['password_confirmation'];
    if($passOne !== $passTwo){
        echo json_encode(array('msg' => 'Repeat password does\'t match the original password.'));
        die();
    }


    //4 Check the session token: 
    $sessionTruth = $_SESSION['registrationtoken'];
    $sessionAnswer = $_POST['token'];
    if(hash_equals($sessionTruth, $sessionAnswer)){
        $validSession = True;
    }else{
        echo json_encode(array('msg' => 'Invalid request'));
        die();
    }

    //5 Check against captcha: 
    $captchaTruth = $_SESSION['captcha_token'];
    $captchaAnswer = $_POST['captcha']; 
    if(strtolower($captchaAnswer) === strtolower($captchaTruth)){
        $captchaCorrect = True;
    }else{
        echo json_encode(array('msg' => 'Invalid Captcha'));
        die();
    }


    $newUserId = (int)$user->autoIncrementControllableUserId(); 

    // all checks have passed: 
    // perform registration using the User class


    /*
    $hashedUserPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);
    if(REGISTRATIONPOLICY === 1){
        //update the record: 
        $query = 'MATCH (n:priv_user) WHERE id(n) = $neoidKnown SET n.logon_attempts = 0, n.name = $setName, n.userid = $userid, n.wd_property_preferences	= "", n.wd_titlestring_preferences	= "", n.wd_wikilink_preferences	= "", n.password = $passwordhash '; 
        $data = array(
            'neoidKnown' => '',
            'setName' => $_POST['fullname'], 
            'role' => 'editor', 
            'userid' => '' ,//TODO
            'passwordhash' => $hashedUserPassword
        );
        $message = 'You now have full access to this project.'; 
    } else if(REGISTRATIONPOLICY === 2){
        //insert a new record:
        $query = ''; 
        $data = array();
        $message = 'You have successfully registered a new account.'; 

    }*/

    $result = $graph->executionOfParameterizedQuery($query, $data); 
    var_dump($result); 

    

}




?>
