<?php

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/entityviews.inc.php');   //get rid of this!
include_once(ROOT_DIR.'/includes/preparedviews.inc.php'); //replaced entityviews!
include_once(ROOT_DIR.'/includes/navbar.inc.php');
include_once(ROOT_DIR.'/includes/datasilo.inc.php');




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
$silo = new Siloconnector($client); 

//getnode that matches the provided UUID or primary key as defined in the configfile:

//if the config file has a PK defined for the given type, use that.
//otherwise: retain the original uid (UUIDV4)
$propertyWithPK = 'uid';
if (array_key_exists($type, PRIMARIES) && boolval(PRIMARIES[$type])){
  $propertyWithPK = PRIMARIES[$type];
}
$core = $graph->matchSingleNode($type, $propertyWithPK, $uuid);
if(array_key_exists('coreID', $core)){
  $coreNeoID = $core["neoID"]; 
  $coreId = $core['coreID'];
  $neighbours = $graph->getNeighbours($coreId);
  $textSharingEt = $graph->getTextsSharingEntity($coreId, true);
  $silo->getNeighboursConnectedBy($coreNeoID); 
  $siloArray = $silo->makeURIs('html'); 
  $block = new Blockfactory($type); 
  //$view = new View($type, array('egoNode'=>$core, 'neighbours'=>$neighbours, 'relatedTexts'=>$textSharingEt));
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
    <?php
      $navbar = new Navbar(); 
      echo $navbar->nav;
    ?>
    <div class="container w-full m-4 p-2">

        <?php 
          echo $block->makeIDBox($core); 
        ?>

    </div>
    <?php

    ?>
    <div class="grid md:grid-cols-2 grid-cols-1">
      <?php
        //datasilo Knowledgebases:
        if (count($siloArray) > 0){
          echo "<div class='p-2 m-2'>";
            echo "<h3>Connected knowledgebases:</h3>";
            echo "<ul>";
            foreach($siloArray as $urlBlock){
              echo '<li class="kblink">'.$urlBlock.'</li>';

            }
            echo "</ul>";
          echo "</div>";
        }
        if(True){
          echo "<div class='p-2 m-2'>";
          echo "<h3>Test block1; </h3>";
          echo "</div>";
        }
        if(True){
          echo "<div class='p-2 m-2'>";
          echo "<h3>Test block2; </h3>";
          echo "</div>";
        }
        if(True){
          echo "<div class='p-2 m-2'>";
          echo "<h3>Test block3; </h3>";
          echo "</div>";
        }
        if(True){
          echo "<div class='p-2 m-2'>";
          echo "<h3>Test block4; </h3>";
          echo "</div>";
        }
      
      
      
      ?>

<!--    todo!
      <div class="" id="tableTarget">
        <script>
          <?php
            //$view->generateJSONOnly(true);
            //output of JSON data: get rid of the view construction!
            //echo "var silos = ".json_encode($siloArray).";";
            //!!!!!!!         TODO:::::
            //echo "var variants = ".json_encode($view->variants).";";
            //echo "var texts = ".json_encode($view->relatedText).";";
          ?>
        </script>
      </div>
    </div> -->
    <hr>
    <div class="h-full w-full" id="visualizeWindow">

    </div>

  </body>
</html>
