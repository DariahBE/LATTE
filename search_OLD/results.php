<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <script type="text/javascript" src='../JS/jquery-3.6.0.min.js'></script>
    <script type="text/javascript" src='JS/navigateSearch.js'></script>
    <title>Search results: </title>
  </head>
  <body>

<script>
// get the search instruction from sessionStorage:
if(typeof(Storage) !== 'undefined'){
     var x = sessionStorage.getItem('mySearchCommand');
    //  console.log(JSON.parse(x));
     var jsondata = JSON.parse(x);
 }

 runsearch();
</script>

  </body>
</html>

<?php



?>
