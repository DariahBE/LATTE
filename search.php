<?php
  if (!defined('ROOT_DIR')) {
    include_once(__DIR__.'/config/config.inc.php');
  }
  include_once(ROOT_DIR.'/includes/getnode.inc.php');
  //include_once(ROOT_DIR.'/includes/entity.views.inc.donotuse.php');
  include_once(ROOT_DIR.'/includes/navbar.inc.php');
  include_once(ROOT_DIR.'/includes/user.inc.php');
  
$user = new User($client);
if((isset($_SESSION) && isset($_SESSION['userid']) && boolval($_SESSION['userid']))){
  $adminMode = $user->isAdmin(); 
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
    <title><?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?>: Search</title>
    <link rel="stylesheet" href="/CSS/style_entities.css">
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script type="text/javascript" src="/JS/search.js?v=<?php echo filemtime(ROOT_DIR . '/JS/search.js'); ?>"></script>
    <?php
      include_once(ROOT_DIR . '/includes/matomo.inc.php');
      echo renderMatomoTracking();
    ?>
  </head>
  <body class="bg-slate-100 text-slate-800 w-full antialiased">
    <?php
        $navbar = new Navbar($adminMode); 
        echo $navbar->getNav();
    ?>

    <div class="max-w-5xl mx-auto my-8 px-4 sm:px-6">
      <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8 mb-8">
        <div class="border-b border-slate-200 pb-4 mb-6">
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Search entities</h1>
          <p class="text-sm text-slate-500 mt-1">Query nodes across the knowledge graph with attribute filters and pattern modifiers.</p>
        </div>

        <div class="mb-6">
          <label class="block text-sm font-semibold text-slate-700 mb-3">Select entity type</label>
          <div id="field1" class="flex flex-wrap gap-2.5"></div>
        </div>

        <div id="searchExplain" class="mb-6"></div>

        <div id="field2" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6"></div>

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
        <div id='searchbutton' class="border-t border-slate-200 pt-5 flex justify-end">
          <button id='searchButtonTrigger' type='button' disabled class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-lg shadow-sm transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed" onclick='updateDict()'>
            Search
          </button>
        </div>
      </div>

      <div id="resultsCard" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8 hidden">
        <script>
          <?php echo 'let offset = '.$offset.';';
          echo 'let limit = '.$limit.';';?>
        </script>
        <div id='pgn' class="mb-4"></div>
        <div id='tableHere' class="overflow-x-auto"></div>
      </div>
    </div>
  </body>
</html>