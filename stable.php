<?php

if (!defined('ROOT_DIR')) {
  include_once(__DIR__.'/config/config.inc.php');
}
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/preparedviews.inc.php'); //replaced entityviews!
include_once(ROOT_DIR.'/includes/navbar.inc.php');
include_once(ROOT_DIR.'/includes/datasilo.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/wikidata_user_prefs.inc.php');


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
  header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/error.php?type=node');
  exit;
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

$user = new User($client);
$user->checkAccess(ENTITIESAREPUBLIC);
$wikidata = new Wikidata_user($client);
$wikidata->buildPreferences();


//getnode that matches the provided UUID or primary key as defined in the configfile:

//if the config file has a PK defined for the given type, use that.
//otherwise: retain the original uid (UUIDV4)
$propertyWithPK = 'uid';
if (array_key_exists($type, PRIMARIES) && boolval(PRIMARIES[$type])){
  $propertyWithPK = PRIMARIES[$type];
}
$core = $graph->matchSingleNode($type, $propertyWithPK, $uuid);
if ($propertyWithPK !== 'uid' && !(array_key_exists('coreID', $core))) {
  $core = $graph->matchSingleNode($type, 'uid', $uuid);
}
// for Annotation_auto nodes you'll need to perform a separate check: 
if ($type == ANNONODE && !(array_key_exists('coreID', $core))){
  //annotation_auto nodes always have uid set by program.
  $core = $graph->matchSingleNode('Annotation_auto', 'uid', $uuid);
}
if(array_key_exists('coreID', $core)){
  $coreNeoID = $core["neoID"]; 
  $coreId = $core['coreID'];
  $neighbours = $graph->getNeighbours($coreNeoID, false, 'see_also');
  //$textSharingEt = $graph->getTextsSharingEntity($coreId, true);
  $silo->getNeighboursConnectedBy($coreNeoID); 
  $siloArray = $silo->makeURIs('html'); 
  $block = new Blockfactory($type); 
  $textConnections = $graph->listTextsConnectedToEntityWithID((int)$coreNeoID);
  $wdqid = $graph->getwikidataValue($core["neoID"]);
}else{
  header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/error.php?type=id');
  exit;
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
    <!-- wikidata SDK and custom code! SDK docs: https://github.com/maxlath/wikibase-sdk-->
    <script src="/JS/wikidata_sdk/wikibase-sdk.js"></script>
    <script src="/JS/wikidata_sdk/wikidata-sdk.js"></script>
    <script src="/JS/wikidata.js"></script>
    <script src="/JS/interactWithEntities.js"></script>
    <!-- extra script for wikidata content: -->
    <script src="/JS/caroussel.js"></script>
    <script src="/JS/makeMap.js"></script>
    <script src="/JS/leaflet/leaflet.js"></script>
    <link rel="stylesheet" href="/CSS/leaflet/leaflet.css">
    <?php
      include_once(ROOT_DIR . '/includes/matomo.inc.php');
      echo renderMatomoTracking();
    ?>
  </head>
  <body class="bg-slate-100 text-slate-800 antialiased">
    <?php
      $adminMode = isset($user) && $user ? $user->isAdmin() : false;
      $navbar = new Navbar($adminMode); 
      echo $navbar->getNav();
    ?>
    <div class="max-w-6xl mx-auto my-8 px-4 sm:px-6 space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php 
          echo $block->makeIDBox($core); 
        ?>
      </div>

      <div id='edrbox' class="grid grid-cols-1 md:grid-cols-2 gap-6"></div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <?php
          //datasilo Knowledgebases:
          if (count($siloArray) > 0){
            echo "<div class='bg-white border border-slate-200 rounded-xl shadow-sm p-6'>";
              echo "<h3 class='text-base font-semibold text-slate-900 mb-3'>Connected knowledge bases</h3>";
              echo "<ul class='space-y-2'>";
              foreach($siloArray as $urlBlock){
                echo '<li class="text-xs text-indigo-600 hover:underline break-all">'.$urlBlock.'</li>';
              }
              echo "</ul>";
            echo "</div>";
          }
          $related_count = 0; 
          foreach($neighbours as $row){
            if($row['t'] !== Null){
              $related_count++;
            }
          }
          echo "<div class='bg-white border border-slate-200 rounded-xl shadow-sm p-6 lg:col-span-2'>";
          echo "<h3 class='text-base font-semibold text-slate-900 mb-3'>Relationships <span class='text-xs font-normal text-slate-500'>(".(int)$related_count." found)</span></h3>";
          if($related_count > 0){
            echo "<div class='overflow-x-auto max-h-[400px] overflow-y-auto rounded-lg border border-slate-200'>";
            echo "<table class='w-full border-collapse text-sm divide-y divide-slate-200'>"; 
            echo "<thead class='bg-slate-50 text-slate-700 font-semibold text-xs uppercase tracking-wider sticky top-0'><tr><th class='py-2.5 px-3 text-left'>Relation</th><th class='py-2.5 px-3 text-left'>Node</th><th class='py-2.5 px-3 text-left'>Properties</th></tr></thead>"; 
            echo "<tbody class='divide-y divide-slate-100'>";
            foreach($neighbours as $row){
              $relation = $row['r'];
              $relatedNode = $row['t']; 
              $nodeProps = $relatedNode['properties']; 
              $nodePropsList = '<ul class="space-y-1">'; 
              foreach ($nodeProps as $propkey => $propValue){
                if(($propkey !== 'priv_user') && (array_key_exists($propkey, NODEMODEL[$relatedNode['labels'][0]]))){
                  $value = NODEMODEL[$relatedNode['labels'][0]][$propkey];
                  $propCleanName = $value[0];
                  $propType = $value[1]; 
                  if($propType === 'uri'){
                    $rawUri = trim((string)$propValue);
                    if (preg_match('/^https?:\/\//i', $rawUri)) {
                      $safeUri = htmlspecialchars($rawUri, ENT_QUOTES, 'UTF-8');
                      $nodePropsList .= "<li class='text-xs'><span class='font-semibold text-slate-700'>".htmlspecialchars($propCleanName, ENT_QUOTES, 'UTF-8').":</span> <a href='$safeUri' target='_blank' rel='noopener noreferrer' class='text-indigo-600 hover:underline break-all'>".$safeUri."</a></li>";
                    } else {
                      $nodePropsList .= "<li class='text-xs'><span class='font-semibold text-slate-700'>".htmlspecialchars($propCleanName, ENT_QUOTES, 'UTF-8').":</span> <span class='text-slate-600 break-all'>".htmlspecialchars($rawUri, ENT_QUOTES, 'UTF-8')."</span></li>";
                    }
                  }else{
                    $nodePropsList .= "<li class='text-xs'><span class='font-semibold text-slate-700'>".htmlspecialchars($propCleanName).":</span> <span class='text-slate-600'>".htmlspecialchars($propValue)."</span></li>";
                  }
                }
              }
              $nodePropsList .= '</ul>';
              echo "<tr class='odd:bg-white even:bg-slate-50/50 hover:bg-slate-100/60 transition-colors'>"; 
              echo "<td class='py-2.5 px-3 font-medium text-slate-800 text-xs whitespace-nowrap'>".htmlspecialchars($relation['type'])."</td>"; 
              echo "<td class='py-2.5 px-3 text-slate-600 text-xs whitespace-nowrap'><span class='inline-block px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]'>".htmlspecialchars($relatedNode['labels'][0])."</span></td>";
              echo "<td class='py-2.5 px-3'>".$nodePropsList."</td>";
              echo "</tr>"; 
            }
            echo "</tbody></table>"; 
            echo "</div>";
          } else {
            echo "<p class='text-xs text-slate-500'>No relationship links recorded for this entity.</p>";
          }
          echo "</div>";

          if(count($textConnections['annotations'])){
            $annos = $textConnections['annotations']; 
            $texts = $textConnections['texts']; 
            $annostring = count($annos) === 1 ? 'annotation' : 'annotations'; 
            $texstring = count($texts) === 1 ? 'text' : 'texts'; 
            echo "<div class='bg-white border border-slate-200 rounded-xl shadow-sm p-6'>";
            echo "<h3 class='text-base font-semibold text-slate-900 mb-1'>Text mentions</h3>";
            echo "<p class='text-xs text-slate-500 mb-3'>".count($annos)." $annostring across ".count($texts)." $texstring</p>";
            echo "<ul class='space-y-1.5'>"; 
            $baseUrl = defined('BASE_URL') ? BASE_URL : '';
            foreach($texts as $tex){
              $texuri = $baseUrl.'/text/'.$tex;
              $safeTexUri = htmlspecialchars($texuri, ENT_QUOTES, 'UTF-8');
              $safeTex = htmlspecialchars((string)$tex, ENT_QUOTES, 'UTF-8');
              echo '<li><a class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:underline" href="'.$safeTexUri.'"><span class="text-slate-400">📄</span> Text #'.$safeTex.'</a></li>'; 
            }
            echo "</ul>"; 
            echo "</div>";
          }
        ?>
      </div>

    <?php
    //conditional block: only echo wikidata on the stable endpoint IF there's a valid identifier (external regex validation)
    if(boolval($wdqid)){
      ?>
      <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6" id="wdwindow">
        <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-200">
          <img src="/images/wikidatawiki_small.png" class="h-8 object-contain">
          <div>
            <h3 class="text-base font-semibold text-slate-900">Wikidata authority record</h3>
            <p class="text-xs text-slate-500">External identity: <?php echo htmlspecialchars($wdqid); ?></p>
          </div>
        </div>

        <div id='wdoptionsblock' class="mb-4 p-3.5 bg-slate-50 border border-slate-200 rounded-xl flex flex-wrap items-center gap-4 text-xs text-slate-700">
          <div class="flex items-center gap-2">
            <label for="wdlookuplanguage" class="font-semibold text-slate-600">Language:</label>
            <select id='wdlookuplanguage' class="bg-white border border-slate-300 rounded-md px-2 py-1 text-xs text-slate-700 focus:ring-1 focus:ring-indigo-500"></select>
          </div>
          <label class="inline-flex items-center gap-2 cursor-pointer">
            <input name='returnSameAsLookup' type='checkbox' id='returnSameAsLookup' class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <span>Prioritize lookup language</span>
          </label>
          <label class="inline-flex items-center gap-2 cursor-pointer">
            <input name='lookupConstraint' type='checkbox' id='strictLookup' class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <span>Use language fallback</span>
          </label>
        </div>

        <div id='insertWDHere'></div>

        <script>
          var qid = '<?php echo htmlspecialchars($wdqid); ?>';
          var entityType = '<?php echo htmlspecialchars($type); ?>';
          var wdProperties = <?php echo json_encode($wikidata->makeSettingsDictionary($type)); ?>;
          helper_setWDLanguages(document.getElementById('wdlookuplanguage')); 
          wd = new wikibaseEntry(qid, wdProperties, 'static', 'qid', entityType);
          wd.getWikidata()
            .then(function(){wd.renderEntities(qid)}); 
        </script>
      </div>
    <?php
    } //end of conditional wikidata block on stable endpoint. 
    ?>
    </div>


  <?php
    //add edit/delete button only to corenodes: 
    if(array_key_exists($type,  CORENODES)){
  ?>
        <script>
            <?php
              if ($type === ANNONODE || $type === 'Annotation_auto'){
                $e = false;
                $a = $coreNeoID;
              }else{
                $e = $coreNeoID;
                $a = false;
              } 
            
            ?>
        checklogin()
          .then(valid => {

            let etid = <?php echo json_encode($e); ?>;
            let annoid = <?php echo json_encode($a); ?>;
            createEditRemoveBox(etid, annoid)
            .then(divelement => {
              console.log('divelement', divelement);
              document.getElementById('edrbox').appendChild(divelement);
            })

          })
        </script>
  <?php }?>
  </body>
</html>
