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
/*
$nodes = array(
  'Person' => array("perid", "mindate", "maxdate", "namid", "sex"),
  'Text' => array("texid", "text", "language"),
  'Place' => array("geoid", "name", "region"),
  'Variant' => array("variant", "remark"),
  'See_Also' => array("partner", "partner_id", "partner_uri"),
  'Annotation' => array("starts", "stops", "private", "uid", "note", "extra")
);*/

$nodesDatamodel = array(
  'Person' => [
    "perid" => ["Trismegistos Person ID", "int", true],
    "mindate" => ["Earliest attestation", "string", false],
    "maxdate" => ["Latest attestation", "string", false],
    "namid" => ["Trismegistos Name ID", "int", false],
    "sex" =>["Gender", "string", false]
  ],
  'Text' => [
    "texid" => ["Trismegistos Text ID", "int", true],
    "text" => ["Text", "string", false],
    "language" => ["Document language", "string", false]
  ],
  'Place' => [
    "geoid" => ["Trismegistos Place ID", "int", false],
    "name" => ["Placename", "string", false],
    "region" => ["Regionname", "string", false]
  ],
  'Variant' => [
    "variant" => ["Label", "string", false],
    "remark" => ["Remark", "string", false]
  ],
  'See_Also' => [
    "partner" => ["Projectname", "string", false],
    "partner_id" => ["External ID", "string", false],
    "partner_uri" => ["Link", "uri", false]
  ],
  'Annotation' => [
    "starts" => ["AnnotionStart", "int", false],
    "stops" => ["AnnotationEnd", "int", false],
    "private" => ["Private Annotation", "bool", false],
    "note" => ["Note", "string", false],
    "extra" => ["Extra", "int", false]
  ]
);

//////////////////////////////////////////////////////

//which nodes should the entitylinking tool look for in the database? Repeat the keys as they are in the
// config object above; Asign the color value to them you want to use in the DOM.
$matchOnNodes = array(
  'Person' => 'rgba(39, 123, 245, 0.6)',
  'Place' => 'rgba(245, 178, 39, 0.6)',
  'Event' => 'rgba(39, 245, 123, 0.6)',
  'Dog' => 'rgba(255, 255, 255, 0.6)'
);

//automatically fill out below config based on nodesDatamodel:
//$nodes = array_keys($nodesDatamodel);
$nodes = array();
foreach(array_keys($nodesDatamodel) as $node){
  $nodes[$node] = array_keys($nodesDatamodel[$node]);
}

/*set the primary keys for your nodes. If No primary key is set, the database will revert to using UUID.*/
$primaryKeys = array_map(function ($ar){
  foreach ($ar as $key => $value) {
    if($value[2]){
      return $key;
    }
  }
  return false;
}, $nodesDatamodel);

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
  'Person' => 'People',
  'See_Also' => 'External Links',
  'Variant' => 'Spelling variants',
  'Place' => 'Places',
  'Text' => 'Texts',
  'Annotation' => 'Annotations',
  'priv_user' => 'Users',
);
/*
$nodeKeys_translate  = array_map(function ($ar){
  foreach ($ar as $key => $value) {
    $nodeKeys_translate[];
    return $key[] = $value[1];
    if($value[2]){
      return $key;
    }
  }
}, $nodesDatamodel);

var_dump($nodeKeys_translate);
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
*/
########### WHICH ENGINE SHOULD BE USED TO DETECT THE LANGUAGE OF A GIVEN TEXT?
#                             spacy
#                             langid
$languageDetectionEngine =    'langid';
define("LANGEXTRACTOR", $languageDetectionEngine);
######################

//provide the base URL of the website. This should match the pattern: http://example.com
$baseURI = 'http://entitylinker.test';

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
//define("NODEKEYSTRANSLATIONS", $nodeKeys_translate);
define("NODEMODEL", $nodesDatamodel);
define("URI", $URI);
define("WEBURL", $baseURI);
define("PRIMARIES", $primaryKeys);

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
