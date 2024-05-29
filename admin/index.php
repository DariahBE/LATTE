<?php
  //base imports
  include_once($_SERVER["DOCUMENT_ROOT"]."/config/config.inc.php");
  include_once(ROOT_DIR."/includes/client.inc.php");
  include_once(ROOT_DIR."/includes/user.inc.php");
  include_once(ROOT_DIR."/includes/navbar.inc.php");
  //there must be a logged in  user; if no session is active, make them log in and redirect back here. 
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
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  </head>
  <body class="bg-neutral-200 w-full">
  <?php
    $navbar = new Navbar($adminMode); 
    echo $navbar->getNav();
    include_once('admin_tasks.php');
  ?>
    <div class='container'>
      <h3 class='font-bold text-lg'>User alignment</h3> 
      <?php
        $user = new User($client);
        $userAlignment = $user->checkAlignment(); 
        if (boolval(count($userAlignment))){
          $classes = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative';
          $boxText = 'Check Failed!';
          $alignmentText = "One or more users did not pass the required alignment check. To fix this issue, please <a class='btn bg-red-200 rounded m-1 p-1' href='/admin/usermanagement/user_align.php'>align your users.</a>";
        }else{
          $classes = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4';
          $boxText = 'Passed!';
          $alignmentText = 'All users passed the alignment check, no further action required. ';
        }
      ?>
        <div class="lm-2 lp-2 <?php echo $classes; ?> z-10" role="alert">
          <strong class="font-bold"><?php echo $boxText; ?></strong>
          <span class="block sm:inline"><?php echo $alignmentText; ?></span>
        </div>

    </div>


  </body>
</html>