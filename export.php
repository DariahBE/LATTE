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
$annotation = new Annotation($client);
$annotation->startTransaction(); 
$user = new User($client);
//check user
$user_uuid = $user->checkSession();
//get text: set it to the exporter together with identified text.
$text = $node->matchTextByNeo($neoId);
if (!boolval($text)){
    header('Location: /error.php');
    die('rejected node');
}
$textString = $text[TEXNODETEXT];
//set raw text: 
$export->setText($textString); 
$i = 0; 
$identifiedText = []; 
foreach(new MbStrIterator($textString) as $c){
  $identifiedText[$i] = nl2br($c); 
  $i++;
}
//set identified

$export->setIdentifiedText($identifiedText); 
//get annotations: 
$existingAnnotation = $annotation->getExistingAnnotationsInText($neoId, $user_uuid);
$export->setAnnotations($existingAnnotation); 
//get automatic annotations: the ones recognized by NER-tools: 
$autoAnnotation = $annotation->getUnlinkedAnnotationsInText($neoId); 
$export->setAutoAnnotations($autoAnnotation); 
//var_dump($autoAnnotation); 
//set document header depending on requested content. 
$export->outputHeaders(); 
$export->generateAnnotatedText();
$export->outputAnnotations($annotation);
echo $export->outputContent(); 
//var_dump($export->outputContent());

?>