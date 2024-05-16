<?php
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR."/includes/client.inc.php");
include_once(ROOT_DIR.'/includes/navbar.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/csrf.inc.php');
include_once(ROOT_DIR.'/includes/buildform.inc.php');
//TODO pending integration
//edits are allowed to use the NEO4J ID. As long as the node exists, the ID does not change. 
//when committing changes check that the node still exists. Otherwise you're good. 

//Edit portal is only open for registered users: 
//  SO: check user registration
$user = new User($client);
$user_id = $user->checkSession();
if($user_id === false){
  header("Location: /user/login.php");
  die("redir required"); 
}

$tokenManager = new CsrfTokenManager(); 
$token = $tokenManager->generateToken();


//get the node ID: 
if(isset($_GET['id'])){
  $id = (int)$_GET['id']; 
}else{
  header("Location: /index.php");
  die("redir required"); 
}
// with the node ID get the data from the DB
$node = new Node($client);
$requestedNode = $node->fetchRawEtById($id); 
$requestedNodeLabel = $requestedNode['label'];
//with label and properties known for the node, get the model as defined in config file. 
//restrict edit to entitytypes in CORENODES constant
//and do not allow users to edit the text property of text nodes!!
var_dump($requestedNode);
$model = false; 
if(array_key_exists($requestedNodeLabel, CORENODES)){
  $model = NODEMODEL[$requestedNodeLabel]; 
}/*else{
  die('Node is not editable or does not exist.');
}*/
//don't edit text!! ==> remove it from editable features
if($requestedNodeLabel === TEXNODE){
  unset($model[TEXNODETEXT]);
}
//remove start and stop properties from the annotation node: 
if($requestedNodeLabel === ANNONODE){
  unset($model[ANNOSTART]);
  unset($model[ANNOSTOP]);
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
    <script src="/JS/validation.js"></script>
  </head>
  <body class="">
    <div>
      <?php
        $navbar = new Navbar(); 
        echo $navbar->getNav();
      ?>

    </div>
    <div class="container">
      <div class="row"> </div>
      <?php
      if ($model === false){
        echo "No editable node matches your request.";
      }else{
      ?>
      <div class='2xl:w-1/2 xl:w-2/3 items-center m-auto'>
        <div class='main py-4 my-4'>
          <h2 class='w-full'>Update node properties</h2>
          <h3 class='w-full'>Type: <?php echo $requestedNodeLabel;  ?></h3>
        </div>
          <?php
            $form = new FormGenerator('update_action.php');
              //var_dump($requestedNode);
            foreach($model as $key => $value){
              //key = name used in NEO4J
              //value = properties of the KEY: 
              //stuck on problem: the KEY matches the last item in the properties value array of $requestedNode!!
              //method isn't used anywhere else, maybe chage the method? .
              //TODO pass the written value from the DB
              //var_dump($value); 
              //echo '<br>';
              // 3rd argument:                          $requestedNode['properties'][$key]
              if(array_key_exists($key, $requestedNode['properties'])){
                //if the node has the key property: extract the value from it
                $dbvalue = $requestedNode['properties'][$key][1]; 
              }else{
                //if the node doesn't have the key property; set the value to null. 
                $dbvalue = null; 
              }
              $form->add_element($key, $value, $dbvalue); 
            }
            $form->generateHiddenToken('token', $token);
            $form->addSubmitButton(); 
            echo $form->renderForm();
          ?>
            <!--
              in here you need a form with pre-filled content,
              DO NOT show the start-stop content if the node is an ANNOTATION NODE    (OK)
              DO attach the validator again. 
              Attach a token to the form
              Send token with data to edit_action.php
              reshow the result. 
            -->


        

      </div>





      <?php
        //var_dump($model);
        /*foreach($requestedNode as $key=>$value){
          var_dump($value);
          echo'<br>';
          //look for the model properties: 
          var_dump($key);
          echo'<br>';
          var_dump($model); 
          echo '<br>';
          echo '<br>';
        }*/
      }

      //add the hidden csrf token to the form
      //echo "<input hidden readonly name='token' type='text' value='$token'>"; 
      $tokenManager->outputToken();

      ?>
    </div>
  </body>
</html>
