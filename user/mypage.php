<?php
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR."/includes/client.inc.php");
include_once(ROOT_DIR."/includes/user.inc.php");
include_once(ROOT_DIR."/includes/annotation.inc.php");

if(isset($_SESSION)){
  $user = new User($client);
  $annotation = new Annotation($client);
}else{
  header('Location: /user/login.php');
}

?>


<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>My user page.</title>
  </head>
  <body>
    <div class="body container">
      <div class="top navbar">

      </div>
      <div class="container data cols-3">
        <div class="col">
          <h2>My Annotations</h2>
          <div class="colDynamic">
            <?php
              $annotation->loadPersonalAnnotations($user->myId);
            ?>
          </div>
        </div>

        <div class="coll">
          <h2>empty</h2>

        </div>

        <div class="coll">
          <h2>empty</h2>

        </div>

      </div>
    </div>
  </body>
</html>
