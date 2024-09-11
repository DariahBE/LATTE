<?php
  include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
  include_once(ROOT_DIR.'/includes/getnode.inc.php');
  //include_once(ROOT_DIR.'/includes/entity.views.inc.donotuse.php');
  include_once(ROOT_DIR.'/includes/navbar.inc.php');
  include_once(ROOT_DIR.'/includes/user.inc.php');
  
$user = new User($client);
if((isset($_SESSION) && isset($_SESSION['userid']) && boolval($_SESSION['userid']))){
  $adminMode = $user->myRole == 'Admin'; 
}else{
  $adminMode = False;
}

//check access policy: endpoint should die() when data is not configured to be openly accessible.
//both text and entities have to be public, otherwise die()
$user->checkAccess(TEXTSAREPUBLIC &&  ENTITIESAREPUBLIC);

$offset = 0; 
$limit = 20; 
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title><?php echo PROJECTNAME?>: Search</title>
    <link rel="stylesheet" href="/CSS/style_entities.css">
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script
        src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
        crossorigin="anonymous">
    </script>

    <script type="text/javascript" src="/JS/validation.js"> </script>
    <script type="text/javascript" src="/JS/search.js"> </script>
  </head>
    <body class="bg-neutral-200 w-full">
    <?php
        $navbar = new Navbar($adminMode); 
        echo $navbar->getNav();
    ?>

      <div class= "2xl:w-1/2 xl:w-2/3 items-center m-auto p-8">
      <div id="field1" class="w-full flex flex-wrap"></div>
      <div id="searchExplain" class="w-full px-2 mx-2"></div>
      <div id="field2" class="w-full flex flex-wrap"></div>
      <?php
        $output = array();
        foreach(NODEMODEL as $key => $properties){
          foreach($properties as $propname => $proplist){
            if ($proplist[4]){
              if(!array_key_exists($key, $output)){
                $output[$key]=array();
              }
              $output[$key][] = [$propname, $proplist[1], $proplist[0]];
            }
          }
        }
        echo "<script>searchFields = ".json_encode($output)."</script>";
      ?>
      <script>createForm(searchFields);</script>
      <div id='searchbutton'>
        <button id='searchButtonTrigger' type='button' disabled class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" onclick='updateDict()'>
          Search
        </button>
      </div>
      <div>
        <script>
          <?php echo 'let offset = '.$offset.';';
          echo 'let limit = '.$limit.';';?>
        </script>
        <div id='pgn'></div>
        <div id='tableHere'></div>
      </div>
    </div>
  </body>
</html>