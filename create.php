<?php
//include_once('includes/getnode.inc.php');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/navbar.inc.php');



if(!(isset($_SESSION) && boolval($_SESSION['userid']))){
    //creating new data is only allowed if a user is logged in: 
    //die($user_uuid);
    $redir = '?redir=/create.php';
    header('Location: /user/login.php'.$redir);
    die();
}else{
  $user = new User($client);
  $adminMode = $user->myRole == 'Admin'; 
}

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title><?php echo PROJECTNAME ?></title>
    <script src="/JS/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="/CSS/style_entities.css">
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="/CSS/leaflet/leaflet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src = "/JS/creation.js"></script>
    <script src = "/JS/validation.js"></script>
  </head>
  <body class="bg-neutral-200 w-full">
    
    <?php
        $navbar = new Navbar($adminMode); 
        echo $navbar->nav;  
        //DO NOT add annotations in this environment (nodename: Annotation) !
        echo '<script>var core = '.json_encode(array_diff(array_keys(CORENODES), array('Annotation'))).';</script>';
    ?>
    <div class= "2xl:w-1/2 xl:w-2/3 items-center m-auto p-8">

      <h3 class="uppercase text-xl underline decoration-4 underline-offset-2">extend graph</h3>
      <div>
        <h4 class="text-lg p-2 m-2">Nodetype: </h4>
        <div class="p-4 m-4" id='formMessageBox'></div>
        <div class="p-4 m-4" id='nodeTypeSelection'>

        </div>
      </div>
      <hr>
      <div id='propertySection' class='hidden w-full'>
        <h4 class="text-lg p-2 m-2">Node properties: </h4>
        <div class="p-4 m-4" id='propertyInputSection'>

        </div>
      </div>
      <hr>
      <!--
      <div id='saveSection' class='hidden'>
        <h4 class="text-lg p-2 m-2">Save and review: </h4>
        <div class="p-4 m-4" id='saveConfirmation'>

        </div>
      </div>
      -->
    </div>

    <script>
    var creator = new nodeCreator(core); 
  </script>
  </body>
</html>