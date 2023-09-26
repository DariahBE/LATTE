<?php
//start by determining if the graph starts from a NEO ID or from a KV pair.
//if the graph starts from an ID ==> pass it in the init()-call and be done with it.
//otherwise perform an initial check in the interface and pass the resulting ID to init.
$value = $_GET['value'];
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
//to identify a node use:
if(isset($_GET['property'])){
  include_once(ROOT_DIR."\includes\getnode.inc.php");
  $node = new Node($client);
  $keyname = $_GET['property'];
  $subresult = $node->matchSingleNode(false, $keyname, $value);
  $value = $subresult['data'][0][0]->get('ID');
}
echo '<script> var colorDefinitions = '. json_encode(CORENODES) .'</script>';
unset($node); 
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" class="h-full">
  <head>
    <meta charset="utf-8">
    <title>Interactive explorer - powered by vis.js</title>
    <script type="text/javascript" src="../JS/vis-network.min.js"></script>
    <script type="text/javascript" src="../JS/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="../JS/browse_viz.js"></script>
    <!-- wikidata SDK and custom code! SDK docs: https://github.com/maxlath/wikibase-sdk-->
    <script src="/JS/wikidata_SDK/wikibase-sdk.js"></script>
    <script src="/JS/wikidata_SDK/wikidata-sdk.js"></script>
    <script src="/JS/wikidata.js"></script> <!-- Last wikidata file!-->
    <link rel="stylesheet" href="/CSS/stylePublic.css">

    <link rel="stylesheet" href="../CSS/browse.css">
  </head>
  <body class="h-full w-full">
    <script>
      var options = [];
    </script>
    <div class="w-full h-full">
      <div class="top-0 left-0 fixed z-50 w-full" id="top">

      </div>
      <div class="h-full w-full" id="viz">

      </div>
      <div class="bottom-0 left-0 fixed" id="bottom">

      </div>

    </div>

<!-- slide over panel for node attributes trigger --> 
<div id="slideover-container" class="right-0 w-1/3 h-full fixed top-0 invisible z-50">
  <div id="slideover" class="w-full bg-white h-full absolute left-0 duration-300 ease-out transition-all translate-x-full">
    <div class="w-full absolute cursor-pointer text-gray-600 top-0 flex items-center justify-center left-0 m-3 p-3 flex-col">
      <h1 class='w-full text-xl'>Node information: </h1>
      <div id='defaultExplain'><p>test</p></div>
      <h2 class='w-full text-lg'>Statistics: </h2>
      <div id='nodestatisticsBox'><p>test</p></div>
      <h2 class='w-full text-lg'>URI: </h2>
      <div id='nodeLinkBox'></div>
      <h2 class='w-full text-lg'>Metadata: </h2>
      <div id='metadataboxGoesHere'></div>
    </div>
  </div>
</div>
<!-- end of slide over -->
  </body>
  <script>
    $( document ).ready(function() {
        init(<?php echo (int)$value;?>);
    });
    
  </script>
</html>
<?php
  die();
?>
