<?php
/*GIVE THE PROJECT A NAME: */
$projectName = 'ProjectName';

/*Connect to the NEO4Jdatabase:*/
$defaultdriver = 'neo4j';   //neo4j or bolt
$hostname = 'localhost';    //where's the DB hosted
$hostport = 7687;           //Port used by the DB
$userName = '';
$userPaswrd = '';
$databaseName = 'hipe.db'; //database hosted on the graph DB instance.
$URI = 'neo4j://localhost:7687';


/*map your nodes:
  arrayKeys are the NodeType:
  For each nodeType you must define the allowed set of nodeProperties.
  Properties should be descriptive, do not simply declare an 'id' property as this is reserved for the database.
  Nodes are capitalized, properties aren't!
  Every label is followed by an array of properties: 
    - Human readable string: this string is used in the frontend.
    - Type of variable: string, int or bool
    - Boolean: Primary Key: is the value unique for this type of nodes? 
    - Boolean: Visual Distinguishing: Is the value used in the dom to label the nodes. If the node does not have a visually distinguishable component, the nodelabel is used.
*/

$nodesDatamodel = array(
  'Person' => [
    "label" => ["Wikidata Label", "string", false, True],
    "sex" =>["Gender", "string", false, false]
  ],
  'Text' => [
    "texid" => ["Text ID", "int", true, true],
    "text" => ["Text", "string", false, false],
    "language" => ["Document language", "string", false, false]
  ],
  'Place' => [
    "geoid" => ["Trismegistos Place ID", "int", false, false],
    "label" => ["Wikidata Label", "string", false, true],
    "region" => ["Regionname", "string", false, false]
  ],
  'Variant' => [
    "variant" => ["Label", "string", false, true],
    "remark" => ["Remark", "string", false, false]
  ],
  'See_Also' => [
    "partner" => ["Projectname", "string", false, false],
    "partner_id" => ["External ID", "string", false, true],
    "partner_uri" => ["Link", "uri", false, false]
  ],
  'Annotation' => [
    "starts" => ["AnnotionStart", "int", false, false],
    "stops" => ["AnnotationEnd", "int", false, false],
    "private" => ["Private Annotation", "bool", false, false],
    "note" => ["Note", "string", false, false],
    "extra" => ["Extra", "int", false, false]
  ],
  'Dog' => [
    "breed" => ["Breed", "string", false, false],
    "age" => ["Age", "int", false, false],
    "label" => ["Name", "string", false, true]
  ]
);

//////////////////////////////////////////////////////

//which nodes should the entitylinking tool look for in the database? Repeat the keys as they are in the
// config object above; Asign the color value to them you want to use in the DOM.
$matchOnNodes = array(
  'Person' => 'rgba(39, 123, 245, 0.6)',
  'Place' => 'rgba(245, 178, 39, 0.6)',
  'Event' => 'rgba(39, 245, 123, 0.6)',
  'Dog' => 'rgba(255, 255, 255, 0.6)', 
  'Text' => 'rgba(28, 200, 28, 0.6)', 
  'Annotation' => 'rgba(200, 28, 28, 0.6)'
);

//automatically fill out below config based on nodesDatamodel:
//$nodes = array_keys($nodesDatamodel);
$nodes = array();
foreach(array_keys($nodesDatamodel) as $node){
  $nodes[$node] = array_keys($nodesDatamodel[$node]);
}

/*set the primary keys for your nodes. If No primary key is set, the database will revert to using UUID.*/
/*The UUID key is shortened as 'uid' */
// // BUG: HOW to get to defaulted uid key!!!?
$primaryKeys = array_map(function ($ar){
  /*
    You should have all the keys of nodesDatamodel here and default them to uid.
    only then let them be overridden!
  */
  foreach ($ar as $key => $value) {
    if($value[2]){
      return $key;
    }
  }
  //return 'uid';
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
  'priv_user' => 'Users'
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
define("CORENODES", $matchOnNodes);
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
