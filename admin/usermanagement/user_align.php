<?php
  if (!defined('ROOT_DIR')) {
    include_once(dirname(dirname(__DIR__))."/config/config.inc.php");
  }
  include_once(ROOT_DIR."/includes/client.inc.php");
  include_once(ROOT_DIR."/includes/user.inc.php");

  $user = new User($client);
  if ($user->checkSession() === false || !$user->isAdmin()) {
    header("HTTP/1.0 403 Forbidden");
    die("Forbidden access: administrator rights required.");
  }

  $user->fixAlignment(); 
  
  $target = (defined('BASE_URL') ? BASE_URL : '') . "/admin/usermanagement/index.php";
  header("Location: $target");
  exit;
?>