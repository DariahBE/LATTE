<?php
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR."/includes/client.inc.php");
include_once(ROOT_DIR.'/includes/navbar.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/csrf.inc.php');
include_once(ROOT_DIR.'/includes/buildform.inc.php');
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

//referrer: 
$ref = false;
if(isset($_SERVER['HTTP_REFERER'])){
  $ref = $_SERVER['HTTP_REFERER']; 
}


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
//and :
//      do not allow users to edit the text property of text nodes!!
//      do not allow users to edit value that are used in unique keys! (API's depend on them)
//      do not allow users to edit UUID fields. 
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
            $form->setHTMLID('updateform');
            $form->setNodeType($requestedNodeLabel);
              //var_dump($requestedNode);
            foreach($model as $key => $value){
              //key = name used in NEO4J
              //value = properties of the KEY: 
              if(array_key_exists($key, $requestedNode['properties'])){
                //if the node has the key property: extract the value from it
                $dbvalue = $requestedNode['properties'][$key][1]; 
              }else{
                //if the node doesn't have the key property; set the value to null. 
                $dbvalue = null; 
              }
              $form->add_element($key, $value, $dbvalue, $border='border-solid border-2 border-grey rounded-md', $fill = 'bg-white-200'); 
            }
            $form->generateHiddenToken('csrf_token', $token);
            $form->generateNodeFieldattributes($requestedNodeLabel, $id); 
            $form->addSubmitButton($submitID='custom_submit'); 
            echo $form->renderForm();
          ?>
      </div>





      <?php
        }
      ?>

    </div>
  </body>
  <script>
  //validation of input fields!
  let validator = new Validator;
  validator.pickup();

  const ref = "<?php echo htmlspecialchars($ref) ?>";
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('updateform');
    //const responseDiv = document.getElementById('response');

    form.addEventListener('submit', function(event) {
        // Prevent the default form submission
        event.preventDefault(); 

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            let submitButton = document.getElementById('custom_submit');
            if(data['success']){
              submitButton.remove();
              //go back to the page that brought you on the edit portal:
              if (ref!== ''){
                window.location.href = ref;
              }else{
                //if referrer is not working: use the window state. 
                history.back(); 
              }
            }else{
              submitButton.innerHTML = "Update failed";
              submitButton.classList.add('bg-red-500');
              submitButton.classList.remove('bg-green-500'); 
              submitButton.classList.remove('hover:bg-green-600'); 
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
  });



  </script>
</html>
