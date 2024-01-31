<?php


include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/multibyte_iter.inc.php');
include_once(ROOT_DIR.'/includes/annotation.inc.php');
include_once(ROOT_DIR.'/includes/export.inc.php');

//mode and neoID as input ==> logic in export.inc.php

//variants still not showing in export!

$mode = $_GET['mode']; 
$neoId = (int)$_GET['neoid'];

$export = new Exporter($client, $mode);
$node = new Node($client);
$annotations = new Annotation($client);
//TODO (critical) test transaction behaviour of annotation 
$annotation->startTransaction(); 
$user = new User($client);
//check user
$user_uuid = $user->checkSession();
//get text: set it to the exporter together with identified text.
$text = $node->matchTextByNeo($neoId);
if (!boolval($text)){
    //todo: redirect to error page!
    die();
}
$textString = $text['text'];
//set raw text: 
$export->setText($textString); 
$i = 0; 
$identifiedText = []; 
foreach(new MbStrIterator($textString) as $c){
  //var_dump($c);
  $identifiedText[$i] = nl2br($c); 
  $i++;
}
//set identified

$export->setIdentifiedText($identifiedText); 
//get annotations: 
$existingAnnotation = $annotations->getExistingAnnotationsInText($neoId, $user_uuid);
$export->setAnnotations($existingAnnotation); 
//get automatic annotations: the ones recognized by NER-tools: 
$autoAnnotation = $annotations->getUnlinkedAnnotationsInText($neoId); 
$export->setAutoAnnotations($autoAnnotation); 
//var_dump($autoAnnotation); 
//set document header depending on requested content. 
$export->outputHeaders(); 
$export->generateAnnotatedText();
$export->outputAnnotations($annotations);
echo $export->outputContent(); 
//var_dump($export->outputContent());

?>