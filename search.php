<?php
  include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
  include_once(ROOT_DIR.'/includes/getnode.inc.php');
  include_once(ROOT_DIR.'/includes/entityviews.inc.php');
  include_once(ROOT_DIR.'/includes/navbar.inc.php');
  include_once(ROOT_DIR.'/includes/user.inc.php');
  
if((isset($_SESSION) && boolval($_SESSION['userid']))){
  $user = new User($client);
  $adminMode = $user->myRole == 'Admin'; 
}else{
  $adminMode = False;
}
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
    <script type="text/javascript" src="/JS/search.js"> </script>
    <script type="text/javascript" src="/JS/validation.js"> </script>
  </head>
    <body class="bg-neutral-200 w-full">
    <?php
        $navbar = new Navbar($adminMode); 
        echo $navbar->nav;
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
    </div>
  </body>
</html>