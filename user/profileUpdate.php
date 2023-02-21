<?php
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR."/includes/user.inc.php");

if(!(isset($_SESSION['userid']))){
    //redirect to loginpage and die:
    header('Location: /user/login.php');
    die();
}
//session_start();
//if the user session is set: 
include_once(ROOT_DIR."/includes/client.inc.php");
include_once(ROOT_DIR."/includes/wikidata_user_prefs.inc.php");

$user = new User($client);
$preferences = new Wikidata_user($client);

$key_to_tell_which_form = 'form_type_setting_application_value';

$postdata = $_POST; 
$formname = $postdata[$key_to_tell_which_form]; 
$data = array_keys($postdata); 
$index = array_search($key_to_tell_which_form,$data);
if($index !== FALSE){
    unset($data[$index]);
}
$actionGotThrough = $preferences->storeProfileSettings($formname, $data); 
if ($actionGotThrough){
    //delete the existing cookie! Changes are made to the user profile; make sure they are picked up by deleting the cookie
    setcookie('wd_'.$formname, NULL, time()-10, "/");
    header('Location: /user/mypage.php');
}else{
    echo 'Request rejected. You\'ll be taken back to your profile page.'; 
    header( "refresh:5;url=/user/mypage.php" );
}

?> 