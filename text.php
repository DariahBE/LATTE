<?php
//include_once('includes/getnode.inc.php');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/wikidata_user_prefs.inc.php');
include_once(ROOT_DIR.'/includes/multibyte_iter.inc.php');
include_once(ROOT_DIR.'/includes/annotation.inc.php');
if(isset($_GET['texid'])){
  $propId = $_GET['texid'];
  $nodeType = 'Text';
  $propKey = helper_extractPrimary($nodeType);
  //$propKey = PRIMARIES[$nodeType];
  //cast the propID to int if type is set:
  $typeOfId = NODEMODEL[$nodeType][$propKey][1];
  if($typeOfId === "int"){
    $propId = (int)$propId;
  }
}else{
  header('Location: /error.php?type=textmissing');
  die();
}

$user = new User($client);

$user_uuid = $user->checkSession();

$annotations = new Annotation($client);
$existingAnnotation = $annotations->getExistingAnnotationsInText($propId, $user_uuid);

$wikidata = new Wikidata_user($client);
$wikidata->buildPreferences();
$node = new Node($client);
$text = $node->matchSingleNode($nodeType, $propKey, $propId);
if(!boolval($text) or !array_key_exists('coreID', $text)){
  header('Location: /error.php?type=text&id='.$propId);
  die();
}
$nodeId = $text['coreID'];
$relations = $node->getEdges($nodeId);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title><?php echo PROJECTNAME ?></title>
    <script src="/JS/jquery-3.6.0.min.js"></script>
    <script src="/JS/initiate.js"></script>
    <script src="/JS/getLang.js"></script>
    <script src="/JS/getEntities.js"></script>
    <!-- <script src="/JS/setPositions.js"></script> -->
    <script src="/JS/getEntityInfo.js"></script>
    <script src="/JS/showSingleEntityInfo.js"></script>
    <script src="/JS/rangy/rangy-core.js"></script>
    <script src="/JS/selectInText.js"></script>
    <script src="/JS/showStoredAnnotations.js"></script>
    <script src="/JS/interactWithEntities.js"></script>
    <!-- wikidata SDK and custom code! SDK docs: https://github.com/maxlath/wikibase-sdk-->
    <script src="/JS/wikidata_SDK/wikibase-sdk.js"></script>
    <script src="/JS/wikidata_SDK/wikidata-sdk.js"></script>
    <script src="/JS/wikidata.js"></script>
    <!-- extra script for wikidata content: -->
    <script src="/JS/caroussel.js"></script>
    <script src="/JS/makeMap.js"></script>
    <script src="/JS/leaflet/leaflet.js"></script>
    <script src="/JS/wikidata_prompt.js"></script>
    <link rel="stylesheet" href="/CSS/style_entities.css">
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="/CSS/leaflet/leaflet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  </head>
  <body class="bg-neutral-200 w-full">
    <div class=" 2xl:w-1/2 xl:w-2/3 items-center m-auto"> 
    <div class="">
      <!-- navbar-->

    </div>
    <!-- content-->

<div class="top ">
  <div id='normalizationDialogue' class="w-full">
    <h3 class='text-xl'>Normalization Options: </h3>
    <p>Normalization improves the pickup of entities. When enabled the Named entity returned by the NER-tool is modified by removing a list of specific characters.</p>
    <div id='normalizationOptions'>
      <div class="flex flex-initialize">
        <label for="normalization_On_Off" class="relative flex justify-between items-center p-2">
          Enable Normalization:
          <input type="checkbox" name="normalization_On_Off" class="absolute left-1/2 -translate-x-1/2 w-full h-full peer appearance-none rounded-md" />
          <span class="w-16 h-10 flex items-center flex-shrink-0 ml-4 p-1 bg-gray-300 rounded-full duration-300 ease-in-out peer-checked:bg-green-400 after:w-8 after:h-8 after:bg-white after:rounded-full after:shadow-md after:duration-300 peer-checked:after:translate-x-6"></span>
        </label>
      </div>
      <div>
        <p>Provide a comma (,) separated list of symbols to be normalized: </p>
        <label for="normalization_list">Normalize these symbols: </label>
        <input type="text" id="normalizationList" name="normalization_list" class="p-2 border-2 border-black border-solid rounded-md">
      </div>
    </div>
    <br>
    <!-- strip spaces when selecting in text: -->
    <div class="">

    </div>
    <!-- strip accents from texts. -->
    <div class="">

    </div>

  </div>
  <div id="explorationDialogue" class="w-full py-4 my-4">
    <h3 class="text-xl">Node Exploration: </h3>
    <!-- automatic exploration of the retrieved entities-->
    <label for="autoexplore">Fetch recognized entities: </label>
    <input type="checkbox" name="autoexplore" value="">
  </div>
</div>

<div class="main flex flex-row py-4 my-4">
  <div class="left" id="leftMainPanel">
  <h3 class="text-xl">Text: </h3>

    <div class="subbox leftsubbox" id="textcontent">

    <?php
      $textString = $text['data'][0]->first()['node']['properties']['text'];
      $textLanguage = isset($text['data'][0]->first()['node']['properties']['language']) ? $text['data']['properties']['language']: False;
      $i = 0;
      foreach(new MbStrIterator($textString) as $c) {
        echo "<span class='ltr' data-itercounter=$i>".nl2br($c)."</span>";
        $i++;
      }
    ?>

    </div>
    <script>
      var coreNodes = <?php echo json_encode(array_keys(CORENODES)); ?>;
      var languageOptions = {
        'text': <?php echo json_encode($textString)?>,
        'ISO_code': <?php echo json_encode($textLanguage)?>,
        'textid': <?php echo json_encode((int)$propId)?>,
        'nodeid': <?php echo json_encode((int)$nodeId)?>
      };
      var wdProperties = <?php echo json_encode($wikidata->makeSettingsDictionary()); ?>;

      var wikidataIndication = <?php echo json_encode($wikidata->labelIndicator()); ?>;
    </script>
    <style>
      <?php
        //load style settings from config fyle, parse them as inline CSS:

        helper_parseEntityStyle();
      ?>
    </style>
  </div>
  <div class="right" id="rightMainPanel">
      <div class="meta" id="topmeta">
        <div class="language">
          <p><span class='font-bold key'>Language ISO: </span><span class='value italic' id='detectedLanguageCode'></span></p>
          <p><span class='font-bold key'>Language: </span><span class='value italic' id='detectedLanguage'></span></p>
          <p><span class='font-bold key'>Certainty: </span><span class='value italic' id='detectedLanguageCertainty'></span></p>
        </div>
        <div class="options" id="entityMatchOptions">
          <div class="hideMatches">
            <input onclick="hideUnhideEntities()" id='hideUnhideEntities' type="checkbox" name="hideMatchingEntities" value=true>
            <label for="hideMatchingEntities">Hide <span id='overlapcount'></span>annotated entities(s)</label>
          </div>
        </div>
        <div class="entities">
          <p><span class='font-bold key'>Nr. of entities: </span><span class='value italic' id='amountOfEntities'></span></p>
          <p><span class='font-bold key'>Used model: </span><span class='value italic' id='usedEntityModel'></span></p>

        </div>
      </div>
      <div class="entities">
        <div class="report" id="entitycontainer">

        </div>
        <div class="analyse" id="specificEntityDetails">

        </div>
      </div>
  </div>
  <!--<div class="extended" id="rightExtensionPanel">
    <div class="base">
      <! -- What is shown by default in the right extension panel. - ->

    </div>
    <div class="full">
      <! -- Extra slideOut panel- ->

    </div>
  </div> -->
</div>
<div id="slideover-container" class="right-0 w-1/2 h-full fixed top-0 invisible">
  <!--<div id="slideover-bg" class="w-full h-full duration-500 ease-out transition-all top-0 absolute bg-gray-900 opacity-0"></div>-->
  <div id="slideover" class="w-full bg-white h-full absolute left-0 duration-300 ease-out transition-all translate-x-full overflow-y-scroll overflow-x-hidden">
  <svg onclick='toggleSlide(0)' xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
  </svg>

      <div id="slideover-dynamicContent" class="absolute cursor-pointer text-gray-600 top-0 w-full h-full justify-center left-0 m-5 p-5">
        <!-- with xhr data loaded: put the response here!
          this panel serves as the target for showing data in the NEO database as well as wikidata responses. 
      -->
    </div>
  </div>
</div>
<div id='setNodeDetailOverlay' class='hiddenOverlay'><p>is this required?????</p></div>
  <?php echo "<script> var storedAnnotations = ".json_encode($existingAnnotation)."</script>";
  if(count($existingAnnotation['relations']) > 0){
    echo "<script>visualizeStoredAnnotations();</script>";
  }

  ?>
</div>
</body>
</html>
