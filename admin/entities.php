<?php
// Base imports.
if (!defined('ROOT_DIR')) {
    include_once(dirname(__DIR__) . "/config/config.inc.php");
}
include_once(ROOT_DIR . "/includes/client.inc.php");
include_once(ROOT_DIR . "/includes/user.inc.php");
include_once(ROOT_DIR . "/includes/navbar.inc.php");
include_once(ROOT_DIR . "/includes/csrf.inc.php");
include_once(ROOT_DIR . "/includes/config_model_manager.inc.php");

$user = new User($client);
if ($user->checkSession() === false) {
    $loginUrl = (defined('BASE_URL') ? BASE_URL : '') . "/user/login.php?redir=" . urlencode((defined('BASE_URL') ? BASE_URL : '') . "/admin/entities.php");
    header("Location: $loginUrl");
    exit();
}

// Only allow administrators here.
if (!$user->isAdmin()) {
    header("HTTP/1.0 403 Forbidden");
    die("Insufficient rights, forbidden access");
}

$adminMode = true;
$baseUrl = defined('BASE_URL') ? BASE_URL : '';
$tokenManager = new CsrfTokenManager();
$csrfToken = $tokenManager->getToken();

$coreSpecs = ConfigModelManager::getMandatedCoreEntities();
$coreStatus = ConfigModelManager::getCoreEntitiesStatus();
$entities = ConfigModelManager::getAllEntitiesWithProperties();
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entity management - <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/stylePublic.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .color-preview {
            width: 20px;
            height: 20px;
            border-radius: 9999px;
            display: inline-block;
            border: 1px solid rgba(0,0,0,0.15);
        }
        .modal-backdrop {
            background-color: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(2px);
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 w-full antialiased min-h-screen">
<?php
    $navbar = new Navbar($adminMode);
    echo $navbar->getNav();
    include_once('admin_tasks.php');
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
    <!-- Header section -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Entity and schema configuration</h1>
            <p class="mt-1 text-sm text-slate-500">
                Manage entity node types, properties, and validation rules stored in SQLite and compiled for high-speed runtime execution.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <?php if (!$coreStatus['all_core_defined']): ?>
                <button type="button" onclick="openEntityModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition-colors shadow-xs"
                        title="Custom entity type creation is locked until Text, Annotation, and Variant are defined">
                    <i class="fas fa-lock text-[10px]"></i>
                    <span>Add entity type (locked)</span>
                </button>
            <?php else: ?>
                <button type="button" onclick="openEntityModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors shadow-xs">
                    <i class="fas fa-plus"></i>
                    <span>Add entity type</span>
                </button>
            <?php endif; ?>

            <button type="button" onclick="triggerCacheRebuild()"
                    class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors shadow-xs">
                <i class="fas fa-sync-alt"></i>
                <span>Rebuild cache</span>
            </button>
            <button type="button" onclick="triggerNeoSync()"
                    class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors shadow-xs"
                    title="Synchronize constraints and property indexes into Neo4j">
                <i class="fas fa-bolt text-amber-500"></i>
                <span>Sync Neo4j</span>
            </button>
            <button type="button" onclick="resetToEmptySchema()"
                    class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-lg bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors shadow-xs"
                    title="Reset entity schema to empty state">
                <i class="fas fa-trash-alt text-[10px]"></i>
                <span>Reset schema</span>
            </button>
        </div>
    </div>

    <!-- Notification message container -->
    <div id="alertBox" class="hidden mb-6 p-4 rounded-xl border text-sm transition-all"></div>

    <!-- Core architecture requirement checklist card -->
    <?php if (!$coreStatus['all_core_defined']): ?>
        <div class="mb-8 p-6 bg-amber-50/60 rounded-2xl border border-amber-200/90 shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-amber-200/70">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-600 text-white flex items-center justify-center text-base shadow-2xs">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-bold text-slate-900">Mandated core architecture entities</h2>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-200 text-amber-900">
                                <i class="fas fa-lock text-[9px]"></i> Creation locked
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 mt-0.5">
                            LATTE requires three fundamental core entity types for its knowledge graph and text analysis pipeline: <strong>Text</strong>, <strong>Annotation</strong>, and <strong>Variant</strong>. Creating custom entity types is locked until these three core types and their required properties are defined.
                        </p>
                    </div>
                </div>

                <div>
                    <button type="button" onclick="scaffoldAllCoreTypes()"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors shadow-xs">
                        <i class="fas fa-magic text-xs"></i>
                        <span>Quick-scaffold core architecture entities</span>
                    </button>
                </div>
            </div>

            <!-- Core entity 3-card grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach ($coreStatus['details'] as $cName => $cDetail): ?>
                    <?php
                        $isComplete = $cDetail['is_complete'];
                        $exists = $cDetail['exists'];
                        $cardBg = $isComplete ? 'bg-white border-emerald-200' : ($exists ? 'bg-white border-amber-300' : 'bg-white border-slate-200');
                    ?>
                    <div class="rounded-xl border p-4 shadow-2xs <?= $cardBg ?> flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full" style="background-color: <?= htmlspecialchars($cDetail['spec']['color'], ENT_QUOTES, 'UTF-8') ?>;"></span>
                                    <h3 class="text-sm font-bold text-slate-900"><?= htmlspecialchars($cName, ENT_QUOTES, 'UTF-8') ?></h3>
                                </div>
                                <?php if ($isComplete): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                        <i class="fas fa-check text-[9px]"></i> Ready
                                    </span>
                                <?php elseif ($exists): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">
                                        Incomplete
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600">
                                        Not defined
                                    </span>
                                <?php endif; ?>
                            </div>

                            <p class="text-[11px] text-slate-500 mb-3 leading-relaxed">
                                <?= htmlspecialchars($cDetail['spec']['description'], ENT_QUOTES, 'UTF-8') ?>
                            </p>

                            <!-- Required properties checklist -->
                            <div class="space-y-1.5 pt-2 border-t border-slate-100 mb-4">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Required properties:</span>
                                <?php foreach ($cDetail['required_properties'] as $reqPropKey => $reqProp): ?>
                                    <?php
                                        $propConfigured = in_array($reqPropKey, $cDetail['existing_properties'], true);
                                    ?>
                                    <div class="flex items-center justify-between text-xs py-1 px-2 rounded <?= $propConfigured ? 'bg-emerald-50/70 text-emerald-900' : 'bg-rose-50/80 text-rose-900' ?>">
                                        <div class="flex items-center gap-1.5 font-mono">
                                            <i class="fas <?= $propConfigured ? 'fa-check text-emerald-600' : 'fa-times text-rose-500' ?> text-[10px]"></i>
                                            <span class="font-semibold"><?= htmlspecialchars($reqPropKey, ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="text-[10px] text-slate-500 font-sans">(<?= htmlspecialchars($reqProp['data_type'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                        </div>
                                        <span class="text-[10px] font-medium <?= $propConfigured ? 'text-emerald-700' : 'text-rose-600' ?>">
                                            <?= $propConfigured ? 'Configured' : 'Missing' ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="pt-2">
                            <?php if ($isComplete): ?>
                                <div class="text-center text-xs font-semibold text-emerald-700 py-1 flex items-center justify-center gap-1.5">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Core entity ready</span>
                                </div>
                            <?php elseif ($exists): ?>
                                <button type="button"
                                        onclick="scaffoldCoreType('<?= htmlspecialchars($cName, ENT_QUOTES, 'UTF-8') ?>')"
                                        class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition shadow-2xs">
                                    <i class="fas fa-wrench text-[10px]"></i>
                                    <span>Add missing required properties</span>
                                </button>
                            <?php else: ?>
                                <button type="button"
                                        onclick="scaffoldCoreType('<?= htmlspecialchars($cName, ENT_QUOTES, 'UTF-8') ?>')"
                                        class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-2xs">
                                    <i class="fas fa-plus text-[10px]"></i>
                                    <span>Define <?= htmlspecialchars($cName, ENT_QUOTES, 'UTF-8') ?> entity</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Unlocked core banner -->
        <div class="mb-8 p-4 bg-emerald-50/70 rounded-xl border border-emerald-200 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-sm shadow-2xs">
                    <i class="fas fa-check"></i>
                </div>
                <div>
                    <h2 class="text-xs font-bold text-emerald-950 uppercase tracking-wider">Core entity types fully configured</h2>
                    <p class="text-xs text-emerald-800 mt-0.5">
                        The required core entities (Text, Annotation, and Variant) are defined with all essential properties. Custom entity type creation is unlocked.
                    </p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-200 text-emerald-900">
                <i class="fas fa-unlock text-[10px]"></i> Unlocked
            </span>
        </div>
    <?php endif; ?>


    <!-- Entity Cards Grid -->
    <div class="space-y-6">
        <?php if (empty($entities)): ?>
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-12 text-center">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fas fa-cubes"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800">No entity types defined yet</h3>
                <p class="mt-1 text-xs text-slate-500 max-w-md mx-auto">
                    The entity type system is currently empty upon initialization. Define the three mandated core architecture entities (Text, Annotation, and Variant) above to begin configuring your knowledge graph.
                </p>
                <div class="mt-6">
                    <button type="button" onclick="scaffoldAllCoreTypes()"
                            class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-xs">
                        <i class="fas fa-magic"></i>
                        <span>Scaffold core architecture entities</span>
                    </button>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($entities as $entity): ?>
                <?php
                    $isCoreEntity = isset($coreSpecs[$entity['name']]);
                ?>
                <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden transition-all hover:border-slate-300" id="entity-card-<?= (int)$entity['id'] ?>">
                    <!-- Card header -->
                    <div class="px-6 py-4 bg-slate-50/80 border-b border-slate-200/70 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="color-preview" style="background-color: <?= htmlspecialchars($entity['color'], ENT_QUOTES, 'UTF-8') ?>;" title="Entity visual badge color"></span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-base font-bold text-slate-900 tracking-tight"><?= htmlspecialchars($entity['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                                    <span class="text-xs text-slate-500 font-normal">(<?= htmlspecialchars($entity['plural_label'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                    <?php if ($isCoreEntity): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-800" title="Core architecture entity mandated for system operation">
                                            <i class="fas fa-shield-alt text-[9px]"></i> Core entity
                                        </span>
                                    <?php elseif (!empty($entity['is_system'])): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-200 text-slate-700" title="Core system node required for application functionality">
                                            <i class="fas fa-lock text-[9px]"></i> System
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($entity['is_searchable'])): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800" title="Recognized by entity linker">
                                            Searchable
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    onclick="openPropertyModal(<?= (int)$entity['id'] ?>, <?= htmlspecialchars(json_encode((string)$entity['name']), ENT_QUOTES, 'UTF-8') ?>)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors">
                                <i class="fas fa-plus text-[10px]"></i>
                                <span>Add property</span>
                            </button>
                            <button type="button"
                                    onclick="openEditEntityModal(<?= htmlspecialchars(json_encode($entity), ENT_QUOTES, 'UTF-8') ?>)"
                                    class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 rounded-lg transition-colors"
                                    title="Edit entity details">
                                <i class="fas fa-edit text-xs"></i>
                            </button>
                            <?php if (!$isCoreEntity && empty($entity['is_system'])): ?>
                                <button type="button"
                                        onclick="confirmDeleteEntity(<?= (int)$entity['id'] ?>, <?= htmlspecialchars(json_encode((string)$entity['name']), ENT_QUOTES, 'UTF-8') ?>)"
                                        class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors"
                                        title="Delete entity type">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Properties table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50/40 text-slate-500 uppercase tracking-wider font-semibold">
                                <tr>
                                    <th class="px-6 py-2.5 text-left">Property key</th>
                                    <th class="px-6 py-2.5 text-left">Display label</th>
                                    <th class="px-6 py-2.5 text-left">Data type</th>
                                    <th class="px-6 py-2.5 text-center">Unique key</th>
                                    <th class="px-6 py-2.5 text-center">Visual label</th>
                                    <th class="px-6 py-2.5 text-center">Searchable</th>
                                    <th class="px-6 py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <?php if (empty($entity['properties'])): ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-slate-400 italic">No properties defined yet. Click "Add property" above to create one.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($entity['properties'] as $prop): ?>
                                        <?php
                                            $isMandatedProp = $isCoreEntity && isset($coreSpecs[$entity['name']]['required_properties'][$prop['name']]);
                                        ?>
                                        <tr class="hover:bg-slate-50/70 transition-colors" id="prop-row-<?= (int)$prop['id'] ?>">
                                            <td class="px-6 py-3 font-mono font-semibold text-slate-800">
                                                <?= htmlspecialchars($prop['name'], ENT_QUOTES, 'UTF-8') ?>
                                                <?php if ($isMandatedProp): ?>
                                                    <i class="fas fa-lock text-[10px] text-indigo-500 ml-1" title="Mandated architecture property"></i>
                                                <?php elseif (!empty($prop['is_system'])): ?>
                                                    <i class="fas fa-lock text-[10px] text-slate-400 ml-1" title="Protected property"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-3 text-slate-700">
                                                <?= htmlspecialchars($prop['display_name'], ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td class="px-6 py-3">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                                    <?= htmlspecialchars($prop['data_type'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-3 text-center">
                                                <?php if (!empty($prop['is_unique'])): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-100 text-indigo-700">Unique</span>
                                                <?php else: ?>
                                                    <span class="text-slate-300">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-3 text-center">
                                                <?php if (!empty($prop['is_visual_distinguishing'])): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800">Visual</span>
                                                <?php else: ?>
                                                    <span class="text-slate-300">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-3 text-center">
                                                <?php if (!empty($prop['is_searchable'])): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">Search</span>
                                                <?php else: ?>
                                                    <span class="text-slate-300">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-3 text-right">
                                                <div class="inline-flex items-center gap-1.5">
                                                    <button type="button"
                                                            onclick="openEditPropertyModal(<?= (int)$entity['id'] ?>, <?= htmlspecialchars(json_encode($prop), ENT_QUOTES, 'UTF-8') ?>, <?= $isMandatedProp ? 'true' : 'false' ?>)"
                                                            class="p-1 text-slate-500 hover:text-slate-800 rounded transition-colors"
                                                            title="Edit property">
                                                        <i class="fas fa-edit text-xs"></i>
                                                    </button>
                                                    <?php if (!$isMandatedProp && empty($prop['is_system'])): ?>
                                                        <button type="button"
                                                                onclick="confirmDeleteProperty(<?= (int)$prop['id'] ?>, <?= htmlspecialchars(json_encode((string)$prop['name']), ENT_QUOTES, 'UTF-8') ?>)"
                                                                class="p-1 text-rose-500 hover:text-rose-700 rounded transition-colors cursor-pointer"
                                                                title="Delete property">
                                                            <i class="fas fa-trash-alt text-xs"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<!-- Entity Modal (Create & Edit) -->
<div id="entityModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        <form id="entityForm" onsubmit="handleEntitySubmit(event)">
            <input type="hidden" name="token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="save_entity">
            <input type="hidden" name="id" id="entityId" value="">

            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-800" id="entityModalTitle">Add entity type</h3>
                <button type="button" onclick="closeModal('entityModal')" class="text-slate-400 hover:text-slate-600 text-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <!-- Locked core warning banner if active -->
                <div id="modalCoreLockedNotice" class="hidden p-3 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900 leading-snug">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-lock text-amber-600 mt-0.5"></i>
                        <span>Custom entity type creation is locked. You must define the remaining core architecture entities (Text, Annotation, Variant) first.</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="entityName">Machine name (e.g. Person, Book)</label>
                    <div id="entityNameInputContainer">
                        <input type="text" id="entityName" name="name" required pattern="^[A-Za-z0-9_]+$"
                               class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>
                    <span class="text-[11px] text-slate-500" id="entityNameHint">Must be unique and contain letters, numbers, or underscores. Capitalization is enforced.</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="entityPlural">Plural display label (e.g. People, Books)</label>
                    <input type="text" id="entityPlural" name="plural_label" required
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="entityColor">Visual highlight color</label>
                    <div class="flex items-center gap-3">
                        <input type="text" id="entityColor" name="color" value="rgba(39, 123, 245, 0.6)" required
                               class="flex-1 px-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        <span id="colorIndicator" class="color-preview w-8 h-8 rounded-lg"></span>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <button type="button" onclick="setColor('rgba(39, 123, 245, 0.6)')" class="w-5 h-5 rounded-md border" style="background-color: rgba(39, 123, 245, 0.6);"></button>
                        <button type="button" onclick="setColor('rgba(245, 178, 39, 0.6)')" class="w-5 h-5 rounded-md border" style="background-color: rgba(245, 178, 39, 0.6);"></button>
                        <button type="button" onclick="setColor('rgba(28, 200, 28, 0.6)')" class="w-5 h-5 rounded-md border" style="background-color: rgba(28, 200, 28, 0.6);"></button>
                        <button type="button" onclick="setColor('rgba(200, 28, 28, 0.6)')" class="w-5 h-5 rounded-md border" style="background-color: rgba(200, 28, 28, 0.6);"></button>
                        <button type="button" onclick="setColor('rgba(145, 100, 52, 0.6)')" class="w-5 h-5 rounded-md border" style="background-color: rgba(145, 100, 52, 0.6);"></button>
                        <button type="button" onclick="setColor('rgba(200, 20, 200, 0.6)')" class="w-5 h-5 rounded-md border" style="background-color: rgba(200, 20, 200, 0.6);"></button>
                        <button type="button" onclick="setColor('rgba(100, 100, 100, 0.6)')" class="w-5 h-5 rounded-md border" style="background-color: rgba(100, 100, 100, 0.6);"></button>
                    </div>
                </div>

                <div class="pt-2 flex items-center gap-2">
                    <input type="checkbox" id="entitySearchable" name="is_searchable" value="1" checked
                           class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-slate-300">
                    <label class="text-xs font-semibold text-slate-700" for="entitySearchable">Searchable (highlight in entity linking tool)</label>
                </div>
            </div>

            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="closeModal('entityModal')"
                        class="px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" id="entitySubmitBtn"
                        class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 shadow-xs">
                    Save entity
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Property Modal (Create & Edit) -->
<div id="propertyModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        <form id="propertyForm" onsubmit="handlePropertySubmit(event)">
            <input type="hidden" name="token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="save_property">
            <input type="hidden" name="id" id="propertyId" value="">
            <input type="hidden" name="entity_type_id" id="propertyEntityTypeId" value="">

            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-800" id="propertyModalTitle">Add property</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Entity: <span class="font-semibold text-indigo-600" id="propertyModalEntityName"></span></p>
                </div>
                <button type="button" onclick="closeModal('propertyModal')" class="text-slate-400 hover:text-slate-600 text-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div id="mandatedPropNotice" class="hidden p-3 rounded-lg bg-indigo-50 border border-indigo-200 text-xs text-indigo-900 leading-snug">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-shield-alt text-indigo-600 mt-0.5"></i>
                        <span>This is a mandated core architecture property. Its key name and data type are required by the system.</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="propertyName">Machine property key (forced lowercase, e.g. label, birth_date)</label>
                    <input type="text" id="propertyName" name="name" required pattern="^[a-z0-9_]+$"
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    <span class="text-[11px] text-slate-500">Must be lowercase and cannot contain spaces.</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="propertyDisplayName">Display label (e.g. Birth date)</label>
                    <input type="text" id="propertyDisplayName" name="display_name" required
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    <span class="text-[11px] text-slate-500">Labels of the properties are free to be chosen by the user.</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="propertyDataType">Data type</label>
                    <select id="propertyDataType" name="data_type"
                            class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        <option value="string">String (plain text)</option>
                        <option value="int">Integer (whole number)</option>
                        <option value="float">Float (decimal number)</option>
                        <option value="bool">Boolean (true / false toggle)</option>
                        <option value="uri">URI (hyperlink / URL)</option>
                        <option value="wikidata">Wikidata (Q-identifier with live API lookup)</option>
                    </select>
                </div>

                <div class="pt-2 space-y-2.5">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="propUnique" name="is_unique" value="1"
                               class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-slate-300">
                        <label class="text-xs font-medium text-slate-700" for="propUnique">
                            Unique key <span class="text-slate-400 font-normal">(enforces unique constraint in Neo4j)</span>
                        </label>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="propVisual" name="is_visual_distinguishing" value="1"
                               class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-slate-300">
                        <label class="text-xs font-medium text-slate-700" for="propVisual">
                            Visual distinguishing <span class="text-slate-400 font-normal">(used as the node label in the interface)</span>
                        </label>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="propSearchable" name="is_searchable" value="1" checked
                               class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-slate-300">
                        <label class="text-xs font-medium text-slate-700" for="propSearchable">
                            Searchable <span class="text-slate-400 font-normal">(search interfaces can query this property)</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="closeModal('propertyModal')"
                        class="px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" id="propSubmitBtn"
                        class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 shadow-xs">
                    Save property
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const AJAX_URL = '<?= $baseUrl ?>/admin/ajax/manage_entities.php';
    const CSRF_TOKEN = '<?= $csrfToken ?>';
    const CORE_STATUS = <?= json_encode($coreStatus) ?>;
    const CORE_SPECS = <?= json_encode($coreSpecs) ?>;
    const ENTITIES_LIST = <?= json_encode($entities) ?>;

    function showAlert(message, isError = false) {
        const box = document.getElementById('alertBox');
        box.className = `mb-6 p-4 rounded-xl border text-xs font-medium transition-all ${
            isError ? 'bg-rose-50 border-rose-200 text-rose-800' : 'bg-emerald-50 border-emerald-200 text-emerald-800'
        }`;
        box.innerHTML = `<div class="flex items-center gap-2"><i class="fas ${isError ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i><span>${message}</span></div>`;
        box.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    function setColor(color) {
        document.getElementById('entityColor').value = color;
        document.getElementById('colorIndicator').style.backgroundColor = color;
    }

    document.getElementById('entityColor').addEventListener('input', function(e) {
        document.getElementById('colorIndicator').style.backgroundColor = e.target.value;
    });

    function openEntityModal() {
        const modal = document.getElementById('entityModal');
        const title = document.getElementById('entityModalTitle');
        const idInput = document.getElementById('entityId');
        const pluralInput = document.getElementById('entityPlural');
        const lockNotice = document.getElementById('modalCoreLockedNotice');
        const nameContainer = document.getElementById('entityNameInputContainer');
        const hint = document.getElementById('entityNameHint');

        title.textContent = 'Add entity type';
        idInput.value = '';
        pluralInput.value = '';
        setColor('rgba(39, 123, 245, 0.6)');
        document.getElementById('entitySearchable').checked = true;

        if (!CORE_STATUS.all_core_defined) {
            lockNotice.classList.remove('hidden');
            const missing = CORE_STATUS.missing_core_types || [];
            let optionsHtml = '';
            missing.forEach(name => {
                const spec = CORE_SPECS[name] || {};
                optionsHtml += `<option value="${name}">${name} (${spec.plural_label || name})</option>`;
            });

            if (missing.length > 0) {
                nameContainer.innerHTML = `
                    <select id="entityName" name="name" onchange="handleCoreSelectChange(this.value)"
                            class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        ${optionsHtml}
                    </select>
                `;
                hint.textContent = 'Select one of the missing core architecture entities to define it.';
                handleCoreSelectChange(missing[0]);
            } else {
                nameContainer.innerHTML = `
                    <input type="text" id="entityName" name="name" required pattern="^[A-Za-z0-9_]+$"
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                `;
            }
        } else {
            lockNotice.classList.add('hidden');
            nameContainer.innerHTML = `
                <input type="text" id="entityName" name="name" required pattern="^[A-Za-z0-9_]+$"
                       class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            `;
            hint.textContent = 'Must be unique and contain letters, numbers, or underscores.';
        }

        modal.classList.remove('hidden');
    }

    function handleCoreSelectChange(val) {
        const spec = CORE_SPECS[val];
        if (spec) {
            document.getElementById('entityPlural').value = spec.plural_label || val;
            setColor(spec.color || 'rgba(39, 123, 245, 0.6)');
            document.getElementById('entitySearchable').checked = Boolean(spec.is_searchable);
        }
    }

    function openEditEntityModal(entity) {
        document.getElementById('modalCoreLockedNotice').classList.add('hidden');
        document.getElementById('entityModalTitle').textContent = 'Edit entity type: ' + entity.name;
        document.getElementById('entityId').value = entity.id;

        const nameContainer = document.getElementById('entityNameInputContainer');
        const isCoreOrSystem = Boolean(Number(entity.is_system)) || Boolean(CORE_SPECS[entity.name]);
        nameContainer.innerHTML = `
            <input type="text" id="entityName" name="name" value="${escapeHtml(entity.name)}" ${isCoreOrSystem ? 'readonly' : ''}
                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 ${isCoreOrSystem ? 'bg-slate-100 cursor-not-allowed' : ''} focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
        `;
        document.getElementById('entityNameHint').textContent = isCoreOrSystem ? 'Core architecture and system entities cannot be renamed.' : 'Unique machine identifier.';
        document.getElementById('entityPlural').value = entity.plural_label;
        setColor(entity.color);
        document.getElementById('entitySearchable').checked = Boolean(Number(entity.is_searchable));
        document.getElementById('entityModal').classList.remove('hidden');
    }

    function openPropertyModal(entityId, entityName) {
        document.getElementById('mandatedPropNotice').classList.add('hidden');
        document.getElementById('propertyModalTitle').textContent = 'Add property';
        document.getElementById('propertyModalEntityName').textContent = entityName;
        document.getElementById('propertyId').value = '';
        document.getElementById('propertyEntityTypeId').value = entityId;

        const nameInput = document.getElementById('propertyName');
        nameInput.value = '';
        nameInput.readOnly = false;
        nameInput.className = 'w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500';

        document.getElementById('propertyDisplayName').value = '';
        const typeSelect = document.getElementById('propertyDataType');
        typeSelect.value = 'string';
        typeSelect.disabled = false;

        // An entity type cannot have more than one property with the wikidata type
        const ent = ENTITIES_LIST.find(item => Number(item.id) === Number(entityId));
        const hasExistingWd = ent && Array.isArray(ent.properties) && ent.properties.some(p => p.data_type === 'wikidata');
        const wdOption = typeSelect.querySelector('option[value="wikidata"]');
        if (wdOption) {
            wdOption.disabled = Boolean(hasExistingWd);
            wdOption.textContent = hasExistingWd ? 'Wikidata (only one allowed per entity)' : 'Wikidata (Q-identifier with live API lookup)';
        }

        document.getElementById('propUnique').checked = false;
        document.getElementById('propVisual').checked = false;
        document.getElementById('propSearchable').checked = true;
        document.getElementById('propertyModal').classList.remove('hidden');
    }

    function openEditPropertyModal(entityId, prop, isMandated = false) {
        const notice = document.getElementById('mandatedPropNotice');
        if (isMandated) {
            notice.classList.remove('hidden');
        } else {
            notice.classList.add('hidden');
        }

        document.getElementById('propertyModalTitle').textContent = 'Edit property: ' + prop.name;
        document.getElementById('propertyId').value = prop.id;
        document.getElementById('propertyEntityTypeId').value = entityId;

        const nameInput = document.getElementById('propertyName');
        nameInput.value = prop.name;
        nameInput.readOnly = isMandated || Boolean(Number(prop.is_system));
        nameInput.className = `w-full px-3 py-2 text-xs rounded-lg border border-slate-300 ${nameInput.readOnly ? 'bg-slate-100 cursor-not-allowed' : ''} focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500`;

        document.getElementById('propertyDisplayName').value = prop.display_name;
        const typeSelect = document.getElementById('propertyDataType');
        typeSelect.value = prop.data_type;
        typeSelect.disabled = isMandated;

        // An entity type cannot have more than one property with the wikidata type
        const ent = ENTITIES_LIST.find(item => Number(item.id) === Number(entityId));
        const hasExistingWd = ent && Array.isArray(ent.properties) && ent.properties.some(p => p.data_type === 'wikidata' && Number(p.id) !== Number(prop.id));
        const wdOption = typeSelect.querySelector('option[value="wikidata"]');
        if (wdOption) {
            wdOption.disabled = Boolean(hasExistingWd);
            wdOption.textContent = hasExistingWd ? 'Wikidata (only one allowed per entity)' : 'Wikidata (Q-identifier with live API lookup)';
        }

        document.getElementById('propUnique').checked = Boolean(Number(prop.is_unique));
        document.getElementById('propVisual').checked = Boolean(Number(prop.is_visual_distinguishing));
        document.getElementById('propSearchable').checked = Boolean(Number(prop.is_searchable));
        document.getElementById('propertyModal').classList.remove('hidden');
    }

    async function handleEntitySubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('entitySubmitBtn');
        btn.disabled = true;

        // Force capitalization of node names (e.g. Text); and force lowercasing of node properties)
        const nameInput = document.getElementById('entityName');
        if (nameInput && nameInput.value) {
            const raw = nameInput.value.replace(/\s+/g, '');
            nameInput.value = raw.charAt(0).toUpperCase() + raw.slice(1);
        }

        const formData = new FormData(document.getElementById('entityForm'));
        if (formData.has('name')) {
            const raw = String(formData.get('name') || '').replace(/\s+/g, '');
            formData.set('name', raw.charAt(0).toUpperCase() + raw.slice(1));
        }

        try {
            const resp = await fetch(AJAX_URL, {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();
            if (data.status === 'success') {
                closeModal('entityModal');
                window.location.reload();
            } else {
                showAlert(data.message || 'Operation failed.', true);
            }
        } catch (err) {
            showAlert('Server request error.', true);
        } finally {
            btn.disabled = false;
        }
    }

    async function handlePropertySubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('propSubmitBtn');
        btn.disabled = true;

        // Force capitalization of node names (e.g. Text); and force lowercasing of node properties)
        const nameInput = document.getElementById('propertyName');
        if (nameInput && nameInput.value) {
            nameInput.value = nameInput.value.replace(/\s+/g, '').toLowerCase();
        }

        const formData = new FormData(document.getElementById('propertyForm'));
        if (formData.has('name')) {
            formData.set('name', String(formData.get('name') || '').replace(/\s+/g, '').toLowerCase());
        }

        const typeSelect = document.getElementById('propertyDataType');
        if (typeSelect.disabled) {
            formData.set('data_type', typeSelect.value);
        }

        const submittedDataType = formData.get('data_type');
        const entityTypeId = document.getElementById('propertyEntityTypeId').value;
        const propId = document.getElementById('propertyId').value;
        if (submittedDataType === 'wikidata') {
            const ent = ENTITIES_LIST.find(item => Number(item.id) === Number(entityTypeId));
            const hasExistingWd = ent && Array.isArray(ent.properties) && ent.properties.some(p => p.data_type === 'wikidata' && Number(p.id) !== Number(propId || 0));
            if (hasExistingWd) {
                showAlert('An entity type cannot have more than one property with the wikidata type.', true);
                btn.disabled = false;
                return;
            }
        }

        try {
            const resp = await fetch(AJAX_URL, {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();
            if (data.status === 'success') {
                closeModal('propertyModal');
                window.location.reload();
            } else {
                showAlert(data.message || 'Operation failed.', true);
            }
        } catch (err) {
            showAlert('Server request error.', true);
        } finally {
            btn.disabled = false;
        }
    }

    async function confirmDeleteEntity(id, name) {
        if (!confirm(`Are you sure you want to delete entity type "${name}" and all its properties?`)) {
            return;
        }
        const formData = new FormData();
        formData.append('token', CSRF_TOKEN);
        formData.append('action', 'delete_entity');
        formData.append('id', id);

        try {
            const resp = await fetch(AJAX_URL, { method: 'POST', body: formData });
            const data = await resp.json();
            if (data.status === 'success') {
                window.location.reload();
            } else {
                showAlert(data.message, true);
            }
        } catch (err) {
            showAlert('Failed to delete entity type.', true);
        }
    }

    async function confirmDeleteProperty(id, name) {
        if (!confirm(`Are you sure you want to delete property "${name}"?`)) {
            return;
        }
        const formData = new FormData();
        formData.append('token', CSRF_TOKEN);
        formData.append('action', 'delete_property');
        formData.append('id', id);

        try {
            const resp = await fetch(AJAX_URL, { method: 'POST', body: formData });
            const data = await resp.json();
            if (data.status === 'success') {
                window.location.reload();
            } else {
                showAlert(data.message, true);
            }
        } catch (err) {
            showAlert('Failed to delete property.', true);
        }
    }

    async function scaffoldAllCoreTypes() {
        const formData = new FormData();
        formData.append('token', CSRF_TOKEN);
        formData.append('action', 'scaffold_core_entities');

        try {
            const resp = await fetch(AJAX_URL, { method: 'POST', body: formData });
            const data = await resp.json();
            if (data.status === 'success') {
                window.location.reload();
            } else {
                showAlert(data.message || 'Failed to scaffold core entities.', true);
            }
        } catch (err) {
            showAlert('Server request error.', true);
        }
    }

    async function scaffoldCoreType(typeName) {
        const formData = new FormData();
        formData.append('token', CSRF_TOKEN);
        formData.append('action', 'scaffold_single_core');
        formData.append('type_name', typeName);

        try {
            const resp = await fetch(AJAX_URL, { method: 'POST', body: formData });
            const data = await resp.json();
            if (data.status === 'success') {
                window.location.reload();
            } else {
                showAlert(data.message || 'Failed to scaffold core entity.', true);
            }
        } catch (err) {
            showAlert('Server request error.', true);
        }
    }

    async function resetToEmptySchema() {
        if (!confirm('Warning: this will delete all entity types and property definitions, resetting the schema to the empty post-initialization state. Do you wish to proceed?')) {
            return;
        }
        const formData = new FormData();
        formData.append('token', CSRF_TOKEN);
        formData.append('action', 'reset_entity_model');

        try {
            const resp = await fetch(AJAX_URL, { method: 'POST', body: formData });
            const data = await resp.json();
            if (data.status === 'success') {
                window.location.reload();
            } else {
                showAlert(data.message, true);
            }
        } catch (err) {
            showAlert('Server request error.', true);
        }
    }

    async function triggerCacheRebuild() {
        const formData = new FormData();
        formData.append('token', CSRF_TOKEN);
        formData.append('action', 'rebuild_cache');

        try {
            const resp = await fetch(AJAX_URL, { method: 'POST', body: formData });
            const data = await resp.json();
            if (data.status === 'success') {
                showAlert('Configuration cache rebuilt successfully.');
            } else {
                showAlert(data.message, true);
            }
        } catch (err) {
            showAlert('Failed to rebuild cache.', true);
        }
    }

    async function triggerNeoSync() {
        if (!confirm('Synchronize database constraints and indexes in Neo4j based on the current model?')) {
            return;
        }
        const formData = new FormData();
        formData.append('token', CSRF_TOKEN);
        formData.append('action', 'sync_neo4j');

        try {
            const resp = await fetch(AJAX_URL, { method: 'POST', body: formData });
            const data = await resp.json();
            if (data.status === 'success') {
                showAlert(data.message);
            } else {
                showAlert(data.message, true);
            }
        } catch (err) {
            showAlert('Failed to synchronize with Neo4j.', true);
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Force capitalization of node names (e.g. Text); and force lowercasing of node properties)
    document.addEventListener('input', function(e) {
        if (e.target && e.target.id === 'entityName') {
            const raw = e.target.value.replace(/\s+/g, '');
            if (raw.length > 0) {
                e.target.value = raw.charAt(0).toUpperCase() + raw.slice(1);
            } else {
                e.target.value = '';
            }
        } else if (e.target && e.target.id === 'propertyName') {
            e.target.value = e.target.value.replace(/\s+/g, '').toLowerCase();
        }
    });
</script>
</body>
</html>
