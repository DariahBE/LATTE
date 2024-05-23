<?php
/*GIVE THE PROJECT A NAME: */
$projectName = 'MOVIE data';

/*Connect to the NEO4Jdatabase:*/
//$defaultdriver = 'neo4j';   //neo4j or bolt  // disabled!
$hostname = 'localhost';    //where's the DB hosted
$hostport = 7688;           //Port used by the DB
$userName = 'neo4j';
$userPaswrd = 'password';
$databaseName = 'dataset2.db'; //database hosted on the graph DB instance.
$URI = 'neo4j://localhost:'.$hostport;

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
    - Type of variable: string, uri, float, int, bool OR wikidata.
        - the wikidata datatype expects a Q-identifier and returns live data from an API call. 
    - Boolean: Unique Key: is the value unique for this type of nodes? 
    - Boolean: Visual Distinguishing: Is the value used in the dom to label the nodes. If the node does not have a visually distinguishable component, the nodelabel is used.
    - Boolean: Can the interface search on this? 
  
*/

$nodesDatamodel = array(
  'Actor' => [
    "name" => ["Name", "string", false, true, true],
    "gender" =>["Gender", "string", false, false, true], 
    "wiki_id" => ["Wikidata Label", 'wikidata', false, false, false],
    "nationality" => ["Nationality", 'nationality', false, false, true],
  ],
  'Movie' => [
    "imdb_id" => ["IMDB ID", "int", true, true, true],
    "language" => ["Language", "string", false, false, true],
    "director" => ["Director", "string", false, false, true]
  ],
  'Variant' => [                                                //'Variant' label is a required nodelabel in the current model! IS not allowed to change. 
    "variant" => ["Label", "string", false, true, true],        //'variant' proprety is a required property in the current model! IS not allowed to change.
    "remark" => ["Remark", "string", false, false, true]
  ],
  'See_Also' => [
    "partner" => ["Projectname", "string", false, false, true],
    "partner_uri" => ["Link", "uri", false, false, true]
  ],
  'Reference' => [
    "selstart" => ["Annotation Start", "int", false, false, false],
    "selstop" => ["Annotation End", "int", false, false, false],
    "private" => ["Private Annotation", "bool", false, false, false],
    "note" => ["Note", "string", false, false, true],
    "extra" => ["Extra", "string", false, false, true], 
    "url" => ["Link", 'uri', false, false, false],
    "cid" => ["comment identifier", 'string', true, false, false]
  ],
  'Full' =>[      //testing all datatypes:
    "nr" => ['ID', 'int', true, false, true], 
    "minrating" => ['Lowest score', 'float', false, false, true], 
    "maxrating" => ['Highest score', 'float', false, false, true], 
    "validated" => ['Validated', 'bool', false, false, true], 
    "wiki_id" => ['Wikdata ID', 'wikidata', true, false, true], 
    "name" => ['Name', 'string', true, false, true], 
    "link" => ['Link', 'uri', false, false, true]
  ], 
  'Article' => [
    "article_id" => ['ID', 'int', true, true, true], 
    "title" => ['Title', 'string', true, false, false], 
    "article" => ['Article', 'string', false, false, false], 
    "source" => ['Source', 'string', false, false, false]
  ], 
  'Character' => [
    "name" => ['Full Name', 'string', true, true, true], 
    "dies" => ['Dies', 'bool', false, false, true]
  ]
);

//what is the node used for Annotations: Should match a key used in your Nodesmodel:
$nodeAsAnnotation = 'Reference'; 
//what is the property that indicates the startposition of an Annotation:
$annotationStart = 'selstart';
//what is the property that indicates the endposition of an Annotation:
$annotationEnd = 'selstop';
//What is the node label used for Text objects. Should match a Key used in your Nodesmodel.
$nodeAsText = 'Article';
$propertyContainingText = 'article';   //Which property holds the text to show on the screen and to annotate into?



//node properties that are protected by the application and automatically generated. 
$privateProperties = array('uid');

//////////////////////////////////////////////////////

//which nodes should the entitylinking tool look for in the database? Repeat the keys as they are in the
// config object above; Asign the color value to them you want to use in the DOM. The keys used in this 
//dictionary should match the names of entitytypes which are part of the researchproject!
$matchOnNodes = array(
  'Actor' => 'rgba(39, 123, 245, 0.6)',
  'Movie' => 'rgba(245, 178, 39, 0.6)',
  'Article' => 'rgba(28, 200, 28, 0.6)',
  'Reference' => 'rgba(200, 28, 28, 0.6)', 
  'Full' => 'rgba(200,200,20,0.6)', 
  'Character' => 'rgba(250, 0, 250, 0.6)'
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
  'Actor' => 'Actor',
  'See_Also' => 'External Links',
  'Variant' => 'Spelling variants',
  'Movie' => 'Movie',
  'Article' => 'Articles',
  'Reference' => 'Annotations',
  // 'priv_user' => 'Users'
);

########## HOW TO DISPLAY PICKUP BY NER-TOOL: ############
$ner_color = 'rgba(94,94,94,0.6)';       //RGBA value or False!

########### SET DATA VISIBILITY FOR THE PUBLIC: ###############################
$textsPublic = True;            //  True/False; True = texts are publicly visible on the internet.
$entityPublic = True;          //  True/False; True = stable pages are publicly visible. 


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
//define("EDGEMODEL", $edgesDatamodel);
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




########### LATTE CONNECTOR INTEGRATION: ###########
# Use Latte Connector (accepted values are: True or False)
$use_connector = True;
/*SECTION: LATTE WEB APP: PYTHON ENVIRONMENT:*/
//  Your virtual environment used for the LATTE_connector
$pyenv = "C:/Workdir/MyApps/Python_VENV/LATTE_connector/Scripts/python.exe";
//  The folder where the scripts are located in LATTE_connector
$scripts = "C:/Workdir/MyApps/Python_VENV/LATTE_connector/hostfiles/";
#which language detection model to use; currenly only langid supported. 
$languageDetectionEngine =    'langid';
define("LATTECONNECTOR", $use_connector); 
define("LANGEXTRACTOR", $languageDetectionEngine);
define("PYTHON", $pyenv);
define("SCRIPTROOT", $scripts);
######################################################




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
