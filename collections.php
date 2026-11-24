<?php
if (!defined('ROOT_DIR')) {
  include_once(__DIR__ . '/config/config.inc.php');
}
include_once(ROOT_DIR . '/includes/client.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/navbar.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');
include_once(ROOT_DIR . '/includes/collection.inc.php');

$user = new User($client);
$user->checkAccess(TEXTSAREPUBLIC);

$adminMode = (isset($_SESSION['userid']) && boolval($_SESSION['userid'])) ? $user->isAdmin() : false;
$isLoggedIn = (isset($_SESSION['userid']) && boolval($_SESSION['userid']));
$userNeoId = $user->neoId ? (int)$user->neoId : null;
$baseUrl = defined('BASE_URL') ? BASE_URL : '';

$tokenManager = new CsrfTokenManager();
$csrfToken = $tokenManager->getToken();

$manager = new CollectionManager($client);
$canCreateProject = $manager->canCreateProjectCollection($user);
$canCreatePersonal = $manager->canCreatePersonalCollection($user);

$requestedId = isset($_GET['id']) ? trim((string)$_GET['id']) : (isset($_GET['uid']) ? trim((string)$_GET['uid']) : '');
$detailCollection = null;
if ($requestedId !== '') {
  $detailCollection = $manager->getCollectionByIdentifier($requestedId, $userNeoId, TEXTSAREPUBLIC);
  if ($detailCollection === null) {
    header('Location: ' . $baseUrl . '/error.php?type=id');
    exit;
  }
}

$allCollections = ($detailCollection === null) ? $manager->listAccessibleCollections($userNeoId, TEXTSAREPUBLIC) : null;
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo $detailCollection ? htmlspecialchars($detailCollection['name'] . ' - Collections - ' . PROJECTNAME, ENT_QUOTES, 'UTF-8') : htmlspecialchars('Collections - ' . PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/stylePublic.css?v=<?php echo filemtime(ROOT_DIR . '/CSS/stylePublic.css'); ?>">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <?php
      include_once(ROOT_DIR . '/includes/matomo.inc.php');
      echo renderMatomoTracking();
    ?>
  </head>
  <body class="bg-slate-100 text-slate-800 w-full antialiased min-h-screen flex flex-col">
    <?php
      $navbar = new Navbar($adminMode);
      echo $navbar->getNav();
    ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-grow space-y-6">
      <?php if ($detailCollection !== null): ?>
        <?php
          $canEditMeta = $manager->canEditCollectionMetadata($user, $detailCollection);
          $canEditMembers = $manager->canEditCollectionMembership($user, $detailCollection);
          $isProject = ($detailCollection['scope'] === 'project');
        ?>
        <!-- Breadcrumb -->
        <div class="flex items-center justify-between gap-4">
          <a href="<?php echo $baseUrl; ?>/collections.php" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="fas fa-arrow-left"></i>
            <span>Back to all collections</span>
          </a>
        </div>

        <!-- Collection detail header -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-xl <?php echo $isProject ? 'bg-indigo-50 text-indigo-600' : 'bg-emerald-50 text-emerald-600'; ?> flex items-center justify-center text-lg font-bold flex-shrink-0">
                <i class="fas <?php echo $isProject ? 'fa-layer-group' : 'fa-bookmark'; ?>"></i>
              </div>
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <h1 class="text-2xl font-bold text-slate-900 tracking-tight"><?php echo htmlspecialchars($detailCollection['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?php echo $isProject ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'; ?>">
                    <?php echo $isProject ? 'Project collection' : 'Personal collection'; ?>
                  </span>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-mono bg-slate-100 text-slate-600">
                    #<?php echo (int)$detailCollection['collection_id']; ?>
                  </span>
                </div>
                <?php if ($detailCollection['description'] !== ''): ?>
                  <p class="text-sm text-slate-600 mt-2 max-w-3xl"><?php echo nl2br(htmlspecialchars($detailCollection['description'], ENT_QUOTES, 'UTF-8')); ?></p>
                <?php else: ?>
                  <p class="text-xs text-slate-400 italic mt-2">No description provided.</p>
                <?php endif; ?>
                <div class="flex flex-wrap items-center gap-4 mt-3 text-xs text-slate-500">
                  <span><i class="fas fa-file-alt mr-1 text-slate-400"></i><?php echo (int)$detailCollection['text_count']; ?> <?php echo $detailCollection['text_count'] === 1 ? 'text' : 'texts'; ?></span>
                  <span class="font-mono text-[11px] text-slate-400">UUID: <?php echo htmlspecialchars($detailCollection['uid'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
              </div>
            </div>

            <?php if ($canEditMeta): ?>
            <div class="flex items-center gap-2">
              <button type="button"
                      onclick='openEditModal(<?php echo json_encode([
                        "collection_id" => $detailCollection["collection_id"],
                        "name" => $detailCollection["name"],
                        "description" => $detailCollection["description"],
                        "scope" => $detailCollection["scope"],
                      ]); ?>)'
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 shadow-2xs transition-colors cursor-pointer">
                <i class="fas fa-edit text-[11px]"></i>
                <span>Edit</span>
              </button>
              <button type="button"
                      onclick="deleteCollection(<?php echo (int)$detailCollection['collection_id']; ?>)"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition-colors cursor-pointer">
                <i class="fas fa-trash-alt text-[11px]"></i>
                <span>Delete</span>
              </button>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Collection texts list -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">Texts in this collection</h2>
            <?php if (!empty($detailCollection['texts'])): ?>
              <a href="<?php echo $baseUrl; ?>/text/<?php echo (int)$detailCollection['texts'][0]['text_id']; ?>?collection=<?php echo (int)$detailCollection['collection_id']; ?>"
                 class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-colors">
                <i class="fas fa-book-open text-[11px]"></i>
                <span>Start reading collection</span>
              </a>
            <?php endif; ?>
          </div>

          <?php if (empty($detailCollection['texts'])): ?>
            <div class="p-10 text-center text-sm text-slate-500">
              <p>No texts have been added to this collection yet.</p>
              <p class="text-xs text-slate-400 mt-1">Open any text in the reader and use the <strong>+ Collections</strong> menu to add it here.</p>
            </div>
          <?php else: ?>
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse text-sm divide-y divide-slate-200">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-600">
                  <tr>
                    <th class="py-3 px-6">Text ID</th>
                    <th class="py-3 px-6">Title</th>
                    <th class="py-3 px-6">Language</th>
                    <th class="py-3 px-6">Length</th>
                    <th class="py-3 px-6 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <?php foreach ($detailCollection['texts'] as $t): ?>
                    <tr class="hover:bg-slate-50/70 transition-colors">
                      <td class="py-3 px-6 font-mono text-xs text-slate-500">#<?php echo (int)$t['text_id']; ?></td>
                      <td class="py-3 px-6 font-medium text-slate-900">
                        <a href="<?php echo $baseUrl; ?>/text/<?php echo (int)$t['text_id']; ?>?collection=<?php echo (int)$detailCollection['collection_id']; ?>" class="text-indigo-600 hover:underline">
                          <?php echo htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                      </td>
                      <td class="py-3 px-6 text-xs text-slate-600">
                        <?php if ($t['language']): ?>
                          <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-medium">
                            <?php echo htmlspecialchars($t['language'], ENT_QUOTES, 'UTF-8'); ?>
                          </span>
                        <?php else: ?>
                          <span class="text-slate-400">—</span>
                        <?php endif; ?>
                      </td>
                      <td class="py-3 px-6 text-xs text-slate-500"><?php echo number_format((int)$t['char_count']); ?> chars</td>
                      <td class="py-3 px-6 text-right whitespace-nowrap space-x-2">
                        <a href="<?php echo $baseUrl; ?>/text/<?php echo (int)$t['text_id']; ?>?collection=<?php echo (int)$detailCollection['collection_id']; ?>"
                           class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition-colors">
                          <i class="fas fa-book-open text-[10px]"></i>
                          <span>Read</span>
                        </a>
                        <?php if ($canEditMembers): ?>
                        <button type="button"
                                onclick="removeTextFromCollection(<?php echo (int)$detailCollection['collection_id']; ?>, <?php echo (int)$t['neo_id']; ?>)"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 transition-colors cursor-pointer">
                          <i class="fas fa-times text-[10px]"></i>
                          <span>Remove</span>
                        </button>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

      <?php else: ?>
        <!-- Overview header -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg font-bold">
              <i class="fas fa-layer-group"></i>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Text collections</h1>
              <p class="text-sm text-slate-500 mt-0.5">Organize texts into shared project collections and private personal collections.</p>
            </div>
          </div>

          <?php if ($canCreatePersonal || $canCreateProject): ?>
          <button type="button"
                  onclick="openCreateModal()"
                  class="inline-flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-colors cursor-pointer">
            <i class="fas fa-plus"></i>
            <span>New collection</span>
          </button>
          <?php endif; ?>
        </div>

        <!-- Project collections -->
        <section class="space-y-3">
          <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 inline-block"></span>
              <span>Project collections</span>
              <span class="text-xs font-normal text-slate-500">(<?php echo count($allCollections['project']); ?>)</span>
            </h2>
          </div>

          <?php if (empty($allCollections['project'])): ?>
            <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center text-sm text-slate-500">
              No project collections have been created yet.
            </div>
          <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <?php foreach ($allCollections['project'] as $col): ?>
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 flex flex-col justify-between gap-4 hover:border-indigo-300 transition-colors">
                  <div>
                    <div class="flex items-start justify-between gap-2 mb-2">
                      <a href="<?php echo $baseUrl; ?>/collection/<?php echo (int)$col['collection_id']; ?>" class="text-base font-bold text-slate-900 hover:text-indigo-600 transition-colors">
                        <?php echo htmlspecialchars($col['name'], ENT_QUOTES, 'UTF-8'); ?>
                      </a>
                      <span class="px-2 py-0.5 rounded-full text-[11px] font-mono bg-slate-100 text-slate-600 flex-shrink-0">#<?php echo (int)$col['collection_id']; ?></span>
                    </div>
                    <p class="text-xs text-slate-600 line-clamp-3">
                      <?php echo $col['description'] !== '' ? htmlspecialchars($col['description'], ENT_QUOTES, 'UTF-8') : '<span class="italic text-slate-400">No description</span>'; ?>
                    </p>
                  </div>
                  <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-500 font-medium">
                      <i class="fas fa-file-alt mr-1 text-slate-400"></i><?php echo (int)$col['text_count']; ?> <?php echo $col['text_count'] === 1 ? 'text' : 'texts'; ?>
                    </span>
                    <a href="<?php echo $baseUrl; ?>/collection/<?php echo (int)$col['collection_id']; ?>" class="font-semibold text-indigo-600 hover:underline">
                      View collection &rarr;
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

        <!-- Personal collections -->
        <?php if ($isLoggedIn): ?>
        <section class="space-y-3">
          <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
              <span>My personal collections</span>
              <span class="text-xs font-normal text-slate-500">(<?php echo count($allCollections['personal']); ?>)</span>
            </h2>
          </div>

          <?php if (empty($allCollections['personal'])): ?>
            <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center text-sm text-slate-500">
              You have not created any personal collections yet.
            </div>
          <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <?php foreach ($allCollections['personal'] as $col): ?>
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 flex flex-col justify-between gap-4 hover:border-emerald-300 transition-colors">
                  <div>
                    <div class="flex items-start justify-between gap-2 mb-2">
                      <a href="<?php echo $baseUrl; ?>/collection/<?php echo (int)$col['collection_id']; ?>" class="text-base font-bold text-slate-900 hover:text-emerald-700 transition-colors">
                        <?php echo htmlspecialchars($col['name'], ENT_QUOTES, 'UTF-8'); ?>
                      </a>
                      <span class="px-2 py-0.5 rounded-full text-[11px] font-mono bg-emerald-50 text-emerald-700 flex-shrink-0">#<?php echo (int)$col['collection_id']; ?></span>
                    </div>
                    <p class="text-xs text-slate-600 line-clamp-3">
                      <?php echo $col['description'] !== '' ? htmlspecialchars($col['description'], ENT_QUOTES, 'UTF-8') : '<span class="italic text-slate-400">No description</span>'; ?>
                    </p>
                  </div>
                  <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-500 font-medium">
                      <i class="fas fa-file-alt mr-1 text-slate-400"></i><?php echo (int)$col['text_count']; ?> <?php echo $col['text_count'] === 1 ? 'text' : 'texts'; ?>
                    </span>
                    <a href="<?php echo $baseUrl; ?>/collection/<?php echo (int)$col['collection_id']; ?>" class="font-semibold text-emerald-700 hover:underline">
                      View collection &rarr;
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
        <?php endif; ?>
      <?php endif; ?>
    </main>

    <!-- Create/Edit Collection Modal -->
    <div id="collectionModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
      <div class="bg-white border border-slate-200 rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
          <h3 id="modalTitle" class="text-base font-bold text-slate-900">New collection</h3>
          <button type="button" onclick="closeCollectionModal()" class="text-slate-400 hover:text-slate-700 cursor-pointer">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <form id="collectionForm" onsubmit="submitCollectionForm(event)" class="p-6 space-y-4">
          <input type="hidden" id="modalCollectionId" value="">
          <div>
            <label for="modalName" class="block text-xs font-semibold text-slate-700 mb-1">Name</label>
            <input type="text" id="modalName" required maxlength="120"
                   class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div>
            <label for="modalDescription" class="block text-xs font-semibold text-slate-700 mb-1">Description</label>
            <textarea id="modalDescription" rows="3"
                      class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
          </div>
          <div id="modalScopeGroup">
            <label for="modalScope" class="block text-xs font-semibold text-slate-700 mb-1">Scope</label>
            <select id="modalScope" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500">
              <option value="personal">Personal (private to you)</option>
              <?php if ($canCreateProject): ?>
              <option value="project">Project (shared across project)</option>
              <?php endif; ?>
            </select>
          </div>
          <div id="modalError" class="hidden text-xs text-rose-600 font-medium"></div>
          <div class="flex items-center justify-end gap-2 pt-2">
            <button type="button" onclick="closeCollectionModal()" class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 cursor-pointer">Cancel</button>
            <button type="submit" id="modalSubmitBtn" class="px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm cursor-pointer">Save</button>
          </div>
        </form>
      </div>
    </div>

    <script>
      const BASE_URL = <?php echo json_encode($baseUrl); ?>;
      function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
      }

      function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'New collection';
        document.getElementById('modalCollectionId').value = '';
        document.getElementById('modalName').value = '';
        document.getElementById('modalDescription').value = '';
        document.getElementById('modalScopeGroup').classList.remove('hidden');
        document.getElementById('modalError').classList.add('hidden');
        const modal = document.getElementById('collectionModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
      }

      function openEditModal(col) {
        document.getElementById('modalTitle').textContent = 'Edit collection';
        document.getElementById('modalCollectionId').value = col.collection_id;
        document.getElementById('modalName').value = col.name || '';
        document.getElementById('modalDescription').value = col.description || '';
        document.getElementById('modalScopeGroup').classList.add('hidden');
        document.getElementById('modalError').classList.add('hidden');
        const modal = document.getElementById('collectionModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
      }

      function closeCollectionModal() {
        const modal = document.getElementById('collectionModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      }

      function submitCollectionForm(e) {
        e.preventDefault();
        const id = document.getElementById('modalCollectionId').value;
        const name = document.getElementById('modalName').value.trim();
        const description = document.getElementById('modalDescription').value.trim();
        const scope = document.getElementById('modalScope').value;
        const errEl = document.getElementById('modalError');

        const payload = id
          ? { action: 'update', id: id, name: name, description: description, csrf_token: getCsrfToken() }
          : { action: 'create', name: name, description: description, scope: scope, csrf_token: getCsrfToken() };

        fetch(BASE_URL + '/AJAX/collections.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
          body: JSON.stringify(payload)
        })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              window.location.reload();
            } else {
              errEl.textContent = data.error || 'Failed to save collection.';
              errEl.classList.remove('hidden');
            }
          })
          .catch(() => {
            errEl.textContent = 'Network error while saving collection.';
            errEl.classList.remove('hidden');
          });
      }

      function deleteCollection(collectionId) {
        if (!confirm('Are you sure you want to delete this collection? The texts inside it will not be deleted.')) {
          return;
        }
        fetch(BASE_URL + '/AJAX/collections.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
          body: JSON.stringify({ action: 'delete', id: collectionId, csrf_token: getCsrfToken() })
        })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              window.location.href = BASE_URL + '/collections.php';
            } else {
              alert(data.error || 'Could not delete collection.');
            }
          });
      }

      function removeTextFromCollection(collectionId, textNeoId) {
        fetch(BASE_URL + '/AJAX/collections.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
          body: JSON.stringify({ action: 'toggle_text', id: collectionId, text_neoid: textNeoId, member: false, csrf_token: getCsrfToken() })
        })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              window.location.reload();
            } else {
              alert(data.error || 'Could not remove text from collection.');
            }
          });
      }
    </script>
  </body>
</html>
