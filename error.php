<?php
include_once(__DIR__ . '/config/config.inc.php');

$title = 'An unspecified error occurred.';
$record = 0;
$fullErrMsg = '';
$hint = 'Please check the URL or return to the homepage.';

if (isset($_GET['type'])){
  switch ($_GET['type']){
    case '':
      $title = 'An unspecified error occurred.';
      break;
    case 'text':
      $title = 'No matching text found.';
      $fullErrMsg = 'There was no text found matching your search criteria.';
      $hint = 'Try searching with different keywords or browse the text archive.';
      break;
    case 'textmissing':
      $title = 'No text identifier provided.';
      $fullErrMsg = 'Your request did not contain a valid text identifier. The link you followed may be broken.';
      $hint = 'Return to the texts directory to select an available document.';
      break;
    case 'node':
      $title = 'Invalid node type.';
      $fullErrMsg = 'Your request does not contain a valid node identifier. This node type may not exist.';
      $hint = 'Verify the entity type in your search or graph query.';
      break;
    case 'id':
      $title = 'Invalid identifier.';
      $fullErrMsg = 'The provided ID does not match any record in the database. The node may have been deleted or never existed.';
      $hint = 'Double check the entity URI or search for the item by name.';
      break;
    case 'conn': 
      if (!headers_sent()) {
        http_response_code(503);
        header('Retry-After: 30');
      }
      $title = 'Database unreachable.'; 
      $fullErrMsg = "Couldn't connect to the project database. Contact your webmaster."; 
      $hint = 'The project database service is currently offline or refusing connections. Please start Neo4j or contact your webmaster.';
      break;
    case 'notexts': 
      $title = 'No texts in database.';
      $fullErrMsg = 'The database is reachable but contains no texts yet.';
      $hint = 'Administrators can use the bulk text loader to ingest corpus files.';
      break;
    default:
      break;
  }
} 
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>/CSS/stylePublic.css">
    <?php
      if (defined('ROOT_DIR') && file_exists(ROOT_DIR . '/includes/matomo.inc.php')) {
        include_once(ROOT_DIR . '/includes/matomo.inc.php');
        echo renderMatomoTracking();
      }
    ?>
  </head>
  <body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white border border-slate-200 rounded-2xl shadow-sm p-8 text-center">
      <div class="w-14 h-14 bg-rose-50 text-rose-500 border border-rose-200 rounded-full flex items-center justify-center mx-auto mb-5 text-2xl font-bold">
        !
      </div>
      <h1 class="text-2xl font-bold text-slate-900 mb-2">
        <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
      </h1>
      <?php if (!empty($fullErrMsg)): ?>
        <p class="text-slate-600 mb-4 text-sm leading-relaxed">
          <?php echo htmlspecialchars($fullErrMsg, ENT_QUOTES, 'UTF-8'); ?>
        </p>
      <?php endif; ?>
      <p class="text-xs text-slate-500 mb-6 bg-slate-50 border border-slate-200 rounded-lg p-3">
        <?php echo htmlspecialchars($hint, ENT_QUOTES, 'UTF-8'); ?>
      </p>
      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <?php $landingUrl = (defined('BASE_URL') && BASE_URL !== '') ? BASE_URL . '/index.php' : '/index.php'; ?>
        <a href="<?php echo htmlspecialchars($landingUrl, ENT_QUOTES, 'UTF-8'); ?>" class="px-5 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm transition-colors shadow-sm">
          Return to homepage
        </a>
        <button onclick="window.history.back()" class="px-5 py-2.5 rounded-lg bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-medium text-sm transition-colors">
          Go back
        </button>
      </div>
    </div>
  </body>
</html>
