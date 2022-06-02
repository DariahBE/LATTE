<?php


?>


<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Find nodes</title>
    <link rel="stylesheet" href="/css/stylePublic.css">
    <script type="text/javascript" src="../JS/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="JS/node.js"></script>
  </head>
  <body>
    <div class="container mx-auto p-4">
      <div class="w-3/4 mx-auto">
        <h1 class="text-lg mx-auto">Basic search</h1>
        <p>Find a node by directly looking for an attribute-value pair. If you require a more advanced way of searching, consider using the <a href="node_advanced.php"> advanced search.</a> </p>
      </div>
      <div class="container" id="nodeTypes">
        <p>1. Select the type of node to find:</p>
        <div class="subbox grid grid-cols-5 w-4/5">

        </div>
      </div>
      <div class="" id="nodeProperties">
        <p>2. Select the node property to search and provide a value:</p>
        <div class="subbox grid grid-cols-1 w-4/5">

        </div>
      </div>
    </div>
    <script>$(document).ready(function(){
      searchInit();
    })</script>
  </body>
</html>
