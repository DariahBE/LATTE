<?php
if (!defined('ROOT_DIR')) {
  include_once(__DIR__.'/config/config.inc.php');
}
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/navbar.inc.php');

if(!(isset($_SESSION) && boolval($_SESSION['userid']))){
    $loginTarget = (defined('BASE_URL') ? BASE_URL : '') . '/user/login.php?redir=' . (defined('BASE_URL') ? BASE_URL : '') . '/create.php';
    header('Location: ' . $loginTarget);
    exit;
}else{
  $user = new User($client);
  $adminMode = $user->isAdmin(); 
}

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="/CSS/style_entities.css">
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="/CSS/leaflet/leaflet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src = "/JS/creation.js"></script>
    <script src = "/JS/validation.js"></script>
    <?php
      include_once(ROOT_DIR . '/includes/matomo.inc.php');
      echo renderMatomoTracking();
    ?>
  </head>
  <body class="bg-slate-100 text-slate-800 w-full antialiased">
    
    <?php
        $navbar = new Navbar($adminMode); 
        echo $navbar->getNav();  
        //DO NOT add annotations in this environment (nodename: Annotation) !
        echo '<script>var core = '.json_encode(array_values(array_diff(array_keys(CORENODES), array(ANNONODE)))).';</script>';
    ?>
    <div class="max-w-4xl mx-auto my-8 px-4 sm:px-6">
      <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8">
        <div class="border-b border-slate-200 pb-4 mb-6">
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Create entity</h1>
          <p class="text-sm text-slate-500 mt-1">Add a new node to the knowledge graph with validated schema properties.</p>
        </div>

        <div id='formMessageBox' class="mb-4"></div>

        <div class="mb-6">
          <label for="select_dd_element" class="block text-sm font-semibold text-slate-700 mb-2">Select entity type</label>
          <div id='nodeTypeSelection'></div>
        </div>

        <div id='propertySection' class='hidden border-t border-slate-200 pt-6'>
          <h2 class="text-lg font-semibold text-slate-900 mb-4">Node properties</h2>
          <div id='propertyInputSection'></div>
        </div>
      </div>
    </div>

    <script>
      const texnode = '<?php echo TEXNODE ?>'; 
      const texnodetext = '<?php echo TEXNODETEXT ?>' ;
      const creator = new nodeCreator(core); 
      //declare in window scope
      let validator;    
      //let creation.js handle the rest: 
      // let validator = new Validator; 
      // validator.set_autofetch_to(true); 
      // validator.pickup(); 
  </script>
  </body>
</html>