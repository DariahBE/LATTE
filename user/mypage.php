<?php
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR."/includes/client.inc.php");
include_once(ROOT_DIR."/includes/user.inc.php");
include_once(ROOT_DIR."/includes/annotation.inc.php");
include_once(ROOT_DIR."/includes/wikidata_user_prefs.inc.php");
include_once(ROOT_DIR.'/includes/navbar.inc.php');


if(isset($_SESSION['userid'])){
  $user = new User($client);
  $annotation = new Annotation($client);
}else{
  header('Location: /user/login.php');
}

$preferences = new Wikidata_user($client);
$preferences->buildPreferences();

?>


<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>My user page.</title>
    <script src="/JS/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="/CSS/style_entities.css">
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">


  </head>
  <body class="bg-neutral-200 w-full">
  <?php
    $navbar = new Navbar(); 
    echo $navbar->nav;  
  ?>
    <div class="top navbar">

    </div>
    <div class="body container 2xl:w-1/2 xl:w-2/3 items-center m-auto">
      <div class="container data cols-3">
        <div class="col">
          <h2>My Annotations</h2>
          <div class="colDynamic">
            <?php
              //$annotation->loadPersonalAnnotations($user->myId);
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
      <div class="container">
        <div>
          <h3>Preferences</h3>
        </div>
        <div>
          <h4>Wikidata Preferences:</h4>
          <div id="wikidataPrefContainer">
            <p><span class='font-bold'>Labels: </span> If present in wikidata, select the properties to show:</p>
            <div id="chosenWDProperties">
              <?php
                echo $preferences->generateForm('properties');
              ?>              
            </div>
            <br>
            <p><span class='font-bold'>Links: </span> If present, a link to the following wikipedia portals are shown: </p>
            <div id="chosenWDLinks">
              <?php 
                echo $preferences->generateForm('links');
              ?>
            </div>
            <br>
            <p><span class='font-bold'>Stringmatching: </span> Match strings against Wikipedia titles in the following languages: </p>
            <div id="chosenStringMatches">
              <?php 
                echo $preferences->generateForm('titles');
              ?>
            </div>
          </div>

        </div>
      </div>
    </div>
  </body>
</html>
