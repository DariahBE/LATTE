<?php

$typeOK = false;
$uuid = false;
if(isset($_GET['type'])){
  $type = ucfirst($_GET['type']);
  $approvedTypes = array('Place', 'Person', 'Event', 'Annotation');
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
include_once(ROOT_DIR.'/includes/entityviews.inc.php');


$graph = new Node($client);
//getnode that matches the provided UUID:
//var_dump($type, $uuid);
$core = $graph->matchSingleNode($type, 'uid', $uuid);
$coreId = $core['coreID'];

$neighbours = $graph->getNeighbours($coreId);

$view = new View($type, array('egoNode'=>$core, 'neighbours'=>$neighbours));
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Stable identifier: <?php echo htmlspecialchars($uuid, ENT_QUOTES, 'UTF-8');?></title>
    <link rel="stylesheet" href="/CSS/style_entities.css">
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script type="text/javascript" src="/JS/clipboardcopy.js"> </script>
  </head>
  <body class="bg-neutral-200">
    <div class="">
      <!-- navbar-->
    </div>
    <div class="container">
      <!-- content -->
      <div class="top">
        <?php $view->outputHeader(); ?>
      </div>

    </div>

  </body>
</html>
