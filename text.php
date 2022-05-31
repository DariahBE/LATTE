<?php
//include_once('includes/getnode.inc.php');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
if(isset($_GET['texid'])){
  $propId = (int)$_GET['texid'];
  $propKey = 'texid';
  $nodeType = 'Text';
}else{
  die('provide a valid texid over GET with key "texid".');
}

$node = new Node($client);
$text = $node->matchSingleNode($nodeType, $propKey, $propId);
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
    <script src="/JS/setPositions.js"></script>
    <script src="/JS/getEntityInfo.js"></script>
    <script src="/JS/contextMenuEntities.js"></script>
    <script src="/JS/showSingleEntityInfo.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/CSS/style_entities.css">
    <link rel="stylesheet" href="/CSS/styling.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  </head>
  <body>
    <div class="">
      <!-- navbar-->

    </div>
    <!-- content-->

<div class="top flex flex-row">
  <div id='normalizationDialogue' class="flex-row">
    <h3 class='h3'>Normalization Options: </h3>
    <p>Normalization improves the pickup of entities. When enabled the Named entity returned by the NER-tool is modified by removing a list of specific characters.</p>
    <div id='normalizationOptions'>
      <div class="">
        <label for="normalization_On_Off">Enable Normalization: </label>
        <input type="checkbox" id="useNormalization" name="normalization_On_Off" onchange="normalize_all()" >
      </div>
      <div>
        <p>Provide a comma (,) separated list of symbols to be normalized: </p>
        <label for="normalization_list">Normalize these symbols: </label>
        <input type="text" id="normalizationList" name="normalization_list">
      </div>
    </div>
  </div>
  <div class="flex-row">
    <!-- automatic exploration of the retrieved entities-->
    <label for="autoexplore">Fetch recognized entities: </label>
    <input type="checkbox" name="autoexplore" value="">
  </div>
</div>

<div class="main flex flex-row">
  <div class="left" id="leftMainPanel">
    <div class="subbox leftsubbox" id="textcontent">

    <?php
      $textString = $text['data']['properties']['text'];
      $textLanguage = isset($text['data']['properties']['language']) ? $text['data']['properties']['language']: False;
      echo nl2br($textString);

    ?>
    </div>
    <script>
      var languageOptions = {
        'text': <?php echo json_encode($textString)?>,
        'ISO_code': <?php echo json_encode($textLanguage)?>,
        'textid': <?php echo json_encode((int)$propId)?>,
        'nodeid': <?php echo json_encode((int)$nodeId)?>
      };
    </script>
  </div>
  <div class="right" id="rightMainPanel">
      <div class="meta" id="topmeta">
        <div class="language">
          <p><span class='font-bold key'>Language ISO: </span><span class='value italic' id='detectedLanguageCode'></span></p>
          <p><span class='font-bold key'>Language: </span><span class='value italic' id='detectedLanguage'></span></p>
          <p><span class='font-bold key'>Certainty: </span><span class='value italic' id='detectedLanguageCertainty'></span></p>
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
</div>
<div id='setNodeDetailOverlay' class='hiddenOverlay'> </div>
  <script>
    <?php echo "const coreNodeRelations = ". json_encode($relations); ?> ;
    attachSelectController(); //attaches select event to text ==> allows user to select words and perform lookup. 
  </script>
</body>
</html>
