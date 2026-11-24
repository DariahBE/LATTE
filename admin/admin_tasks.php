<?php
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
$adminBase = (defined('BASE_URL') ? BASE_URL : '') . '/admin';

$navItems = [
    [
        'label' => 'Dashboard',
        'icon' => 'fas fa-chart-line',
        'href' => $adminBase . '/index.php',
        'active' => strpos($currentScript, 'admin/index.php') !== false,
    ],
    [
        'label' => 'User management',
        'icon' => 'fas fa-users-cog',
        'href' => $adminBase . '/usermanagement/index.php',
        'active' => strpos($currentScript, 'usermanagement') !== false,
    ],
    [
        'label' => 'Entity management',
        'icon' => 'fas fa-cubes',
        'href' => $adminBase . '/entities.php',
        'active' => strpos($currentScript, 'entities.php') !== false,
    ],
    [
        'label' => 'System settings',
        'icon' => 'fas fa-sliders-h',
        'href' => $adminBase . '/settings.php',
        'active' => strpos($currentScript, 'settings.php') !== false,
    ],
    [
        'label' => 'Database validation',
        'icon' => 'fas fa-heartbeat',
        'href' => $adminBase . '/validate_db.php',
        'active' => strpos($currentScript, 'validate_db.php') !== false,
    ],
    [
        'label' => 'Index management',
        'icon' => 'fas fa-bolt',
        'href' => $adminBase . '/mod_indexes.php',
        'active' => strpos($currentScript, 'mod_indexes.php') !== false,
    ],
    [
        'label' => 'Bulk text importer',
        'icon' => 'fas fa-file-import',
        'href' => $adminBase . '/bulk_text_loader.php',
        'active' => strpos($currentScript, 'bulk_text_loader.php') !== false,
    ],
    [
        'label' => 'LATTE queue',
        'icon' => 'fas fa-tasks',
        'href' => $adminBase . '/connector_queue.php',
        'active' => strpos($currentScript, 'connector_queue.php') !== false,
    ],
];
?>
<div class="bg-white border-b border-slate-200/80 shadow-xs mb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 text-sm font-semibold">
                    <i class="fas fa-shield-alt"></i>
                </span>
                <span class="text-sm font-bold tracking-tight text-slate-800">Admin control panel</span>
            </div>
            <nav class="flex flex-wrap items-center gap-1.5" aria-label="Admin sub navigation">
                <?php foreach ($navItems as $item): ?>
                    <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
                       class="inline-flex items-center gap-2 px-3.5 py-1.5 text-xs font-medium rounded-lg transition-all <?= $item['active']
                           ? 'bg-indigo-600 text-white shadow-xs font-semibold'
                           : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                        <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
</div>
