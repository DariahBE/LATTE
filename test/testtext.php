<?php
//temporary file - delete when done.


header('Content-Type: application/json; charset=utf-8');

include_once("../config/config.inc.php");
include_once(ROOT_DIR."\includes\getnode.inc.php");
include_once(ROOT_DIR."\includes\annotation.inc.php");
include_once(ROOT_DIR."\includes\user.inc.php");


$texid = (int)$_GET['texid'];
$node = new Node($client);
$annotation = new Annotation($client);
$user = new User($client);


/*
$annotation->createAnnotation(78, 1561, 1568, 'c42c4c15-b546-46c5-bdc5-23ea20c7c628','85541469-4a69-4732-b8fa-7e8e32487225');
$annotation->createAnnotation(78, 211, 218, 'c42c4c15-b546-46c5-bdc5-23ea20c7c628','85541469-4a69-4732-b8fa-7e8e32487225');
$annotation->createAnnotation(78, 1954, 1962, 'c42c4c15-b546-46c5-bdc5-23ea20c7c628','de47c6e6-934a-41e8-a473-8e8c156af4a1', true);
$annotation->createAnnotation(78, 651, 659, 'c42c4c15-b546-46c5-bdc5-23ea20c7c628','de47c6e6-934a-41e8-a473-8e8c156af4a1');
$annotation->createAnnotation(78, 475, 484, 'd6576386-d819-413c-b01f-7fcc10a10149','bf3861df-78ef-4b2e-b0c3-3e639287a7ae');
*/



$data = $annotation->getExistingAnnotationsInText(78, 'c42c4c15-b546-46c5-bdc5-23ea20c7c628');

echo json_encode($data);
?>
