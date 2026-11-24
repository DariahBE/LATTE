<?php
include_once(dirname(__DIR__) . '/config/config.inc.php');
include_once(ROOT_DIR . "/includes/client.inc.php");
include_once(ROOT_DIR . '/includes/navbar.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');
include_once(ROOT_DIR . '/includes/nodes_extend_cud.inc.php');
include_once(ROOT_DIR . '/includes/buildform.inc.php');
// Edit portal is only open for registered users: 
// SO: check user registration.
$user = new User($client);
$user_id = $user->checkSession();
if($user_id === false){
  header("Location: " . BASE_URL . "/user/login.php");
  exit("Redirect required"); 
}

$tokenManager = new CsrfTokenManager(); 
$token = $tokenManager->generateToken();

// Referrer: 
$ref = false;
if(isset($_SERVER['HTTP_REFERER'])){
  $ref = $_SERVER['HTTP_REFERER']; 
}


// Get the node ID: 
if(isset($_GET['id'])){
  $id = (int)$_GET['id']; 
}else{
  header("Location: " . BASE_URL . "/index.php");
  exit("Redirect required"); 
}
// With the node ID get the data from the DB.
$node = new Node($client);
$requestedNode = $node->fetchRawEtById($id); 
$requestedNodeLabel = $requestedNode['label'];

// Verify edit permissions for this node.
$crudNode = new CUDNode($client);
$rights = $crudNode->determineRightsSet($user, $id);
if (!$rights['edit']) {
  http_response_code(403);
  die('You do not have permission to edit this node.');
}

// With label and properties known for the node, get the model as defined in config file. 
// Restrict edit to entity types in CORENODES constant.
$model = false; 
if(array_key_exists($requestedNodeLabel, CORENODES)){
  $model = NODEMODEL[$requestedNodeLabel]; 
}
// Don't edit text property of text nodes.
if($requestedNodeLabel === TEXNODE){
  unset($model[TEXNODETEXT]);
}
// Remove start and stop properties from the annotation node: 
if($requestedNodeLabel === ANNONODE){
  unset($model[ANNOSTART]);
  unset($model[ANNOSTOP]);
}

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/CSS/stylePublic.css">
    <script src="<?php echo BASE_URL; ?>/JS/jquery-3.6.0.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/JS/validation.js"></script>
    <?php
      include_once(ROOT_DIR . '/includes/matomo.inc.php');
      echo renderMatomoTracking();
    ?>
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
          <h3 class='w-full'>Type: <?php echo htmlspecialchars((string)$requestedNodeLabel, ENT_QUOTES, 'UTF-8'); ?></h3>
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
  validator.set_autofetch_to(false); 
  validator.pickup();

  const ref = <?php echo json_encode($ref, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
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
