<?php
header('Content-Type: application/json; charset=utf-8');

include_once(dirname(__DIR__, 2) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/client.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');

$user = new User($client);
$user_id = $user->checkSession();
if (!$user_id) {
    header("Location: " . BASE_URL . "/user/login.php?redir=" . urlencode(BASE_URL . "/admin/index.php"));
    exit("Redirect required"); 
}
// Only allow admins here: 
if (!$user->isAdmin()) {
    header("HTTP/1.0 403 Forbidden");
    exit();
}
$adminMode = true;

  //most of this is in the onboarding tool!
  //check CSRF
  function databaseReachable(){
    /**
     * Checks if the database is properly configured and can be accessed by the tool.
     */
    global $client;
    try {
      $result = $client->run("RETURN 1 AS test");
      // var_dump($result[0]['test']);
      //code...
      return boolval($result[0]['test']);
    } catch (\Throwable $th) {
      //throw $th;
      return False;
    }
    return False;
  }

  function pluginExists(){
    /**
     * Checks if the APOC plugin can  be used by the tool. 
     */
    global $client; 
    try {
      $result = $client->run('RETURN apoc.version() AS v;');
      return $result[0]['v'];
    } catch (\Throwable $th){
      return False;
    }
    return False;

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
    )    
  ));
  die(); 

?>