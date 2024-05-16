<?php
//TODO: temporary file - delete when done.


header('Content-Type: application/json; charset=utf-8');

include_once("../config/config.inc.php");
include_once(ROOT_DIR."\includes\getnode.inc.php");
include_once(ROOT_DIR."\includes\annotation.inc.php");
include_once(ROOT_DIR."\includes\user.inc.php");
include_once(ROOT_DIR."\includes\mail.inc.php");
include_once(ROOT_DIR."\includes\datasilo.inc.php");
include_once(ROOT_DIR."\includes\\nodes_extend_cud.inc.php"); 

$node = new CUDNode($client);
/*
echo 'CASE1: EXPECT NODE'; 
$node->testNewQuery(789, 9999); 
echo 'CASE2: EXPECT NODE'; 
$node->testNewQuery(1387, 9999); 
echo 'CASE3: EXPECT NODE'; 
$node->testNewQuery(5912, 9999); 
echo 'CASE4: #private floating node > EXPECT NULL'; 
$node->testNewQuery(3081, 2); 
echo 'CASE5: #private node, good user  > EXPECT NODE'; 
$node->testNewQuery(5911, '4a10bcc4-4677-495b-9f20-6b79f259335f'); 
echo 'CASE6 #private node, wrong user:  > EXPECT NULL'; 
$node->testNewQuery(5911, 2); */






var_dump($node->checkOwnershipOfNode(0, 1));


die('exit'); 
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
//TODO (critical): Test transaction implementation. 
$annotation->startTransaction(); 


var_dump($annotation->fetchAnnotationUUID(7727)); 



die('exit');
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
    [1580, 1612], [1649, 1673], [1761, 1801], [1849, 1884], [2122, 2149], [2216, 2227], [2228, 2235], [2348, 2366], [2515, 2529], [2567, 2601], [2641, 2670], [2671, 2682], [2695, 2715], [2844, 2876], [2886, 2919], [3028, 3042], [3093, 3131], [3132, 3138], [3225, 3249], [3250, 3255], [3355, 3379], [3380, 3398], [3478, 3517], [3554, 3587], [3588, 3602], [3613, 3631], [3632, 3657], [3658, 3694], [3695, 3729], [3736, 3757], [3788, 3814], [4002, 4026], [4072, 4079], [4151, 4190], [4331, 4370], [4371, 4383], [4444, 4456], [4457, 4490], [4491, 4503], [4570, 4601], [4681, 4696], [4697, 4710], [4747, 4757], [5104, 5122], [5486, 5496], [5535, 5546], [5587, 5612], [5671, 5681], [5775, 5814], [5851, 5859], [6004, 6042], [6267, 6284], [6319, 6325], [6326, 6363], [6493, 6503], [6504, 6510], [6599, 6604], [6681, 6703], [6704, 6735], [6759, 6785], [6797, 6814], [6815, 6823], [6949, 6954], [6955, 6974], [6975, 6984], [6985, 7022], [7225, 7240], [7241, 7252], [7300, 7332], [7355, 7391], [7392, 7414], [7698, 7708], [7874, 7902], [7974, 7981], [8029, 8055], [8056, 8069], [8363, 8398], [8399, 8426], [8595, 8629], [8740, 8765], [8890, 8919], [8991, 9014], [9204, 9243], [9300, 9332], [9412, 9431], [9432, 9447], [9597, 9633], [9634, 9640], [9712, 9736], [9824, 9834], [9854, 9865], [10382, 10401], [10463, 10492], [10556, 10576], [10577, 10616], [10832, 10852], [10903, 10939], [11066, 11077], [11078, 11093], [11162, 11189], [11246, 11268], [11308, 11344], [11434, 11439], [11491, 11501], [11550, 11577], [11637, 11652], [11693, 11717], [11718, 11746], [11917, 11925], [12031, 12062], [12234, 12263], [12615, 12652], [12690, 12703], [12796, 12821], [12834, 12869], [13031, 13050], [13051, 13087], [13164, 13180], [13340, 13345], [13346, 13376], [13377, 13399], [13400, 13431], [13440, 13470], [13471, 13500], [13693, 13720], [13721, 13759], [13850, 13885], [13886, 13909], [14062, 14094], [14115, 14141], [14178, 14207], [14238, 14274], [14292, 14321], [14339, 14374], [14407, 14436], [14467, 14493], [14494, 14505], [14595, 14620], [14642, 14660], [14695, 14718], [14719, 14754], [14857, 14891], [14892, 14931], [15055, 15067], [15068, 15104], [15336, 15370], [15816, 15837], [15838, 15863], [16091, 16114], [16115, 16128], [16203, 16224], [16225, 16247], [16270, 16282], [16402, 16423], [16471, 16479], [16480, 16511], [16645, 16668], [16926, 16941], [16942, 16953], [17024, 17059], [17168, 17192], [17193, 17201]

); 
try {
    $annotation->createRecognizedAnnotation(7513, $connections);
    $annotation->commitTransaction(); 
} catch (\Throwable $th) {
    $annotation->rollbackTransaction(); 
}
die('exit');


echo json_encode($data);
?>


