<?php
  include_once($_SERVER["DOCUMENT_ROOT"]."/config/config.inc.php");
  include_once(ROOT_DIR."/includes/client.inc.php");
  include_once(ROOT_DIR."/includes/user.inc.php");
  $user = new User($client);
  $user->fixAlignment(); 
  
  header("Location: ../index.php");




?>