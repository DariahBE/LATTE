<?php
if (!defined('ROOT_DIR')) {
  include_once(__DIR__ . '/config/config.inc.php');
}
include_once(ROOT_DIR . '/includes/client.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/navbar.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');
include_once(ROOT_DIR . '/includes/entity_merge.inc.php');

$user = new User($client);
$baseUrl = defined('BASE_URL') ? BASE_URL : '';

if ($user->checkSession() === false) {
  header('Location: ' . $baseUrl . '/user/login.php?redir=' . urlencode($baseUrl . '/merge_entities.php'));
  exit();
}

$merger = new EntityMerger($client);
if (!$merger->canMerge($user)) {
  http_response_code(403);
  die('Insufficient permissions: only project leads and administrators can merge entities.');
}

$adminMode = $user->isAdmin();
$csrfToken = (new CsrfTokenManager())->getToken();
$entityTypes = $merger->getAllowedEntityTypes();
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <title>Merge entities - <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
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
      if ($adminMode) {
        include_once(ROOT_DIR . '/admin/admin_tasks.php');
      }
    ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full flex-grow space-y-6">
      <!-- Header -->
      <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg font-bold">
            <i class="fas fa-compress-arrows-alt"></i>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Entity deduplication &amp; merge</h1>
            <p class="text-sm text-slate-500 mt-0.5">
              Merge duplicate entities (within or across entity types). Historical stable URIs and API links to the deleted entity automatically redirect to the kept entity.
            </p>
          </div>
        </div>
        <button type="button" onclick="loadCandidates()"
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 cursor-pointer">
          <i class="fas fa-sync-alt"></i>
          <span>Scan for duplicates</span>
        </button>
      </div>

      <!-- Status / result banner -->
      <div id="mergeStatusBanner" class="hidden rounded-2xl border p-4 text-sm"></div>

      <!-- Duplicate candidates -->
      <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="text-base font-bold text-slate-900">Suggested duplicate candidates</h2>
            <p class="text-xs text-slate-500">Detected from shared Wikidata Q-IDs or shared normalized spelling variants.</p>
          </div>
          <select id="candidateTypeFilter" onchange="loadCandidates()"
                  class="text-xs bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-slate-700">
            <option value="">All entity types</option>
            <?php foreach ($entityTypes as $et): ?>
              <option value="<?php echo htmlspecialchars($et, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($et, ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div id="candidatesContainer" class="p-6 text-xs text-slate-500">Loading duplicate candidates...</div>
      </section>

      <!-- Manual Source vs Target selection -->
      <section class="grid grid-cols-1 lg:grid-cols-11 gap-4 items-stretch">
        <!-- Source (WILL BE DELETED) -->
        <div class="lg:col-span-5 bg-white border-2 border-rose-200 rounded-2xl shadow-sm p-5 space-y-3">
          <div class="flex items-center justify-between">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
              <i class="fas fa-trash-alt text-[10px]"></i>
              <span>Duplicate (will be deleted)</span>
            </span>
            <span id="selectedSourceBadge" class="text-xs font-mono text-slate-400">None selected</span>
          </div>
          <div class="flex gap-2">
            <select id="sourceTypeFilter" onchange="searchEntities('source')" class="text-xs bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5">
              <option value="">All types</option>
              <?php foreach ($entityTypes as $et): ?>
                <option value="<?php echo htmlspecialchars($et, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($et, ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
            <input type="text" id="sourceSearchInput" oninput="searchEntities('source')" placeholder="Search duplicate entity..."
                   class="flex-1 min-w-0 px-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500">
          </div>
          <div id="sourceSearchResults" class="max-h-52 overflow-y-auto divide-y divide-slate-100 border border-slate-100 rounded-xl"></div>
        </div>

        <!-- Swap button -->
        <div class="lg:col-span-1 flex items-center justify-center">
          <button type="button" onclick="swapSourceAndTarget()" title="Swap deleted and kept entities"
                  class="w-11 h-11 rounded-full bg-white hover:bg-indigo-50 text-indigo-600 border border-slate-300 shadow-sm flex items-center justify-center transition-colors cursor-pointer">
            <i class="fas fa-exchange-alt"></i>
          </button>
        </div>

        <!-- Target (WILL BE KEPT) -->
        <div class="lg:col-span-5 bg-white border-2 border-emerald-200 rounded-2xl shadow-sm p-5 space-y-3">
          <div class="flex items-center justify-between">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
              <i class="fas fa-check-circle text-[10px]"></i>
              <span>Canonical (will be kept)</span>
            </span>
            <span id="selectedTargetBadge" class="text-xs font-mono text-slate-400">None selected</span>
          </div>
          <div class="flex gap-2">
            <select id="targetTypeFilter" onchange="searchEntities('target')" class="text-xs bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5">
              <option value="">All types</option>
              <?php foreach ($entityTypes as $et): ?>
                <option value="<?php echo htmlspecialchars($et, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($et, ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
            <input type="text" id="targetSearchInput" oninput="searchEntities('target')" placeholder="Search canonical entity..."
                   class="flex-1 min-w-0 px-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
          </div>
          <div id="targetSearchResults" class="max-h-52 overflow-y-auto divide-y divide-slate-100 border border-slate-100 rounded-xl"></div>
        </div>
      </section>

      <!-- Side-by-side preview & execution -->
      <section id="previewSection" class="hidden bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50">
          <div>
            <h2 class="text-base font-bold text-slate-900">Merge preview &amp; conflict resolution</h2>
            <p class="text-xs text-slate-500">Review what will be transferred, resolve any differing properties, and confirm.</p>
          </div>
          <span id="crossTypeBadge" class="hidden px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
            Cross-type merge
          </span>
        </div>
        <div id="previewBody" class="p-6 space-y-6"></div>
      </section>
    </main>

    <script>
      const BASE_URL = <?php echo json_encode($baseUrl); ?>;
      let selectedSourceId = null;
      let selectedTargetId = null;
      let currentPreview = null;

      function getCsrfToken() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
      }

      function esc(str) {
        return String(str == null ? '' : str)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;');
      }

      function showStatus(msg, isError = false) {
        const el = document.getElementById('mergeStatusBanner');
        el.className = isError
          ? 'rounded-2xl border p-4 text-sm bg-rose-50 border-rose-200 text-rose-800'
          : 'rounded-2xl border p-4 text-sm bg-emerald-50 border-emerald-200 text-emerald-900';
        el.innerHTML = msg;
        el.classList.remove('hidden');
      }

      function loadCandidates() {
        const type = document.getElementById('candidateTypeFilter').value;
        const container = document.getElementById('candidatesContainer');
        container.innerHTML = '<span class="text-slate-400">Scanning graph...</span>';

        fetch(BASE_URL + '/AJAX/merge_entities.php?action=candidates&type=' + encodeURIComponent(type))
          .then(r => r.json())
          .then(data => {
            if (!data.success || !Array.isArray(data.candidates) || data.candidates.length === 0) {
              container.innerHTML = '<span class="text-slate-500">No duplicate candidates detected automatically. You can still select any two entities manually below.</span>';
              return;
            }
            let html = '<div class="divide-y divide-slate-100">';
            data.candidates.forEach(c => {
              html += `
                <div class="py-3 flex flex-wrap items-center justify-between gap-3">
                  <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                      <span class="font-bold text-slate-900">${esc(c.source.display)}</span>
                      <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">${esc(c.source.label)} #${c.source.neo_id}</span>
                      <span class="text-slate-400">&harr;</span>
                      <span class="font-bold text-slate-900">${esc(c.target.display)}</span>
                      <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">${esc(c.target.label)} #${c.target.neo_id}</span>
                    </div>
                    <div class="text-[11px] text-indigo-600 font-medium">${esc(c.reason)}</div>
                  </div>
                  <button type="button" onclick="selectPair(${c.source.neo_id}, ${c.target.neo_id})"
                          class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold cursor-pointer">
                    Compare &amp; merge
                  </button>
                </div>
              `;
            });
            html += '</div>';
            container.innerHTML = html;
          })
          .catch(() => {
            container.innerHTML = '<span class="text-rose-600">Failed to load duplicate candidates.</span>';
          });
      }

      function searchEntities(role) {
        const isSource = (role === 'source');
        const q = document.getElementById(isSource ? 'sourceSearchInput' : 'targetSearchInput').value;
        const type = document.getElementById(isSource ? 'sourceTypeFilter' : 'targetTypeFilter').value;
        const box = document.getElementById(isSource ? 'sourceSearchResults' : 'targetSearchResults');

        fetch(BASE_URL + '/AJAX/merge_entities.php?action=search&q=' + encodeURIComponent(q) + '&type=' + encodeURIComponent(type))
          .then(r => r.json())
          .then(data => {
            if (!data.success || !Array.isArray(data.entities) || data.entities.length === 0) {
              box.innerHTML = '<div class="p-3 text-xs text-slate-400">No matching entities.</div>';
              return;
            }
            box.innerHTML = '';
            data.entities.forEach(item => {
              const isSelected = isSource ? (selectedSourceId === item.neo_id) : (selectedTargetId === item.neo_id);
              const btn = document.createElement('button');
              btn.type = 'button';
              btn.className = 'w-full text-left px-3 py-2 text-xs flex items-center justify-between gap-2 hover:bg-slate-50 cursor-pointer ' +
                (isSelected ? (isSource ? 'bg-rose-50 font-semibold' : 'bg-emerald-50 font-semibold') : '');
              btn.innerHTML = `
                <div class="min-w-0 flex-1">
                  <div class="truncate text-slate-900">${esc(item.display)}</div>
                  <div class="text-[10px] text-slate-500 font-mono">${esc(item.label)} #${item.neo_id} • ${item.anno_count} annotation(s)</div>
                </div>
                <span class="text-[11px] font-semibold ${isSource ? 'text-rose-600' : 'text-emerald-600'}">
                  ${isSelected ? 'Selected' : 'Select'}
                </span>
              `;
              btn.onclick = () => {
                if (isSource) {
                  selectedSourceId = item.neo_id;
                  document.getElementById('selectedSourceBadge').textContent = `${item.label} #${item.neo_id}`;
                } else {
                  selectedTargetId = item.neo_id;
                  document.getElementById('selectedTargetBadge').textContent = `${item.label} #${item.neo_id}`;
                }
                searchEntities(role);
                if (selectedSourceId && selectedTargetId) {
                  loadPreview();
                }
              };
              box.appendChild(btn);
            });
          });
      }

      function selectPair(sourceId, targetId) {
        selectedSourceId = sourceId;
        selectedTargetId = targetId;
        document.getElementById('selectedSourceBadge').textContent = '#' + sourceId;
        document.getElementById('selectedTargetBadge').textContent = '#' + targetId;
        searchEntities('source');
        searchEntities('target');
        loadPreview();
      }

      function swapSourceAndTarget() {
        const tmp = selectedSourceId;
        selectedSourceId = selectedTargetId;
        selectedTargetId = tmp;
        document.getElementById('selectedSourceBadge').textContent = selectedSourceId ? ('#' + selectedSourceId) : 'None selected';
        document.getElementById('selectedTargetBadge').textContent = selectedTargetId ? ('#' + selectedTargetId) : 'None selected';
        searchEntities('source');
        searchEntities('target');
        if (selectedSourceId && selectedTargetId) {
          loadPreview();
        }
      }

      function loadPreview() {
        const section = document.getElementById('previewSection');
        const body = document.getElementById('previewBody');
        const crossBadge = document.getElementById('crossTypeBadge');

        if (!selectedSourceId || !selectedTargetId || selectedSourceId === selectedTargetId) {
          section.classList.add('hidden');
          return;
        }

        section.classList.remove('hidden');
        body.innerHTML = '<div class="text-xs text-slate-500">Loading merge preview...</div>';

        fetch(BASE_URL + '/AJAX/merge_entities.php?action=preview&source_id=' + selectedSourceId + '&target_id=' + selectedTargetId)
          .then(r => r.json())
          .then(data => {
            if (!data.success || !data.preview) {
              body.innerHTML = `<div class="text-xs text-rose-600">${esc(data.error || 'Could not load preview.')}</div>`;
              return;
            }
            const p = data.preview;
            currentPreview = p;
            document.getElementById('selectedSourceBadge').textContent = `${p.source.label} #${p.source.neo_id}`;
            document.getElementById('selectedTargetBadge').textContent = `${p.target.label} #${p.target.neo_id}`;
            crossBadge.classList.toggle('hidden', !p.cross_type);

            let propRows = '';
            p.property_resolution.forEach(pr => {
              const safeKey = esc(pr.key);
              const mergedSuggestion = (pr.source_value && pr.target_value && pr.source_value !== pr.target_value)
                ? (pr.target_value + ' / ' + pr.source_value)
                : (pr.target_value || pr.source_value || '');
              propRows += `
                <tr class="${pr.has_conflict ? 'bg-amber-50/50' : ''}">
                  <td class="py-2.5 px-3 text-xs font-semibold text-slate-700 align-top">${esc(pr.label)}</td>
                  <td class="py-2.5 px-3 text-xs align-top">
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                      <input type="radio" name="prop_${safeKey}" value="source" ${pr.default_choice === 'source' ? 'checked' : ''} onchange="onPropModeChange('${safeKey}')">
                      <span class="${pr.source_value ? 'text-slate-800' : 'text-slate-400 italic'}">${pr.source_value ? esc(pr.source_value) : '(empty)'}</span>
                    </label>
                  </td>
                  <td class="py-2.5 px-3 text-xs align-top">
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                      <input type="radio" name="prop_${safeKey}" value="target" ${pr.default_choice === 'target' ? 'checked' : ''} onchange="onPropModeChange('${safeKey}')">
                      <span class="${pr.target_value ? 'text-slate-800 font-medium' : 'text-slate-400 italic'}">${pr.target_value ? esc(pr.target_value) : '(empty)'}</span>
                    </label>
                  </td>
                  <td class="py-2.5 px-3 text-xs align-top space-y-1.5">
                    <label class="inline-flex items-center gap-1.5 cursor-pointer font-medium text-indigo-700">
                      <input type="radio" name="prop_${safeKey}" value="custom" onchange="onPropModeChange('${safeKey}')">
                      <span>Merge / adapt</span>
                    </label>
                    <input type="text" id="prop_custom_${safeKey}" value="${esc(mergedSuggestion)}"
                           onfocus="selectCustomPropRadio('${safeKey}')"
                           class="w-full px-2.5 py-1 text-xs bg-white border border-slate-300 rounded-lg focus:ring-1 focus:ring-indigo-500">
                  </td>
                </tr>
              `;
            });

            let sourceOnlySection = '';
            if (Array.isArray(p.source_only_properties) && p.source_only_properties.length > 0) {
              const targetPropOptions = p.property_resolution.map(pr =>
                `<option value="${esc(pr.key)}">${esc(pr.label)} (${esc(pr.key)})</option>`
              ).join('');
              let extraRows = '';
              p.source_only_properties.forEach(sp => {
                extraRows += `
                  <tr>
                    <td class="py-2 px-3 text-xs font-semibold text-slate-700">${esc(sp.label)}</td>
                    <td class="py-2 px-3 text-xs text-slate-800">${esc(sp.value)}</td>
                    <td class="py-2 px-3 text-xs">
                      <select data-source-extra-key="${esc(sp.key)}" data-source-extra-val="${esc(sp.value)}"
                              onchange="applySourceExtraMapping(this)"
                              class="text-xs bg-white border border-slate-300 rounded-lg px-2 py-1 text-slate-700">
                        <option value="">Discard (delete with source node)</option>
                        ${targetPropOptions}
                      </select>
                    </td>
                  </tr>
                `;
              });
              sourceOnlySection = `
                <div>
                  <h3 class="text-xs font-bold uppercase tracking-wider text-rose-600 mb-2">
                    Properties on deleted node not in ${esc(p.target.label)} schema
                  </h3>
                  <p class="text-xs text-slate-500 mb-2">These properties will be deleted with the source node unless you copy them into a target property above.</p>
                  <div class="overflow-x-auto border border-rose-200 rounded-xl">
                    <table class="w-full text-left border-collapse divide-y divide-rose-100">
                      <thead class="bg-rose-50/70 text-xs font-semibold text-rose-800">
                        <tr>
                          <th class="py-2 px-3">Source property</th>
                          <th class="py-2 px-3">Value</th>
                          <th class="py-2 px-3">Action</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-100">${extraRows}</tbody>
                    </table>
                  </div>
                </div>
              `;
            }

            body.innerHTML = `
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl bg-rose-50/60 border border-rose-200 space-y-1.5">
                  <div class="text-xs font-bold text-rose-800 uppercase tracking-wider">Will be deleted (Source)</div>
                  <div class="text-base font-bold text-slate-900">${esc(p.source.display)}</div>
                  <div class="text-xs text-slate-600 font-mono">${esc(p.source.label)} #${p.source.neo_id}</div>
                  <div class="text-xs text-slate-600 pt-1">
                    Transfers: <strong>${p.source.anno_count}</strong> annotation(s),
                    <strong>${p.source.variants.length}</strong> variant(s),
                    <strong>${p.source.kb_count}</strong> KB link(s).
                  </div>
                </div>
                <div class="p-4 rounded-xl bg-emerald-50/60 border border-emerald-200 space-y-1.5">
                  <div class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Will be kept (Canonical Target)</div>
                  <div class="text-base font-bold text-slate-900">${esc(p.target.display)}</div>
                  <div class="text-xs text-slate-600 font-mono">${esc(p.target.label)} #${p.target.neo_id}</div>
                  <div class="text-xs text-slate-600 pt-1">
                    Currently has: <strong>${p.target.anno_count}</strong> annotation(s),
                    <strong>${p.target.variants.length}</strong> variant(s),
                    <strong>${p.target.kb_count}</strong> KB link(s).
                  </div>
                </div>
              </div>

              <div class="p-3 rounded-xl bg-indigo-50/70 border border-indigo-200 text-xs text-indigo-900 flex items-center gap-2">
                <i class="fas fa-directions text-indigo-600"></i>
                <span>Historical links <code>/URI/${esc(p.source.label)}/${esc(p.source.canonical_id)}</code> and <code>/API/${esc(p.source.label)}/${esc(p.source.canonical_id)}</code> will automatically redirect (HTTP 301) to the kept entity.</span>
              </div>

              <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Target properties (${esc(p.target.label)} schema)</h3>
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                  <table class="w-full text-left border-collapse divide-y divide-slate-200">
                    <thead class="bg-slate-50 text-xs font-semibold text-slate-600">
                      <tr>
                        <th class="py-2.5 px-3">Property</th>
                        <th class="py-2.5 px-3 text-rose-700">Copy from deleted (${esc(p.source.label)})</th>
                        <th class="py-2.5 px-3 text-emerald-700">Keep from target (${esc(p.target.label)})</th>
                        <th class="py-2.5 px-3 text-indigo-700">Merge / adapt value</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">${propRows || '<tr><td colspan="4" class="p-3 text-xs text-slate-400">No editable schema properties.</td></tr>'}</tbody>
                  </table>
                </div>
              </div>

              ${sourceOnlySection}

              <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                <button type="button" onclick="executeMerge()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm cursor-pointer">
                  <i class="fas fa-compress-arrows-alt"></i>
                  <span>Delete "${esc(p.source.display)}" &amp; merge into "${esc(p.target.display)}"</span>
                </button>
              </div>
            `;
          });
      }

      function onPropModeChange(key) {
        // Handled on submit when reading radio and custom input.
      }

      function selectCustomPropRadio(key) {
        const customRadio = document.querySelector(`input[name="prop_${key}"][value="custom"]`);
        if (customRadio) customRadio.checked = true;
      }

      function applySourceExtraMapping(selectEl) {
        const targetKey = selectEl.value;
        const sourceVal = selectEl.getAttribute('data-source-extra-val') || '';
        if (!targetKey || !sourceVal) return;
        const customInput = document.getElementById('prop_custom_' + targetKey);
        if (customInput) {
          const existing = customInput.value.trim();
          customInput.value = existing ? (existing + ' | ' + sourceVal) : sourceVal;
          selectCustomPropRadio(targetKey);
        }
      }

      function executeMerge() {
        if (!currentPreview) return;
        const msg = `Permanently delete "${currentPreview.source.display}" (${currentPreview.source.label} #${currentPreview.source.neo_id}) and transfer all links into "${currentPreview.target.display}" (${currentPreview.target.label} #${currentPreview.target.neo_id})?`;
        if (!confirm(msg)) return;

        const resolved = {};
        currentPreview.property_resolution.forEach(pr => {
          const checked = document.querySelector(`input[name="prop_${pr.key}"]:checked`);
          if (checked) {
            if (checked.value === 'custom') {
              const customEl = document.getElementById('prop_custom_' + pr.key);
              resolved[pr.key] = { mode: 'custom', value: customEl ? customEl.value : '' };
            } else {
              resolved[pr.key] = { mode: checked.value };
            }
          }
        });

        fetch(BASE_URL + '/AJAX/merge_entities.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
          body: JSON.stringify({
            action: 'merge',
            source_id: currentPreview.source.neo_id,
            target_id: currentPreview.target.neo_id,
            resolved_properties: resolved,
            csrf_token: getCsrfToken()
          })
        })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              const targetUrl = BASE_URL + '/URI/' + encodeURIComponent(data.target_label) + '/' + encodeURIComponent(data.target_canonical_id);
              showStatus(
                `Merged <strong>${esc(data.deleted_source_label)} #${data.deleted_source_id}</strong> into ` +
                `<a href="${targetUrl}" class="underline font-bold">${esc(data.target_label)} #${data.target_id}</a> ` +
                `(${data.transferred_annotations} annotations, ${data.transferred_variants} variants, ${data.transferred_kb_links} KB links transferred).`
              );
              selectedSourceId = null;
              selectedTargetId = null;
              currentPreview = null;
              document.getElementById('previewSection').classList.add('hidden');
              document.getElementById('selectedSourceBadge').textContent = 'None selected';
              document.getElementById('selectedTargetBadge').textContent = 'None selected';
              loadCandidates();
              searchEntities('source');
              searchEntities('target');
            } else {
              showStatus(esc(data.error || 'Merge failed.'), true);
            }
          })
          .catch(() => {
            showStatus('Network error while executing merge.', true);
          });
      }

      document.addEventListener('DOMContentLoaded', function() {
        loadCandidates();
        searchEntities('source');
        searchEntities('target');
      });
    </script>
  </body>
</html>
