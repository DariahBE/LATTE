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
$originEmail = '';     //emailadress used to sent the message.
$smtpUser = false;     //account use on the server to send the mail. set to false if there is no authentication method
$smtpPassword = false;     //password associated to the email adress. set to false if there is no authentication method
$smtpServer = '';
$smtpPort = 0;     //port of the SMTP server  //default = 25
$selfSignedCertificates = true;           //set to true for servers where a self-signed SSL-certificate is being used (see Bug info described here: https://github.com/PHPMailer/PHPMailer/issues/718 )



/*map your nodes:
  arrayKeys are the NodeType:
  For each nodeType you must define the allowed set of nodeProperties.
  Properties should be descriptive, do not simply declare an 'id' property as this is reserved for the database.
  Nodes are capitalized, properties aren't!
  Every label is followed by an array of properties: 
    - Human readable string: this string is used in the frontend.
    - Type of variable: string, float, int, bool, uri OR wikidata.
        - the wikidata datatype expects a Q-identifier and returns live data from an API call. 
    - Boolean: Unique Key: is the value unique for this type of nodes? 
    - Boolean: Visual Distinguishing: Is the value used in the dom to label the nodes. If the node does not have a visually distinguishable component, the nodelabel is used.
    - Boolean: Can the interface search on this? 
  
*/

$nodesDatamodel = array(
  //Variant Nodes are required to be encoded like this: 
  'Variant' => [                                                //'Variant' label is a required nodelabel in the current model! IS not allowed to change. 
    "variant" => ["Label", "string", false, true, true],        //'variant' proprety is a required property in the current model! IS not allowed to change.
    "remark" => ["Remark", "string", false, false, true]
  ],
  //Knowledgebase Nodes are required to be encoded like this:
  'See_Also' => [
    "partner" => ["Projectname", "string", false, false, true],
    "partner_uri" => ["Link", "uri", false, false, true]
  ],
  //A node that is chose as 'annotation'nodes is required, the model can be chosen freely. For the DH-workshop we use this as a template
  'Annotation' => [
    "starts" => ["Annotation Start", "int", false, false, false],
    "stops" => ["Annotation End", "int", false, false, false],
    "private" => ["Private Annotation", "bool", false, false, false],
    "note" => ["Note", "string", false, false, true],
    "extra" => ["Extra", "string", false, false, true], 
    "url" => ["Link", 'uri', false, false, false]
  ],
  //For the dh_demo workshop we set up the model for Text-nodes alreay: you are free to choose this 
  'Text' => [
    "text" => ["Text", "string", false, false, true],
    "title" =>['Title', 'string', false, false, true],
    "texid" => ["Text ID", "int", true, true, true],
    "language" => ["Document language", "string", false, false, true],
    "publication" => ["Publisher", "string", false, false, true],
    "date" => ["Publishing date", "string", false, false, false], 
    "url" => ["Link", "uri", false, false, false]
  ],
  /*
  'Place' => [
    "geoid" => ["Trismegistos Place ID", "int", false, false, true],
    "label" => ["Label", "string", false, true, true],
    "region" => ["Regionname", "string", false, false, true], 
    "wikidata" => ["Wikidata Label", "wikidata", false, false, false]
  ],
  'Organization' =>[
    "label" => ["Label", "string", false, true, true],
    "uid" => ["Label", "string", false, false, true],
    "wikidata" => ["Wikidata Label", "wikidata", false, false, false]
  ],
  'Person' => [
    "label" => ["Name", "string", false, true, true],
    "sex" =>["Gender", "string", false, false, true], 
    "wikidata" => ["Wikidata Label", 'wikidata', false, false, false]
  ],
  'Organization' => [
    "label" => ["Label", "string", false, false, true]
  ], 
  'Disease' => [
    "name" => ["Name", 'string', true, false, true], 
    "erradicated" => ["Erradicated", 'bool', false, false, true], 
    "wikidata" => ["Wikidata ID", 'wikidata', false, false, false]
  ]*/
);

//Filled in for the DH-demo: should match the names and properties you choose in your model. 
//what is the node used for Annotations: Should match a key used in your Nodesmodel:
$nodeAsAnnotation = 'Annotation'; 
//what is the property that indicates the startposition of an Annotation:
$annotationStart = 'starts';
//what is the property that indicates the endposition of an Annotation:
$annotationEnd = 'stops';
//What is the node label used for Text objects. Should match a Key used in your Nodesmodel.
$nodeAsText = 'Text';
$propertyContainingText = 'text';   //Which property holds the text to show on the screen and to annotate into?


//node properties that are protected by the application and automatically generated. 
$privateProperties = array('uid');

//////////////////////////////////////////////////////

//which nodes should the entitylinking tool look for in the database? Repeat the keys as they are in the
// config object above; Asign the color value to them you want to use in the DOM. The keys used in this 
//dictionary should match the names of entitytypes which are part of the researchproject!
$matchOnNodes = array(
  //'Person' => 'rgba(39, 123, 245, 0.6)',
  //'Place' => 'rgba(245, 178, 39, 0.6)',
  //'Event' => 'rgba(39, 245, 123, 0.6)',
  'Text' => 'rgba(28, 200, 28, 0.6)',
  'Annotation' => 'rgba(200, 28, 28, 0.6)', 
  //'Organization' => 'rgba(145,100,52,0.6)', 
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
  //'Person' => 'People',
  'See_Also' => 'External Links',
  'Variant' => 'Spelling variants',
  //'Place' => 'Places',
  'Text' => 'Texts',
  'Annotation' => 'Annotations',
  //'priv_user' => 'Users'
);

########## HOW TO DISPLAY PICKUP BY NER-TOOL: ############
$ner_color = 'rgba(94,94,94,0.6)';       //RGBA value or False!

########### SET DATA VISIBILITY FOR THE PUBLIC: ###############################
$textsPublic = True;            //  True/False; True = texts are publicly visible on the internet.
$entityPublic = True;          //  True/False; True = stable pages are publicly visible. 


######################

//provide the base URL of the website. This should match the pattern: http://example.com
$baseURI = 'http://demo.test';



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
// define("EDGEMODEL", $edgesDatamodel);
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
$pyenv = "C:/Workdir/MyApps/Python_VENV/LATTE_connector_demo/Scripts/python.exe";
//  The folder where the scripts are located in LATTE_connector
$scripts = "C:/Workdir/MyApps/Python_VENV/LATTE_connector_demo/hostfiles/";
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
