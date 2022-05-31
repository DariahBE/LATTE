<?php


?>


<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Find nodes</title>
    <script type="text/javascript" src="../JS/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="JS/node.js"></script>
  </head>
  <body>
    <div class="">
      <div class="" id="nodeTypes">
        <p>1. Select the type of node to find:</p>
        <div class="subbox">

        </div>
      </div>
      <div class="" id="nodeProperties">
        <p>2. Select the node property to search:</p>
        <div class="subbox">

        </div>
      </div>
      <div class="" id="propertyValue">
        <p>3. Provide a value to search: </p>
        <div class="subbox">

        </div>
      </div>
    </div>
    <script>$(document).ready(function(){
      searchInit();
    })</script>
  </body>
</html>
