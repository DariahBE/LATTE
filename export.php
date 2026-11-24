<?php


include_once(__DIR__ . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/multibyte_iter.inc.php');
include_once(ROOT_DIR . '/includes/annotation.inc.php');
include_once(ROOT_DIR . '/includes/collection.inc.php');
include_once(ROOT_DIR . '/includes/export.inc.php');

// Mode and neoID as input ==> logic in export.inc.php.

// Variants still not showing in export!

$mode = isset($_GET['mode']) ? $_GET['mode'] : ''; 
$neoId = isset($_GET['neoid']) ? (int)$_GET['neoid'] : 0;

$export = new Exporter($client, $mode);
$node = new Node($client);
$annotation = new Annotation($client);
$user = new User($client);
// Check user.
// Check session returns the SQL user id.
$user_id = $user->checkSession();
// Get text and pass it to the exporter together with identified text.
$text = $node->matchTextByNeo($neoId);
if (!boolval($text)){
    header('Location: ' . BASE_URL . '/error.php');
    exit('Rejected node');
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
// pass the user ID! NOT the UUID!
//send SQL ID of the user! (OK)
$existingAnnotation = $annotation->getExistingAnnotationsInText($neoId, $user_id);
$export->setAnnotations($existingAnnotation); 
//get automatic annotations: the ones recognized by NER-tools: 
$autoAnnotation = $annotation->getUnlinkedAnnotationsInText($neoId); 
$export->setAutoAnnotations($autoAnnotation); 
$collectionManager = new CollectionManager($client);
$textCollections = $collectionManager->getMemberCollectionsForText($neoId, $user->neoId ? (int)$user->neoId : null, TEXTSAREPUBLIC);
$export->setCollections($textCollections);
//var_dump($autoAnnotation); 
//set document header depending on requested content. 
$export->outputHeaders($neoId); 
$export->generateAnnotatedText();
$export->outputAnnotations($annotation);
echo $export->outputContent(); 
//var_dump($export->outputContent());

?>