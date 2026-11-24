<?php
include_once(dirname(__DIR__) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
$user = new User(false);
$user->logout();
?>
