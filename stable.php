<?php

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/entityviews.inc.php');



$typeOK = false;
$uuid = false;
if(isset($_GET['type'])){
  $type = ucfirst($_GET['type']);
  $approvedTypes = array_keys(NODEMODEL);
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

}


if(!($uuid)){
  header('Location: /error.php?type=uuid');
  die();
}


$graph = new Node($client);
//getnode that matches the provided UUID or primary key as defined in the configfile:

//if the config file has a PK defined for the given type, use that.
//otherwise: retain the original uid (UUIDV4)
$propertyWithPK = 'uid';
if (array_key_exists($type, PRIMARIES) && boolval(PRIMARIES[$type])){
  $propertyWithPK = PRIMARIES[$type];
}
$core = $graph->matchSingleNode($type, $propertyWithPK, $uuid);
if(array_key_exists('coreID', $core)){
  $coreId = $core['coreID'];
  $neighbours = $graph->getNeighbours($coreId);
  $textSharingEt = $graph->getTextsSharingEntity($coreId, true);
  $view = new View($type, array('egoNode'=>$core, 'neighbours'=>$neighbours, 'relatedTexts'=>$textSharingEt));
}else{
  header('Location:/error.php?type=id');
}
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
    <div class="container row">
      <!-- content -->
      <div class="top">
        <?php $view->outputHeader(); ?>
      </div>

    </div>
    <div class="container row">
      <div class="content row-span-2 md:row-span-3" id="tableTarget">
        <script>
          <?php
            $view->generateJSONOnly(true);
            //output of JSON data:
            echo "var variants = ".json_encode($view->variants).";";
            echo "var silos = ".json_encode($view->datasilos).";";
            echo "var texts = ".json_encode($view->relatedText).";";
            //echo "var annotations = ". json_encode($view->)
          ?>
        </script>
      </div>

    </div>

    <div class="h-full w-full" id="visualizeWindow">

    </div>

  </body>
</html>
