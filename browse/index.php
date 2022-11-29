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
    <link rel="stylesheet" href="/CSS/stylePublic.css">

    <link rel="stylesheet" href="../CSS/browse.css">
  </head>
  <body class="h-full w-full">
    <script>
      var options = [];
    </script>
    <div class="container w-full h-full">
      <div class="top-0 left-0 fixed" id="top">

      </div>
      <div class="h-full w-full" id="viz">

      </div>
      <div class="bottom-0 left-0 fixed" id="bottom">

      </div>

    </div>

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
