<?php
  // Base imports.
  if (!defined('ROOT_DIR')) {
    include_once(dirname(__DIR__) . "/config/config.inc.php");
  }
  include_once(ROOT_DIR . "/includes/client.inc.php");
  include_once(ROOT_DIR . "/includes/user.inc.php");
  include_once(ROOT_DIR . "/includes/navbar.inc.php");
  include_once(ROOT_DIR . "/includes/integrity.inc.php");
  include_once(ROOT_DIR . "/includes/csrf.inc.php");
  include_once(ROOT_DIR . "/includes/getnode.inc.php");

  $user = new User($client);
  if ($user->checkSession() === false) {
    $loginUrl = (defined('BASE_URL') ? BASE_URL : '') . "/user/login.php?redir=" . urlencode((defined('BASE_URL') ? BASE_URL : '') . "/admin/mod_indexes.php");
    header("Location: $loginUrl");
    die("redir required"); 
  }

  // Only allow admins here.
  if (!$user->isAdmin()) {
    header("HTTP/1.0 403 Forbidden");
    die("Insufficient rights, forbidden access");
  }
  $adminMode = true;
  $baseUrl = defined('BASE_URL') ? BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index management - <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <script>window.BASE_URL = <?php echo json_encode($baseUrl); ?>;</script>
    <script src="<?php echo $baseUrl; ?>/admin/JS/validation.js"></script>
    <script src="<?php echo $baseUrl; ?>/admin/JS/idx_handles.js?v=<?php echo filemtime(__DIR__ . '/JS/idx_handles.js'); ?>"></script>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/stylePublic.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  </head>
  <body class="bg-slate-100 text-slate-800 w-full antialiased min-h-screen">
  <?php
    $navbar = new Navbar($adminMode); 
    $node = new Node($client); 
    echo $navbar->getNav();
    $integrity = new Integrity($client);
    include_once('admin_tasks.php');
  ?>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold tracking-tight text-slate-900">Database index management</h1>
      <p class="mt-1 text-sm text-slate-500">Configure Cypher indexes on node properties to accelerate search operations and entity lookup speed.</p>
    </div>

    <!-- Instruction card -->
    <div class="mb-8 p-4 bg-white rounded-xl border border-slate-200/80 shadow-xs flex items-start gap-3">
      <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm">
        <i class="fas fa-info-circle"></i>
      </div>
      <div class="text-xs text-slate-600 leading-relaxed">
        <p class="font-semibold text-slate-800 mb-0.5">How property indexes work</p>
        Properties can be indexed to speed up repeated search and filter queries. A green lightning badge indicates the property is currently indexed in Neo4j. A red badge indicates no index exists. Click the badge to add or remove the index in real time.
      </div>
    </div>

    <!-- Entity index cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php
      $indexed_columns = $node->getIndexes(); 
      // Ensure the Variant node property 'variant' is indexed automatically.
      $variantIndexed = is_array($indexed_columns) &&
                        isset($indexed_columns['Variant']) &&
                        is_array($indexed_columns['Variant']) &&
                        array_key_exists('variant', $indexed_columns['Variant']);
      if (!$variantIndexed) {
        $addRes = $node->modifyIndex('Variant', 'variant', 'add', null);
        if (!empty($addRes['name'])) {
          if (!isset($indexed_columns['Variant']) || !is_array($indexed_columns['Variant'])) {
            $indexed_columns['Variant'] = [];
          }
          $indexed_columns['Variant']['variant'] = $addRes['name'];
        } else {
          $indexed_columns = $node->getIndexes();
        }
      }
      foreach (NODEMODEL as $label => $properties) {
    ?>
      <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col justify-between">
        <div>
          <div class="px-5 py-3.5 bg-slate-50/80 border-b border-slate-200/60 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
              <h2 class="text-sm font-bold text-slate-800 tracking-tight"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></h2>
            </div>
            <span class="text-[11px] font-medium text-slate-500"><?php echo count($properties); ?> properties</span>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-xs">
              <thead class="bg-slate-50/40 text-slate-500 uppercase tracking-wider font-semibold">
                <tr>
                  <th scope="col" class="py-2.5 px-4 text-left">Property</th>
                  <th scope="col" class="py-2.5 px-4 text-center w-28">Index state</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 bg-white">
                <?php foreach ($properties as $propName => $propparameters): ?>
                  <?php
                    $isIndexed = is_array($indexed_columns) &&
                                 isset($indexed_columns[$label]) &&
                                 is_array($indexed_columns[$label]) &&
                                 array_key_exists($propName, $indexed_columns[$label]);
                    $idxName = $isIndexed ? $indexed_columns[$label][$propName] : '';
                  ?>
                  <tr class="hover:bg-slate-50/60 transition-colors">
                    <td class="py-2.5 px-4 font-mono text-slate-700 break-all">
                      <?php echo htmlspecialchars($propName, ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                    <td class="py-2.5 px-4 text-center">
                      <?php if ($isIndexed): ?>
                        <button type="button"
                                data-node-label="<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>"
                                data-node-prop="<?php echo htmlspecialchars($propName, ENT_QUOTES, 'UTF-8'); ?>"
                                data-idxname="<?php echo htmlspecialchars($idxName, ENT_QUOTES, 'UTF-8'); ?>"
                                data_nodeLabel="<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>"
                                data_nodeProp="<?php echo htmlspecialchars($propName, ENT_QUOTES, 'UTF-8'); ?>"
                                data_idxname="<?php echo htmlspecialchars($idxName, ENT_QUOTES, 'UTF-8'); ?>"
                                title="Indexed: click to drop index"
                                class="idxBolt hasIndex inline-flex items-center justify-center gap-1 min-w-[5.75rem] px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 cursor-pointer font-semibold shadow-2xs transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                          <i class="fas fa-bolt text-[11px]"></i>
                          <span>Indexed</span>
                        </button>
                      <?php else: ?>
                        <button type="button"
                                data-node-label="<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>"
                                data-node-prop="<?php echo htmlspecialchars($propName, ENT_QUOTES, 'UTF-8'); ?>"
                                data_nodeLabel="<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>"
                                data_nodeProp="<?php echo htmlspecialchars($propName, ENT_QUOTES, 'UTF-8'); ?>"
                                title="Not indexed: click to create index"
                                class="idxBolt noIndex inline-flex items-center justify-center gap-1 min-w-[5.75rem] px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 cursor-pointer font-semibold shadow-2xs transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                          <i class="fas fa-bolt text-[11px]"></i>
                          <span>No index</span>
                        </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php } ?>
    </div>
  </main>

  <script>
    attach_dom_idx_triggers(); 
  </script>
  </body>
</html>
