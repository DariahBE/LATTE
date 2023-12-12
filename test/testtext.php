<?php
//temporary file - delete when done.


header('Content-Type: application/json; charset=utf-8');

include_once("../config/config.inc.php");
include_once(ROOT_DIR."\includes\getnode.inc.php");
include_once(ROOT_DIR."\includes\annotation.inc.php");
include_once(ROOT_DIR."\includes\user.inc.php");
include_once(ROOT_DIR."\includes\mail.inc.php");
include_once(ROOT_DIR."\includes\datasilo.inc.php");
/*

$user = new User($client); 

$mail = 'frederic2_pietowski@3hotmail.com';
$name = 'fre';
$role = 'adm'; 
$password = ''; 


var_dump($user->createUser($mail, $name, $role)); 
*/

/*

//$texid = (int)$_GET['texid'];
$node = new Node($client);
$annotation = new Annotation($client);
$user = new User($client);
var_dump($user->autoIncrementControllableUserId() );

die();
$mail = new Mail(); 

$mail->setMessageContent('Hello world');
$mail->setRecipient('frederic.pietowski@kuleuven.be');
$mail->setSubjectOfMail('implementation test');
$mail->send();*/


/*
$silo = new Siloconnector($client); 
$silo->getNeighboursConnectedBy(1528); 
$arr = $silo->makeURIs('json'); 
*/

/*
$annotation->createAnnotation(78, 1561, 1568, 'c42c4c15-b546-46c5-bdc5-23ea20c7c628','85541469-4a69-4732-b8fa-7e8e32487225');
$annotation->createAnnotation(78, 211, 218, 'c42c4c15-b546-46c5-bdc5-23ea20c7c628','85541469-4a69-4732-b8fa-7e8e32487225');
$annotation->createAnnotation(78, 1954, 1962, 'c42c4c15-b546-46c5-bdc5-23ea20c7c628','de47c6e6-934a-41e8-a473-8e8c156af4a1', true);
$annotation->createAnnotation(78, 651, 659, 'c42c4c15-b546-46c5-bdc5-23ea20c7c628','de47c6e6-934a-41e8-a473-8e8c156af4a1');
$annotation->createAnnotation(78, 475, 484, 'd6576386-d819-413c-b01f-7fcc10a10149','bf3861df-78ef-4b2e-b0c3-3e639287a7ae');
*/


// test for quirrina: GEOID 5400 UID: bca33178-814e-4a01-a484-e3363c13b3c8
/*$annotation->createAnnotation(13704, 500, 506, 'd6576386-d819-413c-b01f-7fcc10a10149','bca33178-814e-4a01-a484-e3363c13b3c8');
$annotation->createAnnotation(13704, 615, 621, 'd6576386-d819-413c-b01f-7fcc10a10149','bca33178-814e-4a01-a484-e3363c13b3c8');
$annotation->createAnnotation(13704, 2017, 2022, 'd6576386-d819-413c-b01f-7fcc10a10149','bca33178-814e-4a01-a484-e3363c13b3c8');
$annotation->createAnnotation(13704, 3209, 3214, 'd6576386-d819-413c-b01f-7fcc10a10149','bca33178-814e-4a01-a484-e3363c13b3c8');
$annotation->createAnnotation(17006, 146, 153, 'd6576386-d819-413c-b01f-7fcc10a10149','bca33178-814e-4a01-a484-e3363c13b3c8');
$annotation->createAnnotation(18544, 1943, 1953, 'd6576386-d819-413c-b01f-7fcc10a10149','bca33178-814e-4a01-a484-e3363c13b3c8');
$annotation->createAnnotation(69913, 283, 298, 'c42c4c15-b546-46c5-bdc5-23ea20c7c628','bca33178-814e-4a01-a484-e3363c13b3c8');
$annotation->createAnnotation(78524, 56, 63, 'd6576386-d819-413c-b01f-7fcc10a10149','bca33178-814e-4a01-a484-e3363c13b3c8');
$annotation->createAnnotation(78524, 20, 27, 'd6576386-d819-413c-b01f-7fcc10a10149','bca33178-814e-4a01-a484-e3363c13b3c8');
$annotation->createAnnotation(78524, 135, 142, 'd6576386-d819-413c-b01f-7fcc10a10149','bca33178-814e-4a01-a484-e3363c13b3c8');
$annotation->createAnnotation(78524, 283, 290, 'd6576386-d819-413c-b01f-7fcc10a10149','bca33178-814e-4a01-a484-e3363c13b3c8');
$annotation->createAnnotation(15734, 12, 16, 'd6576386-d819-413c-b01f-7fcc10a10149','dab817ec-c2e4-4171-968c-a41c39563ca0');
$annotation->createAnnotation(15734, 145, 151, 'd6576386-d819-413c-b01f-7fcc10a10149','dab817ec-c2e4-4171-968c-a41c39563ca0');
$annotation->createAnnotation(15734, 182, 188, 'd6576386-d819-413c-b01f-7fcc10a10149','dab817ec-c2e4-4171-968c-a41c39563ca0');
$annotation->createAnnotation(15734, 326, 332, 'd6576386-d819-413c-b01f-7fcc10a10149','dab817ec-c2e4-4171-968c-a41c39563ca0');
$annotation->createAnnotation(15734, 884, 890, 'd6576386-d819-413c-b01f-7fcc10a10149','dab817ec-c2e4-4171-968c-a41c39563ca0');
$annotation->createAnnotation(15734, 967, 973, 'd6576386-d819-413c-b01f-7fcc10a10149','dab817ec-c2e4-4171-968c-a41c39563ca0');
$annotation->createAnnotation(15734, 1030, 1036, 'd6576386-d819-413c-b01f-7fcc10a10149','dab817ec-c2e4-4171-968c-a41c39563ca0');
$annotation->createAnnotation(15734, 1193, 1202, 'd6576386-d819-413c-b01f-7fcc10a10149','dab817ec-c2e4-4171-968c-a41c39563ca0');
$annotation->createAnnotation(15734, 1322, 1328, 'd6576386-d819-413c-b01f-7fcc10a10149','dab817ec-c2e4-4171-968c-a41c39563ca0');
$annotation->createAnnotation(15734, 5476, 5482, 'd6576386-d819-413c-b01f-7fcc10a10149','dab817ec-c2e4-4171-968c-a41c39563ca0');
$annotation->createAnnotation(15734, 12735, 12744, 'd6576386-d819-413c-b01f-7fcc10a10149','dab817ec-c2e4-4171-968c-a41c39563ca0');
$annotation->createAnnotation(15734, 20540, 20546, 'd6576386-d819-413c-b01f-7fcc10a10149','dab817ec-c2e4-4171-968c-a41c39563ca0');
$annotation->createAnnotation(15734, 20791, 20797, 'd6576386-d819-413c-b01f-7fcc10a10149','dab817ec-c2e4-4171-968c-a41c39563ca0');
$annotation->createAnnotation(15734, 20873, 20879, 'd6576386-d819-413c-b01f-7fcc10a10149','dab817ec-c2e4-4171-968c-a41c39563ca0');
$annotation->createAnnotation(15734, 20940, 20946, 'd6576386-d819-413c-b01f-7fcc10a10149','dab817ec-c2e4-4171-968c-a41c39563ca0');
$annotation->createAnnotation(15734, 28328, 28335, 'd6576386-d819-413c-b01f-7fcc10a10149','dab817ec-c2e4-4171-968c-a41c39563ca0');
$annotation->createAnnotation(15734, 39157, 39163, 'd6576386-d819-413c-b01f-7fcc10a10149','cea92872-e025-4b6e-a6fc-996295e7d049');
$annotation->createAnnotation(15734, 63615, 63623, 'd6576386-d819-413c-b01f-7fcc10a10149','071fa3da-eb55-4551-aceb-61970628d5fa');
$annotation->createAnnotation(15734, 39298, 39308, 'd6576386-d819-413c-b01f-7fcc10a10149','6e69895b-5177-4d51-b905-352bb0cf43d3');
$annotation->createAnnotation(15734, 45997, 46008, 'd6576386-d819-413c-b01f-7fcc10a10149','6e69895b-5177-4d51-b905-352bb0cf43d3');
$annotation->createAnnotation(15734, 50526, 50536, 'd6576386-d819-413c-b01f-7fcc10a10149','6e69895b-5177-4d51-b905-352bb0cf43d3');
*/

//$user->requestPasswordReset('someoneWhodoesnotexist@gmail.com'); 
/*
$annotation->createAnnotation(78, 1561, 1568, 'c42c4c15-b546-46c5-bdc5-23ea20c7c628','85541469-4a69-4732-b8fa-7e8e32487225');
$annotation->createAnnotation(78, 1561, 1568, 'c42c4c15-b546-46c5-bdc5-23ea20c7c628','85541469-4a69-4732-b8fa-7e8e32487225');
$annotation->createAnnotation(78, 1561, 1568, 'c42c4c15-b546-46c5-bdc5-23ea20c7c628','85541469-4a69-4732-b8fa-7e8e32487225');
*/
$annotation = new Annotation($client);
$connections = array(
    array(0,14), 
    array(97,110), 
    array(226,243),
    array(316,320),
    array(326,332),
    array(337,343),
    array(406,412),
    array(448,460),
    array(463,467),
    array(463,467),
    array(474,488),
    array(641,656),
    array(685,706),
    array(712,726),
    array(1563,1572),
    [1580, 1606], [3559, 3596], [4003, 4022], [4284, 4314], [4625, 4641], [5016, 5043], [5604, 5631], [6031, 6060], [6364, 6385], [6675, 6703], [6886, 6906], [7256, 7262], [7533, 7552], [8075, 8105], [8164, 8202], [8853, 8892], [9367, 9395], [9588, 9594], [9748, 9760], [10254, 10273], [10300, 10328], [10364, 10395], [10632, 10654], [11740, 11768], [11861, 11898], [12112, 12130], [12144, 12167], [12168, 12201], [13293, 13318], [13462, 13472], [14367, 14407], [14493, 14524], [14964, 14987], [15187, 15204], [16403, 16426], [16427, 16434], [16514, 16550], [16586, 16594], [16621, 16659]

); 
$annotation->createRecognizedAnnotation(7513, $connections);
die('exit');

$data = $annotation->createAnnotation(78, 21, 29, 'c42c4c15-b546-46c5-bdc5-23ea20c7c628', '120d4d51-9db4-437d-842e-dbde3fc869a9' );
$data = $annotation->getExistingAnnotationsInText(78, 'c42c4c15-b546-46c5-bdc5-23ea20c7c628');

echo json_encode($data);
?>


