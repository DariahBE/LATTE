<?php
/*GIVE THE PROJECT A NAME: */
$projectName = <insert project name>

/*Connect to the NEO4Jdatabase:*/
//$defaultdriver = 'neo4j';   //neo4j or bolt  // disabled!
$hostname =  '<name of the neo4J container>';    //where's the DB hosted
$hostport = <portnumber here>;           //Port used by the DB
$userName = '<username here>';
$userPaswrd = '<password here>';
$databaseName = '<dbname.db here>'; //database hosted on the graph DB instance.
$URI = "neo4j://$hostname:$hostport";
$latteConnector = '<containername>:<exposedport>';
/**
 *     CONFIGURATION OF THE EMAIL CONNECTION
 */
$mailprotocol = '<PROTOCOL>';          // 'SMTP' or ''.
$originEmail = '<sent-from email to be shown to recipients.>';     //emailadress used to sent the message.
$smtpUser = '<username of the smtp server>';     //account use on the server to send the mail. set to false if there is no authentication method
$smtpPassword = '<password of the smtp server>';     //password associated to the email adress. set to false if there is no authentication method
$smtpServer = '<SMTP server address>';
$smtpPort = <SMTP Port number>;     //port of the SMTP server  //default = 25
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
  '<Nodelabel>' => [
    '<propertyname>' => ['<Human readable string>', '<Type of variable>', '<Unique Key>', '<Visual Distinguishing>', '<Searchable>'],
    '<propertyname_2>' => ['<Human readable string>', '<Type of variable>', '<Unique Key>', '<Visual Distinguishing>', '<Searchable>'],
  ], 
  'Nodelabel_2' => [
    'propertyname' => ['Human readable string', 'Type of variable', 'Unique Key', 'Visual Distinguishing', 'Searchable'],
    'propertyname_2' => ['Human readable string', 'Type of variable', 'Unique Key', 'Visual Distinguishing', 'Searchable'],
  ]
);

//what is the node used for Annotations: Should match a key used in your Nodesmodel:
$nodeAsAnnotation = '<Nodelabel used for annotations>'; 
//what is the property that indicates the startposition of an Annotation:
$annotationStart = '<propertyname of annotation start points.>';
//what is the property that indicates the endposition of an Annotation:
$annotationEnd = '<propertyname of annotation end points.>';
//What is the node label used for Text objects. Should match a Key used in your Nodesmodel.
$nodeAsText = '<Nodelabel used for texts>';
$propertyContainingText = '<Property used for text in the Text node.>';   //Which property holds the text to show on the screen and to annotate into?

//node properties that are protected by the application and automatically generated. 
$privateProperties = array('uid');

//////////////////////////////////////////////////////

//which nodes should the entitylinking tool look for in the database? Repeat the keys as they are in the
// config object above; Asign the color value to them you want to use in the DOM. The keys used in this 
//dictionary should match the names of entitytypes which are part of the researchproject!
$matchOnNodes = array(
  '<Nodelabel>'=> '<rgba value for this node type: e.g. rbga(39,125,245,0.6)>',
  '<Nodelabel_2>'=> '<rgba value for this node type: e.g. rbga(125,39,245,0.6)>',
);

//automatically fill out below config based on nodesDatamodel:
//$nodes = array_keys($nodesDatamodel);
$nodes = array();
foreach(array_keys($nodesDatamodel) as $node){
  $nodes[$node] = array_keys($nodesDatamodel[$node]);
}

/*set the primary keys for your nodes. If No primary key is set, the database will revert to using UUID.*/
/*The UUID key is shortened as 'uid' */
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
  //TODO Consider returning UID in stead of false. 
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
);
$nodes_translate = array(
  'Person' => 'People',
  'See_Also' => 'External Links',
  'Variant' => 'Spelling variants',
  'Place' => 'Places',
  'Text' => 'Texts',
  'Annotation' => 'Annotations',
  'Dog' => 'Dogs',
  'priv_user' => 'Users'
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
define("SMTPPATCH", $selfSignedCertificates);
/*EntityExtractor*/
$extractor = 'local';                         //local or Base URL
define("ENTITYEXTRACTOR", $extractor);
define("NERCOLOR", $ner_color); 




########### LATTE CONNECTOR INTEGRATION: ###########
# Use Latte Connector (accepted values are: True or False)
$use_connector = True;
#which language detection model to use; currenly only langid supported. 
$languageDetectionEngine =    'langid';
#Leave constant definitions untouched!
define("LATTECONNECTOR", $use_connector); 
define("LANGEXTRACTOR", $languageDetectionEngine);
define("CONNECTORENDPOINT", $latteConnector);
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
