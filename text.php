<?php
//include_once('includes/getnode.inc.php');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/wikidata_user_prefs.inc.php');
include_once(ROOT_DIR.'/includes/multibyte_iter.inc.php');
include_once(ROOT_DIR.'/includes/annotation.inc.php');
include_once(ROOT_DIR.'/includes/navbar.inc.php');

$node = new Node($client);


if(isset($_GET['texid'])){
  $propId = $_GET['texid'];
  $nodeType = TEXNODE;
  $propKey = helper_extractPrimary($nodeType);
  //$propKey = PRIMARIES[$nodeType];
  //cast the propID to int if type is set:
  $typeOfId = NODEMODEL[$nodeType][$propKey][1];
  if($typeOfId === "int"){
    $propId = (int)$propId;
  }
}else{
  //load the first text OR error out
  // if we get a returned value from our method ==> load it. 
  header('Location: /error.php?type=textmissing');
  die();
}
//die($propId); 

if($propId == -1){
  //load the first text OR error out
  // if we get a returned value from our method ==> load it. 
  $firstText = $node->getFirstText();
  //var_dump($firstText); 
  //die(); 
  if($firstText !== false){
      header("Location: /text/$firstText");
  }else{
    //else: DB holds no texts ==> error out: 
      header('Location: /error.php?type=textmissing');
    }
    die();
  }

$user = new User($client);
$user->checkAccess(TEXTSAREPUBLIC);
$annotation = new Annotation($client);
$annotation->startTransaction(); 
$wikidata = new Wikidata_user($client);

//checkSession returns: the session['userid'] value
// which is the SQL id!!!
$user_id = $user->checkSession();


$wikidata->buildPreferences();
$text = $node->matchSingleNode($nodeType, $propKey, $propId);
if(!boolval($text) or !array_key_exists('coreID', $text)){
  header('Location: /error.php?type=text&id='.$propId);
  die();
}
$nodeId = $text['coreID'];
$neoId = $text['neoID'];  
// pass the user ID! NOT the UUID!
//send SQL ID of the user! (OK)
$existingAnnotation = $annotation->getExistingAnnotationsInText($neoId, $user_id);
$unlinkedAnnotations = $annotation->getUnlinkedAnnotationsInText($neoId); 


?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title><?php echo PROJECTNAME ?></title>
    <script src="/JS/jquery-3.6.0.min.js"></script>
    <!-- <script src="/JS/initiate.js"></script>  autoload of language and extractor, don't do this -->
    <script src="/JS/getLang.js"></script>
    <script src="/JS/getEntities.js"></script>
    <!-- <script src="/JS/setPositions.js"></script> -->
    <script src="/JS/getEntityInfo.js"></script>
    <script src="/JS/showSingleEntityInfo.js"></script>
    <script src="/JS/rangy/rangy-core.js"></script>
    <script src="/JS/selectInText.js"></script>
    <script src="/JS/showStoredAnnotations.js"></script>
    <script src="/JS/interactWithEntities.js"></script>
    <script src="/JS/et_variants.js"></script>
    <script src="/JS/knowledgebase.js"></script>
    <!-- wikidata SDK and custom code! SDK docs: https://github.com/maxlath/wikibase-sdk-->
    <script src="/JS/wikidata_SDK/wikibase-sdk.js"></script>
    <script src="/JS/wikidata_SDK/wikidata-sdk.js"></script>
    <script src="/JS/wikidata.js"></script>
    <!-- extra script for wikidata content: -->
    <script src="/JS/caroussel.js"></script>
    <script src="/JS/makeMap.js"></script>
    <script src="/JS/leaflet/leaflet.js"></script>
    <script src="/JS/wikidata_prompt.js"></script>
    <!-- datatype validators.  -->
    <script src="/JS/validation.js"></script>
    <link rel="stylesheet" href="/CSS/style_entities.css">
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="/CSS/leaflet/leaflet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  </head>
  <body class="bg-neutral-200 w-full">
  <?php
    $navbar = new Navbar(); 
    echo $navbar->getNav();
  ?>

    <div class="2xl:w-1/2 xl:w-2/3 items-center m-auto"> 
    <!-- content-->

<div class="top ">
  <!-- Navigation for elements to browse between texts: -->
  <div class="flex flex-row justify-between items-center w-full">
    <?php
      $firstText = $node->getFirstText();
      $prevText = $node->getPreviousText($propId);
      $nextText = $node->getNextText($propId);
      $lastText = $node->getLastText();
      
    ?>
    <div class="<?php echo ($firstText === $propId) ? 'invisible' : 'hover:underline font-bold' ;?>"><a disabled class='' href='/text/<?php echo $firstText ; ?>'><< First text</a></div>
    <div><a class=' <?php echo ($prevText === False) ? 'invisible' : 'hover:underline font-bold' ;?>' href='/text/<?php echo $prevText; ?>'>< Previous text</a></div>
    <div><a class=' <?php echo ($nextText === False) ? 'invisible' : 'hover:underline font-bold' ;?>' href='/text/<?php echo $nextText; ?>'>Next text ></a></div>
    <div><a class=' <?php echo ($lastText === $propId) ? 'invisible' : 'hover:underline font-bold'; ?>' href='/text/<?php echo $lastText; ?>'>Last text >></a></div>
  </div>
  <!-- <div id='normalizationDialogue' class="w-full">
    <h3 class='text-xl'>Normalization Options: </h3>
    <p>Normalization improves the pickup of entities. When enabled the Named entity returned by the NER-tool is modified by removing a list of specific characters.</p>
    <div id='normalizationOptions'>
      <div class="flex flex-initialize">
        <label for="normalization_On_Off" class="relative flex justify-between items-center p-2">
          Enable Normalization:
        </label>
          <input id="normalization_On_Off" type="checkbox" name="normalization_On_Off" class="p-2 border-2 border-black border-solid rounded-md" />
      </div>
      <div>
        <p>Provide a comma (,) separated list of symbols to be normalized: </p>
        <label for="normalizationList">Normalize these symbols: </label>
        <input type="text" id="normalizationList" name="normalization_list" class="p-2 border-2 border-black border-solid rounded-md">
      </div>
    </div>
    <br>

  </div> -->
  <!-- 
  <div id="explorationDialogue" class="w-full py-4 my-4">
    <h3 class="text-xl">Node Exploration: </h3>
    <! -- automatic exploration of the retrieved entities -- >
    <label for="autoexplore">Fetch recognized entities: </label>
    <input id="autoexplore" type="checkbox" name="autoexplore" value="">
  </div>-->
</div>

<div class="main flex flex-row py-4 my-4">
  <div class="left float-left w-full m-2 p-2" id="leftMainPanel">
  <h3 class="text-xl">Text: </h3>
    <div class="subbox leftsubbox" >
      <div class="flex h-12" id="exportBox">
        <a class="object-contain h-10" href="/export.php?mode=xml&neoid=<?php echo (int)$neoId?>">
          <img class="object-contain h-10 " src='/images/xml-export.png'/>
        </a>
        <a class="object-contain h-10" href="/export.php?mode=json&neoid=<?php echo (int)$neoId?>">
          <img class="object-contain h-10" src='/images/json-export.png'/>
        </a>
      </div>
      <div id="textcontent">
      <?php
        if(array_key_exists(TEXNODETEXT, $text['data'][0]->first()['node']['properties']->toArray())){

          $textString = $text['data'][0]->first()['node']['properties'][TEXNODETEXT];
          $i = 0;
          foreach(new MbStrIterator($textString) as $c) {
            echo "<span class='ltr' data-itercounter=$i>".nl2br($c)."</span>";
            $i++;
          }
          
          $coreNodeFiltered = array(); 
          foreach(array_keys(CORENODES) as $cn){
            if($cn !== TEXNODE && $cn !== ANNONODE){
              $coreNodeFiltered[]=$cn; 
            }
          }
        }
       ?>

      </div>
    </div>
    <script>
      var coreNodes = <?php echo json_encode($coreNodeFiltered); ?>;
      var annocoreNode = <?php echo json_encode(ANNONODE); ?>;
      var languageOptions = {
        'text': <?php echo json_encode($textString)?>,
        'ISO_code': <?php echo json_encode(false)?>,
        'textid': <?php echo json_encode((int)$propId)?>,
        'nodeid': <?php echo json_encode((int)$neoId)?>
      };
      var wdProperties = <?php echo json_encode($wikidata->makeSettingsDictionary()); ?>;
     // var wikidataIndication = <?php //echo json_encode($wikidata->labelIndicator()); ?>;
      let startcode = "<?php echo ANNOSTART; ?>";
      let stopcode = "<?php echo ANNOSTOP; ?>";
    </script>
    <style>
      <?php
        //load style settings from config fyle, parse them as inline CSS:

        helper_parseEntityStyle();
      ?>
    </style>
  </div>

  <div class="right float-right" id="rightMainPanel">
      <div class="meta" id="topmeta">
        <!--controlling options for WD string-lookups-->
        <div id='wdoptionsblock'>
          <div class="flex">
            <img src="/images/wikidatawiki_small.png" class="h-auto max-h-10 rounded-r-lg p-1">
            <p class='font-bold'>entity lookup options:</p>
          </div>
          <select id='wdlookuplanguage'></select>
          <br>
          <input name='returnConstraint' type='checkbox' id='returnSameAsLookup'>
          <label for='returnSameAsLookup'>Prioritize results in lookuplanguage</label>
          <br>
          <input name='lookupConstraint' type='checkbox' id='strictLookup'>
          <label for='strictLookup'>Use language fallback</label>
        </div>
        <br>
        <div class='resize-none'>
          <h4 class="font-bold text-lg">Fuzzy Matching</h4>
          <div class="flex flex-row">
          <p class="pr-1 mr-1">Levenshtein Distance: </p>
          <input type="checkbox" name='allow_ld' id='use_ld' onclick="open_ld_maxhits()">
          </div>
          <input class="hidden" type="number" min="1" name='max_ld_hits' id='max_ld' value='5'>
        </div>
        <?php
          if(LATTECONNECTOR){
            ?>
            <div>
            <h4 class="font-bold text-lg">LATTE Connector</h4>
            <div class="hidden" id="connectorExpand">
              <div class="language">
                <p><span class='font-bold key'>Language ISO: </span><span class='value italic' id='detectedLanguageCode'></span></p>
                <p><span class='font-bold key'>Language: </span><span class='value italic' id='detectedLanguage'></span></p>
                <p><span class='font-bold key'>Certainty: </span><span class='value italic' id='detectedLanguageCertainty'></span></p>
              </div>
              <!-- <div class="options" id="entityMatchOptions">
                <div class="hideMatches">
                  <input onclick="hideUnhideEntities()" id='hideUnhideEntities' type="checkbox" name="hideMatchingEntities" value=true>
                  <label for="hideUnhideEntities">Hide <span id='overlapcount'></span>annotated entities(s)</label>
                </div>
              </div> -->
              <div class="entities">
                <p><span class='font-bold key'>Nr. of entities: </span><span class='value italic' id='amountOfEntities'></span></p>
                <p><span class='font-bold key'>Used model: </span><span class='value italic' id='usedEntityModel'></span></p>
              </div>
            </div>
            <button id="extractorTrigger" onclick="entity_extraction_launcher()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded">Extract!</button>
            <div id="extractorProgress" class="hidden"></div>
          </div>
        <?php
          }
        
        ?>

      </div>
      <div class="entities">
        <div class="report" id="entitycontainer">

        </div>
        <!-- <div class="analyse" id="specificEntityDetails">

        </div> -->
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
<div id="slideover-container" class="right-0 w-1/2 h-full fixed top-0 invisible z-50">
  <!--<div id="slideover-bg" class="w-full h-full duration-500 ease-out transition-all top-0 absolute bg-gray-900 opacity-0"></div>-->
  <div id="slideover" class="w-full bg-white h-full absolute left-0 duration-300 ease-out transition-all translate-x-full overflow-y-scroll overflow-x-hidden">
  <svg onclick='toggleSlide(0); ignoreSuggestion();' xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
  </svg>

      <div id="slideoverDynamicContent" class="absolute text-gray-600 top-0 w-full h-full justify-center left-0 m-5 p-5">
        <!-- with xhr data loaded: put the response here!
          this panel serves as the target for showing data in the NEO database as well as wikidata responses. 
      -->
    </div>
  </div>
</div>
<!--<div id='setNodeDetailOverlay' class='hiddenOverlay'></div>-->
  <?php
  echo "<script> const storedAnnotations = ".json_encode($existingAnnotation)."</script>";
  echo "<script> const automatic_annotations = ".json_encode($unlinkedAnnotations)."</script>"; 
  if(count($existingAnnotation['relations']) > 0 || count($unlinkedAnnotations) > 0 ){
    echo "<script>visualizeStoredAnnotations();</script>";
  }
  ?>
  <script>
    helper_setWDLanguages(document.getElementById('wdlookuplanguage')); 
  </script>
</div>
</body>
</html>
