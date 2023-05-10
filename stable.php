<?php

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
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
  $neighbours = $graph->getNeighbours($coreNeoID, false, 'see_also');
  //$textSharingEt = $graph->getTextsSharingEntity($coreId, true);
  $silo->getNeighboursConnectedBy($coreNeoID); 
  $siloArray = $silo->makeURIs('html'); 
  $block = new Blockfactory($type); 
  $textConnections = $graph->listTextsConnectedToEntityWithID((int)$coreNeoID);
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
    <div class="w-full centerCustom">
    <div class="md:grid md:grid-cols-2 grid-cols-1 w-7/8 m-4 mx-auto px-4 ">

      <?php 
        echo $block->makeIDBox($core); 
      ?>

    </div>
    <div class="md:w-4/5 md:grid md:grid-cols-2 grid-cols-1 centerCustom">
      <?php
        //datasilo Knowledgebases:
        if (count($siloArray) > 0){
          echo "<div class='p-2 m-2'>";
            echo "<h3 class='text-lg'>Connected knowledgebases:</h3>";
            echo "<ul>";
            foreach($siloArray as $urlBlock){
              echo '<li class="externalURILogo">'.$urlBlock.'</li>';
            }
            echo "</ul>";
          echo "</div>";
        }
        if(count($neighbours)){
          echo "<div class='p-2 m-2'>";
          echo "<h3 class='text-lg'>".count($neighbours)." connection(s) </h3>";
          echo "<table>"; 
          echo "<thead class='font-bold bg-slate-300'><td>relation</td><td>node</td><td>nodeproperties</td></thead>"; 
          foreach($neighbours as $row){
            $relation = $row['r'];
            $relatedNode = $row['t']; 
            $nodeProps = $relatedNode['properties']; 
            $nodePropsList = '<ul>'; 
            foreach ($nodeProps as $propkey => $propValue){
              if(array_key_exists($propkey, NODEMODEL[$relatedNode['labels'][0]])){
                $value = NODEMODEL[$relatedNode['labels'][0]][$propkey];
                $propCleanName = $value[0];
                $propType = $value[1]; 
                if($propType === 'uri'){
                  $nodePropsList .= "<li><span class='font-bold'>".htmlspecialchars($propCleanName).":</span> <a href='$propValue' target='_blank'><span>".htmlspecialchars($propValue)."</span></a></li>";
                }else{
                  $nodePropsList .= "<li><span class='font-bold'>".htmlspecialchars($propCleanName).":</span> <span>$propValue</span></li>";
                }
              }
            }
            $nodePropsList .= '</ul>';
            echo "<tr>"; 
            echo "<td>".$relation['type']."</td>"; 
            echo "<td>".$relatedNode['labels'][0]."</td>";
            echo "<td>".$nodePropsList."</td>";
            echo "</tr>"; 
          }
          echo "</table>"; 
          echo "</div>";
        }
        if(count($textConnections['annotations'])){
          //count annotations: 
          $annos = $textConnections['annotations']; 
          $texts = $textConnections ['texts']; 
          //display text: 
          $annostring = count($annos) === 1 ? 'annotation':'annotations'; 
          $texstring = count($texts) === 1 ? 'text':'texts'; 
          echo "<div class='p-2 m-2'>";
          echo "<h3 class='text-lg'>".count($annos)." $annostring in ".count($texts)." $texstring</h3>";
          echo "<h4 class='font-bold'>Texts:</h4>";
          echo "<ul>"; 
          foreach($texts as $tex){
            $texuri = $baseURI.'/text/'.$tex; 
            echo '<li><a class="internalURILogo" href= "'.$texuri.'">'.$tex.'</a></li>'; 
          }
          echo "</ul>"; 
          echo "</div>";
        }
      
      
      ?>

    <div class="h-full w-full" id="visualizeWindow">

    </div>
    </div>
  </body>
</html>
