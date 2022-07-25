<?php
//temporary file - delete when done.


header('Content-Type: application/json; charset=utf-8');

include_once("../config/config.inc.php");
include_once(ROOT_DIR."\includes\getnode.inc.php");
include_once(ROOT_DIR."\includes\annotation.inc.php");


$texid = (int)$_GET['texid'];
$node = new Node($client);
$annotation = new Annotation($client);

$annotation->createAnnotation(78, 431, 441, 'd6576386-d819-413c-b01f-7fcc10a10149', '1c3ec8c9-1203-49e9-9863-81eb64582d68', false);

$annotation->getExistingAnnotationsInText(78);



?>
