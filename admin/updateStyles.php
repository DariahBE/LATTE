<?php
  include_once("../config/styleConfig.php");


  $cssFile=fopen("../CSS/style_entities.css","w");
  if($boldNodesIfIdentified){
    fwrite($cssFile,'.linkedNode{font-weight:bold;}');
  }
  fwrite($cssFile, '.Place{background-color: '.$placeNodesColorBG.'; color:'.$placeNodesColorTex.';}');
  fwrite($cssFile, '.Person{background-color: '.$personNodesColorBG.'; color:'.$personNodesColorTex.';}');
  fclose($cssFile);

?>
