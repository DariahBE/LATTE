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
    <script src="/admin/JS/bulk_loader.js"></script>
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  </head>
  <body class="bg-neutral-200 w-full">
  <?php
    $navbar = new Navbar($adminMode); 
    echo $navbar->getNav();
    $integrity = new Integrity($client);
  ?>

  <?php
    include_once('admin_tasks.php');
  ?>

  <script>
    <?php echo 'const model = '.  json_encode(NODEMODEL[TEXNODE]);  ?>;
  </script>

  <div class='container'>
    <div>
        <h2>Bulk import text</h2>
        <p>Load a csv file with text and optional metadata into the database. </p>
    </div>

    <div id='drag_and_drop_container'>

    </div>
    <div id='progress_container'>

    </div>

  </div>

  <script>
    let csv_handler = new CSVHandler('drag_and_drop_container');
    csv_handler.setDatamodel(model); 
  </script>
  </body>
</html>