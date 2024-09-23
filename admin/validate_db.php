<?php
 //only allow admins here: 


  //base imports
  include_once($_SERVER["DOCUMENT_ROOT"]."/config/config.inc.php");
  include_once(ROOT_DIR."/includes/client.inc.php");
  include_once(ROOT_DIR."/includes/user.inc.php");
  include_once(ROOT_DIR."/includes/navbar.inc.php");
  include_once(ROOT_DIR."/includes/integrity.inc.php");
  include_once(ROOT_DIR."/includes/csrf.inc.php");
  //there must be a logged in  user; if no session is active, make them log in and redirect back here. 
  if(isset($_SESSION["userid"])){
    $user = new User($client);
  }else{
    header("Location: /user/login.php?redir=/admin/index.php");
    die("redir required"); 
  }
  //only allow admins here; 
  if($user->myRole !== "Admin"){
    header("HTTP/1.0 403 Forbidden");
    die("Insufficient rights, forbidden access");
  }

    //only allow admins here; 
    $adminMode = False;
    if($user->myRole !== "Admin"){
      header("HTTP/1.0 403 Forbidden");
      die("Insufficient rights, forbidden access");
    }else{
      $adminMode = True;
    }

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title><?php echo PROJECTNAME ?></title>
    <script src="/JS/jquery-3.6.0.min.js"></script>
    <script src="/admin/JS/validation.js"></script>
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  </head>
  <body class="bg-neutral-200 w-full">
  <?php
    $navbar = new Navbar($adminMode); 
    echo $navbar->getNav();
    $integrity = new Integrity($client);
    $tokenManager = new CsrfTokenManager();
    $token = $tokenManager->generateToken(); 
  ?>

  <?php
    include_once('admin_tasks.php');
  ?>




  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 gap-4">
    <div class=" margin-auto p-4">
      <h3>Inconsistent Nodes</h3>
      <ul>
      <?php
        $results = $integrity->checkNodesNotMatchingModel();
        foreach($results as $key => $value){
          echo '<li>'.$key.' ('.$value.') <button type="button" onclick="dropnodefromdb(\''.$key.'\', \''.$token.'\')"> Fix </button> </li>';
        }
      ?>
      </ul>
    </div>
    <div class=" p-4">
      <h3>Nodes missing UUID</h3>
        <?php
          $results = $integrity->checkNodesWithoutUUID();
          if($results == 0){
            $message = "No nodes with missing UUID property found.";
          }else{
            $n = ($results == 1) ? '' : 's';
            $message = "The UUID property is missing from $results node$n. <button type='button' onclick='patchUUID(\"{$token}\")'> Fix </button> </li>";
          }
          echo "<p>$message</p>";
        ?>
    </div>
    <div class=" p-4">

    </div>
    <div class=" p-4">

    </div>
  </div>




  <div class='container'>
    <div>

    </div>
    <div>
      
    </div>

  </div>
  </body>
</html>