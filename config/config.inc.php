<?php
/*GIVE THE PROJECT A NAME: */
$projectName = 'ProjectName';

/*Connect to the NEO4Jdatabase:*/
$defaultdriver = 'neo4j';   //neo4j or bolt
$hostname = 'localhost';    //where's the DB hosted
$hostport = 7687;           //Port used by the DB
$userName = '';
$userPaswrd = '';
$databaseName = 'people.db'; //database hosted on the graph DB instance.
$URI = 'neo4j://localhost:7687';


/*map your nodes:
  arrayKeys are the NodeType:
  For each nodeType you must define the allowed set of nodeProperties.
  Properties should be descriptive, do not simply declare an 'id' property as this is reserved for the database.
  Nodes are capitalized, properties aren't!
*/
$nodes = array(
  'Person' => array("perid", "mindate", "maxdate", "namid", "sex"),
  'Text' => array("texid", "text", "language"),
  'Place' => array("geoid", "name", "region")
);

/*Provide an optional translation for edges.
Edgelabels may use another name in the database than in the tool's GUI.
This allows for more intuitive names.
*/
$edges_translate = array(
  'same_as' => 'Variant',
  'see_also' => 'Knowledgebase relations',
  'resides_in' => 'Lives in',
  'knows' => 'Knows'
);
$nodes_translate = array(
  'See_Also' => 'Knowledgebase record',
  'Variant' => 'Synonym'
);
$nodeKeys_translate = array(
  'Person' => array(
    'perid' => 'Trismegistos Person ID',
    'mindate' => 'Earliest attestation',
    'maxdate' => 'Latest attestation',
    'namid' => 'Trismegistos Nam ID',
    'sex' => 'Gender'
  ),
  'Text' => array(
    'texid' => 'Trismegistos Text ID',
    'text' => 'Textcontent',
    'language' => 'Language'
  ),
  'Place' => array(
    'geoid' => 'Trismegistos Geo ID',
    'name' => 'Placename',
    'region' => 'Trismegistos region'
  ),
  'See_Also' => array(
    '' => '',
  ),
  'Variant' => array(
    '' => '',
  )
);

########### WHICH ENGINE SHOULD BE USED TO DETECT THE LANGUAGE OF A GIVEN TEXT?
#                             spacy
#                             langid
$languageDetectionEngine =    'langid';
define("LANGEXTRACTOR", $languageDetectionEngine);
######################


/*Make constants*/
//// IDEA:
//all variables should be verified before defining as a constant!
define("PROJECTNAME", $projectName);
define("DBNAME", $databaseName);
define("USERNAME", $userName);
define("PASSWORD", $userPaswrd);
define("HOSTNAME", $hostname);
define("HOSTPORT", $hostport);
define("DEFAULTDRIVER", $defaultdriver);
define("NODES", $nodes);
define("EDGETRANSLATIONS", $edges_translate);
define("NODETRANSLATIONS", $nodes_translate);
define("NODEKEYSTRANSLATIONS", $nodeKeys_translate);
define("URI", $URI);

/*EntityExtractor*/
$extractor = 'local';                         //local or Base URL
define("ENTITYEXTRACTOR", $extractor);

/*PYTHON ENVIRONMENT:*/
$pyenv = "C:/Users/u0118112/AppData/Local/Programs/Python/Python310/python.exe";
$scripts = "C:/Users/u0118112/OneDrive - KU Leuven/DARIAH/2021 - 2025/webDevelopment/V1/host_scripts/";
//$scripts = "C:/xampp/";
define("PYTHON", $pyenv);
define("SCRIPTROOT", $scripts);

/*system environment: where's the default folder.*/
define( 'ROOT_DIR', $_SERVER["DOCUMENT_ROOT"] );

/*Registration set up:
  0 = closed: no new registrations possible.
  1 = invite only.
  2 = open: public frontend will allow users to regiser.
*/

$registration_policy = 0;
define('REGISTRATIONPOLICY', $registration_policy);


?>
