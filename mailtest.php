<?php
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR."/includes/mail.inc.php");

$mail_interface = new Mail(); 
$mail_interface->setSubjectOfMail('Your '.PROJECTNAME.' password resetcode.'); 
$mail_interface->setRecipient('frederic.pietowski@kuleuven.be');
$msg = 'Hello world';
$mail_interface->setMessageContent($msg, True); 

$mail_interface->send(); 

?>