<?php
  include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');

  //to identify a node use:
  if(isset($_GET['property'])){
    $keyname = $_GET['property'];
    $mode = 'byproperty';
  }else{
    $mode = 'byneo';
  }
  $value = $_GET['value'];
  //e.g.: http://entitylinker.test/browse/?property=uid&value=2d82ba58-745f-4d86-aadb-bdf319a07c56

  $data = array();

  /*
    keyname:
      UID = matches against the neo4J assigned apoc method to createUUID()
      any other keyname should be handled with caution.

    value:
      value related to the set property (sequence escaping necessary)!!

  NOTE:
    - You can't use neovis.js, your password is exposed as plaintext!!!

  DOCUMENTATION:
    - start by identifying a node; extract it's id() property.
    - follow up by using the getNeighbours() method in the NODE class to extend the graph.
    - on doubleclick: each NODE should extend to the server and get it's neighbours.
    - On server response ==> extend the current graph!
  */
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Interactive explorer - powered by vis.js</title>
    <script type="text/javascript" src="../JS/vis-network.min.js"></script>
    <script type="text/javascript" src="../JS/browse_viz.js"></script>
    <link rel="stylesheet" href="../CSS/browse.css">
  </head>
  <body>
    <script>
      var canvas_id = 'viz';
      var data = <?php echo json_encode($data); ?>;
      var options = [];
    </script>
    <div class="container w-full h-full">
      <div class="top-0 left-0 fixed" id="top">

      </div>
      <div class="" id="viz">

      </div>
      <div class="bottom-0 left-0 fixed" id="bottom">

      </div>

    </div>

  </body>
</html>
<?php


?>
