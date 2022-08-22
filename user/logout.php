<?php
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
$user = new User(false);
$user->logout();
?>
