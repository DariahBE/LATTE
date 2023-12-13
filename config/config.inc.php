<?php
/*GIVE THE PROJECT A NAME: */
$projectName = 'HIPE data';

/*Connect to the NEO4Jdatabase:*/
//$defaultdriver = 'neo4j';   //neo4j or bolt  // disabled!
$hostname = 'localhost';    //where's the DB hosted
$hostport = 7687;           //Port used by the DB
$userName = 'neo4j';
$userPaswrd = 'password';
$databaseName = 'hipe.db'; //database hosted on the graph DB instance.
$URI = 'neo4j://localhost:7687';

/**
 *     CONFIGURATION OF THE EMAIL CONNECTION
 */
$mailprotocol = 'SMTP';          // 'SMTP' or ''.
$originEmail = 'donotreply@trismegistos.org';     //emailadress used to sent the message.
$smtpUser = false;     //account use on the server to send the mail. set to false if there is no authentication method
$smtpPassword = false;     //password associated to the email adress. set to false if there is no authentication method
$smtpServer = 'smtp.kuleuven.be';
$smtpPort = 25;     //port of the SMTP server
$selfSignedCertificates = true;           //set to true for servers where a self-signed SSL-certificate is being used (see Bug info described here: https://github.com/PHPMailer/PHPMailer/issues/718 )



/*map your nodes:
  arrayKeys are the NodeType:
  For each nodeType you must define the allowed set of nodeProperties.
  Properties should be descriptive, do not simply declare an 'id' property as this is reserved for the database.
  Nodes are capitalized, properties aren't!
  Every label is followed by an array of properties: 
    - Human readable string: this string is used in the frontend.
    - Type of variable: string, longtext, int, bool OR wikidata.
        - the wikidata datatype expects a Q-identifier and returns live data from an API call. 
    - Boolean: Unique Key: is the value unique for this type of nodes? 
    - Boolean: Visual Distinguishing: Is the value used in the dom to label the nodes. If the node does not have a visually distinguishable component, the nodelabel is used.
    - Boolean: Can the interface search on this? 
  
*/

$nodesDatamodel = array(
  'Person' => [
    "label" => ["Name", "string", false, true, true],
    "sex" =>["Gender", "string", false, false, true], 
    "wikidata" => ["Wikidata Label", 'wikidata', false, false, false]
  ],
  'Text' => [
    "texid" => ["Text ID", "int", true, true, true],
    "text" => ["Text", "string", false, false, true],
    "language" => ["Document language", "string", false, false, true],
    "publication" => ["Publisher", "string", false, false, true],
    "place" => ["Publishing Place", "string", false, false, true]
  ],
  'Place' => [
    "geoid" => ["Trismegistos Place ID", "int", false, false, true],
    "label" => ["Label", "string", false, true, true],
    "region" => ["Regionname", "string", false, false, true], 
    "wikidata" => ["Wikidata Label", "wikidata", false, false, false]
  ],
  'Variant' => [                                                //'Variant' label is a required nodelabel in the current model!
    "variant" => ["Label", "string", false, true, true],        //'variant' proprety is a required property in the current model!
    "remark" => ["Remark", "string", false, false, true]
  ],
  'See_Also' => [
    "partner" => ["Projectname", "string", false, false, true],
    "partner_uri" => ["Link", "uri", false, false, true]
  ],
  'Annotation' => [
    "starts" => ["AnnotionStart", "int", false, false, false],
    "stops" => ["AnnotationEnd", "int", false, false, false],
    "private" => ["Private Annotation", "bool", false, false, false],
    "note" => ["Note", "longtext", false, false, true],
    "extra" => ["Extra", "string", false, false, true], 
    "url" => ["Link", 'uri', false, false, false]
  ],
  'Organization' =>[
    "label" => ["Label", "string", false, true, true],
    "uid" => ["Label", "string", false, false, true],
    "wikidata" => ["Wikidata Label", "wikidata", false, false, false]
  ]
  /*'Dog' => [
    "breed" => ["Breed", "string", false, false, true],
    "age" => ["Age", "int", false, false, false],
    "label" => ["Name", "string", false, true, true],
    "Wikidata" => ["Wikidata Label", 'wikidata', false, false, false],
    "smart" => ["Did tricks", 'bool', false, false, false],
  ], 
  'Organization' => [
    "label" => ["Label", "string", false, false, true]
  ], 
  'Test' =>[      //testing all datatypes:
    "id" => ['ID', 'int', true, false, true], 
    "minscore" => ['Lowest score', 'float', false, false, true], 
    "highscore" => ['Highest score', 'float', false, false, true], 
    "validated" => ['Validated', 'bool', false, false, true], 
    "wikidata" => ['Wikdata ID', 'wikidata', true, false, true], 
    "name" => ['Name', 'string', true, false, true], 
    "link" => ['Link', 'uri', true, false, true]
  ], 
  'Disease' => [
    "name" => ["Name", 'string', true, false, true], 
    "erradicated" => ["Erradicated", 'bool', false, false, true], 
    "wikidata" => ["Wikidata ID", 'wikidata', false, false, false]
  ]*/
);

//what is the node used for Annotations: Should match a key used in your Nodesmodel:
$nodeAsAnnotation = 'Annotation'; 
//what is the property that indicates the startposition of an Annotation:
$annotationStart = 'starts';
//what is the property that indicates the endposition of an Annotation:
$annotationEnd = 'stops';
//What is the node label used for Text objects. Should match a Key used in your Nodesmodel.
$nodeAsText = 'Text';
$propertyContainingText = 'text';   //Which property holds the text to show on the screen and to annotate into?




/**Feed the edges to the application: 
 * each key in the model is an edgename 
 * the value for each key is an array.
 * In that array the first two arguments are two separate arrays of nodes the edge connects.
 * The third argument is a boolean True/False is accepted here. If True the 
 * edge is directed (node1)->(node2) and goes from your first argument to your second argument
 * If False, the edge is not-directed and goes back and forth. (node1)--(node2)
 */
$edgesDatamodel = array(
  'contains' => [array('Text'), array('Annotation'), True],
  'references' => [array('Annotation'), array('Person', 'Dog', 'Place'), True],
  'same_as' => [array('Variant'), array('Person', 'Dog', 'Place'), True], 
  'see_also' => [array('Person', 'Dog', 'Place'), array('See_Also'), True]
);



//node properties that are protected by the application and automatically generated. 
$privateProperties = array('uid');

//////////////////////////////////////////////////////

//which nodes should the entitylinking tool look for in the database? Repeat the keys as they are in the
// config object above; Asign the color value to them you want to use in the DOM. The keys used in this 
//dictionary should match the names of entitytypes which are part of the researchproject!
$matchOnNodes = array(
  'Person' => 'rgba(39, 123, 245, 0.6)',
  'Place' => 'rgba(245, 178, 39, 0.6)',
  //'Event' => 'rgba(39, 245, 123, 0.6)',
  'Text' => 'rgba(28, 200, 28, 0.6)',
  'Annotation' => 'rgba(200, 28, 28, 0.6)', 
  'Organization' => 'rgba(145,100,52,0.6)'
  //'Disease' => 'rgba(145,100,52,0.6)'
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
  foreach ($ar as $key => $value){
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
// TODO: edge information should not be editable by user anyway,
//        Is it then really needed in the config file? 
*/
$edges_translate = array(
  'same_as' => 'Variant',
  'see_also' => 'Knowledgebase relations',
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

########## HOW TO DISPLAY PICKUP BY NER-TOOL: ############
$ner_color = 'rgba(200,150,0,0.6)';       //RGBA value or False!

########### SET DATA VISIBILITY FOR THE PUBLIC: ###############################
$textsPublic = False;            //  True/False; True = texts are publicly visible on the internet.
$entityPublic = False;          //  True/False; True = stable pages are publicly visible. 


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
//define("DEFAULTDRIVER", $defaultdriver);
define("NODES", $nodes);
define("EDGETRANSLATIONS", $edges_translate);
define("NODETRANSLATIONS", $nodes_translate);
define("CORENODES", $matchOnNodes);
//define("NODEKEYSTRANSLATIONS", $nodeKeys_translate);
define("NODEMODEL", $nodesDatamodel);
define("URI", $URI);
define("WEBURL", $baseURI);
define("PRIMARIES", $primaryKeys);
define("PRIVATEPROPERTIES", $privateProperties);
define("EDGEMODEL", $edgesDatamodel);
define("ANNOSTART", $annotationStart);
define("ANNOSTOP", $annotationEnd);
define("TEXNODE", $nodeAsText);
define("ANNONODE", $nodeAsAnnotation);
define("TEXNODETEXT", $propertyContainingText);
//accessibility for the public:
define("TEXTSAREPUBLIC", $textsPublic); 
define("ENTITIESAREPUBLIC", $entityPublic); 
//email config: 
define("PROTOCOL", $mailprotocol);
define("SMTPSERVERADR", $smtpServer);
define("SMTPPORT", $smtpPort);
define("SERVERORIGMAIL", $originEmail);
define("SMTPUSER", $smtpUser);
define("SMTPPASSWORD", $smtpPassword);
define("SMPTPPATCH", $selfSignedCertificates);
/*EntityExtractor*/
$extractor = 'local';                         //local or Base URL
define("ENTITYEXTRACTOR", $extractor);
define("NERCOLOR", $ner_color); 

/*PYTHON ENVIRONMENT:*/
$pyenv = "C:/Workdir/MyApps/Python/Python311/python.exe";
$scripts = "C:/Users/u0118112/OneDrive - KU Leuven/DARIAH/2021 - 2025/webDevelopment/V1/host_scripts/";

define("PYTHON", $pyenv);
define("SCRIPTROOT", $scripts);

/*system environment: where's the default folder.*/
define( 'ROOT_DIR', $_SERVER["DOCUMENT_ROOT"] );

/*Registration set up:
  0 = closed: no new registrations possible.
  1 = invite only.
  2 = open: public frontend will allow users to regiser.
*/
$registration_policy = 1;
define('REGISTRATIONPOLICY', $registration_policy);

?>
