<?php
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR."/includes/client.inc.php");
include_once(ROOT_DIR.'/includes/navbar.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');

//edits are allowed to use the NEO4J ID. As long as the node exists, the ID does not change. 
//when committing changes check that the node still exists. Otherwise you're good. 

//Edit portal is only open for registered users: 
//  SO: check user registration
$user = new User($client);
$user_uuid = $user->checkSession();
if($user_uuid === false){
  header("Location: /user/login.php");
  die("redir required"); 
}

//get the node ID: 
$id = (int)$_GET['id']; 
// with the node ID get the data from the DB
$node = new Node($client);
$requestedNode = $node->fetchEtById($id); 
$requestedNodeLabel = $node->fetchLabelById($id);
//with label and properties known for the node, get the model as defined in config file. 
//restrict edit to entitytypes in CORENODES constant
//and do not allow users to edit the text property of text nodes!!
$model = false; 
if(array_key_exists($requestedNodeLabel, CORENODES)){
  $model = NODEMODEL[$requestedNodeLabel]; 
}else{
  die('Node is not editable.');
}
//don't edit text!! ==> remove it from editable features
if($requestedNodeLabel === TEXNODE){
  unset($model[TEXNODETEXT]);
}


//Once the user is detected, make sure that the user has the rights to edit this node: 
//$allowedToEdit = $user->hasEditRights($user->myRole, $user->myName === $owner);

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title><?php echo PROJECTNAME;?></title>
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <script src="/JS/jquery-3.6.0.min.js"></script>
    <script src="/JS/validator.js"></script>
  </head>
  <body class="">
    <div>
      <?php
        $navbar = new Navbar(); 
        echo $navbar->nav; 
      ?>

    </div>
    <div class="container">
      <div class="row"> </div>
      <h2>Update node properties</h2>
      <?php
        //var_dump($requestedNode);
        foreach($requestedNode as $key=>$value){
          var_dump($value);
          echo'<br>';
          //look for the model properties: 
          var_dump($key);
          echo'<br>';
          var_dump($model); 
          echo '<br>';
          echo '<br>';
        }
      ?>
    </div>
  </body>
</html>
