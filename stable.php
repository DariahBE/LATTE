<?php

$typeOK = false;
$uuid = false;
if(isset($_GET['type'])){
  $type = $_GET['type'];
  $approvedTypes = array('place', 'person', 'event');
  if(in_array($type, $approvedTypes)){
    $typeOK = true;
  }
}
if(!($typeOK)){
  header('Location: /error.php?type=node');
  die();
}

if(isset($_GET['uuid'])){
  $uuid = $_GET['uuid'];
  //CHECK validity: against V4 UUID Specs.: https://en.wikipedia.org/wiki/Universally_unique_identifier#Version_4_.28random.29
  if(!(preg_match('/^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$/', $uuid)) ){
    $uuid = false;
  }
}


if(!($uuid)){
  header('Location: /error.php?type=uuid');
  die();
}

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');


$graph = new Node($client);
//getnode that matches the provided UUID:
$core = $graph->matchSingleNode($type, 'uid', $uuid);
var_dump($core);

$neighbours = $graph->getNeighbours($coreID);

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Stable identifier: <?php echo htmlspecialchars($uuid, ENT_QUOTES, 'UTF-8');?></title>
  </head>
  <body>

  </body>
</html>
