<?php
header('Content-Type: application/json; charset=utf-8');

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR."\includes\getnode.inc.php");
include_once(ROOT_DIR."\includes\user.inc.php");
include_once(ROOT_DIR."\includes\csrf.inc.php");

if(isset($_SESSION["userid"])){
    $user = new User($client);
  }else{
    header("Location: /user/login.php?redir=/admin/index.php");
    die("redir required"); 
  }
  //only allow admins here; 
  $adminMode = False;
  if($user->myRole !== "Admin"){
    header("HTTP/1.0 403 Forbidden");
    die();
  }else{
    $adminMode = True;
  }

  //check CSRF
  function databaseReachable(){
    // TODO 
    /**
     * Checks if the database is properly configured and can be accessed by the tool.
     */
    return 'TODO';

    return False;
  }

  function pluginExists(){
    // TODO
    /**
     * Checks if the APOC plugin can  be used by the tool. 
     */
    return 'TODO';
  }

  function checkApacheConfig(){
    // TODO
    /**
     *              DEPENDENCIES IN APACHE:
     * GD  ==> needed for image functions (e.g.: imagetruecolor()  ===> https://stackoverflow.com/questions/4560996/call-to-undefined-function-imagecreatetruecolor-error-in-php-pchart)
     * Redirect ==> Needed for htacces redirects (e.g. 301 ) ===> ?? 
     * 
     */
  }

  function coreNodeCheck(){
    /**
     * Checks if the corenodes contains no nodes that are not part of the Nodesmodel constant
     */
    $good = True;
    foreach(CORENODES as $core => $v){
        if(!(array_key_exists($core, NODEMODEL))){
            $good = False;
        }
    }
    return $good; 
  }

  function correctAnnotationSet(){
    /**
     * Checks if the $nodeAsAnnotation derived constant value is part of the Nodesmodel constant
     */
    return array_key_exists(ANNONODE, NODEMODEL);
  }

  function annotationHasStart(){
    /**
     * Checks if the Annotation node's start definition is part of the Nodesmodel constant
     */
    if(array_key_exists(ANNONODE, NODEMODEL)){
        return array_key_exists(ANNOSTART, NODEMODEL[ANNONODE]);
    }else{
        return False;
    }
  }

  function annotationHasStop(){
    /**
     * Checks if the Annotation node's stop definition is part of the Nodesmodel constant
     */
    if(array_key_exists(ANNONODE, NODEMODEL)){
        return array_key_exists(ANNOSTOP, NODEMODEL[ANNONODE]);
    }else{
        return False;
    }
  }






  echo json_encode( array(
    'Database' => array(
        'connection' => databaseReachable(),
        'plugin' => pluginExists(),
    ),
    'Model' => array(
        'corenodes' => coreNodeCheck(),
    ),
    'Annotation' => array(
        'key_exists' => correctAnnotationSet(), 
        'start_exists' => annotationHasStart(),
        'stop_exists' => annotationHasStop(),
    ), 
    'Server' => array(
      'apache_modules_enabled' => checkApacheConfig(), 
    )
    
  ));
  die(); 

?>