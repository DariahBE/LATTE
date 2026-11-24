<?php
$configFile = dirname(__DIR__) . "/config/config.inc.php";
if (!file_exists($configFile)) {
    header("Location: ../onboarding/index.php");
    exit();
}
if (!defined('ROOT_DIR')) {
    include_once($configFile);
}
include_once(ROOT_DIR . "/includes/client.inc.php");
include_once(ROOT_DIR . "/includes/user.inc.php");
include_once(ROOT_DIR . "/includes/navbar.inc.php");
include_once(ROOT_DIR . "/includes/latte_queue_manager.inc.php");

$user = new User($client);
if ($user->checkSession() === false) {
    $loginUrl = (defined('BASE_URL') ? BASE_URL : '') . "/user/login.php?redir=" . urlencode((defined('BASE_URL') ? BASE_URL : '') . "/admin/connector_queue.php");
    header("Location: $loginUrl");
    exit();
}

if (!$user->isAdmin()) {
    header("HTTP/1.0 403 Forbidden");
    die("Insufficient rights, forbidden access");
}

// Handle daemon ajax requests for live status polling and toggle actions.
if (isset($_GET['ajax_daemon']) || isset($_POST['ajax_daemon'])) {
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'start_daemon') {
            $started = LatteQueueManager::startDaemon();
            $info = LatteQueueManager::isDaemonRunning();
            echo json_encode([
                'success' => $started,
                'running' => $info['running'],
                'pid' => $info['pid'],
                'message' => $started ? 'Background worker daemon started successfully.' : 'Could not start background worker daemon.'
            ]);
            exit;
        } elseif ($action === 'stop_daemon') {
            LatteQueueManager::stopDaemon();
            $info = LatteQueueManager::isDaemonRunning();
            echo json_encode([
                'success' => true,
                'running' => $info['running'],
                'pid' => $info['pid'],
                'message' => 'Stop signal sent to background worker daemon.'
            ]);
            exit;
        }
    }
    // Return live daemon status.
    $info = LatteQueueManager::isDaemonRunning();
    echo json_encode([
        'running' => $info['running'],
        'pid' => $info['pid']
    ]);
    exit;
}

$adminMode = true;
$baseUrl = defined('BASE_URL') ? BASE_URL : '';

$message = '';
$messageType = '';

// Handle actions and settings form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        $queueEnabled = isset($_POST['queue_enabled']);
        $maxRetries = max(1, min(10, (int)($_POST['max_retries'] ?? 3)));
        $schedule = trim($_POST['weekly_schedule'] ?? '');

        $updateData = [
            'queue_enabled' => $queueEnabled,
            'max_retries' => $maxRetries
        ];

        if (strlen($schedule) === 672 && preg_match('/^[01]{672}$/', $schedule)) {
            $updateData['weekly_schedule'] = $schedule;
        }

        LatteQueueManager::updateSettings($updateData);
        $message = 'Background processing weekly schedule and queue settings updated successfully.';
        $messageType = 'success';
    } elseif ($action === 'scan') {
        try {
            $enqueued = LatteQueueManager::scanAndEnqueueUnparsedTexts($client);
            $message = "Graph database scanned. {$enqueued} new unparsed texts enqueued.";
            $messageType = 'success';
        } catch (\Throwable $e) {
            $message = 'Scan failed: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'force_process') {
        $stats = LatteQueueManager::getQueueStats();
        if ($stats['pending'] === 0) {
            $message = 'No pending tasks in the queue to process.';
            $messageType = 'info';
        } else {
            $dispatched = LatteQueueManager::spawnWorkerProcess('--force');
            if ($dispatched) {
                $message = "Background worker dispatched ({$stats['pending']} tasks pending).";
                $messageType = 'success';
            } else {
                $message = 'Could not spawn background worker process.';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'process_task_now') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        if ($taskId > 0) {
            $task = LatteQueueManager::getTaskById($taskId);
            if ($task) {
                $dispatched = LatteQueueManager::spawnWorkerProcess('--task-id ' . $taskId . ' --force');
                if ($dispatched) {
                    $message = "Background worker dispatched for task #{$taskId} (text #{$task['text_prop_id']}).";
                    $messageType = 'success';
                } else {
                    $message = "Could not spawn background worker for task #{$taskId}.";
                    $messageType = 'error';
                }
            } else {
                $message = "Task #{$taskId} not found.";
                $messageType = 'error';
            }
        }
    } elseif ($action === 'process_next') {
        $task = LatteQueueManager::fetchNextPendingTask();
        // If outside window, allow administrator to force-process the highest pending task.
        if (!$task) {
            $pdo = LatteQueueManager::getPdo();
            $stmt = $pdo->query("SELECT * FROM latte_queue WHERE status = 'pending' ORDER BY priority DESC, queued_at ASC LIMIT 1");
            $task = $stmt->fetch() ?: null;
        }

        if ($task) {
            $dispatched = LatteQueueManager::spawnWorkerProcess('--task-id ' . (int)$task['id'] . ' --force');
            if ($dispatched) {
                $message = "Background worker dispatched for task #{$task['id']} (text ID #{$task['text_prop_id']}).";
                $messageType = 'success';
            } else {
                $message = "Failed to dispatch worker for task #{$task['id']}.";
                $messageType = 'error';
            }
        } else {
            $message = 'No pending tasks to process.';
            $messageType = 'info';
        }
    } elseif ($action === 'run_cron_cycle') {
        try {
            $scanCount = LatteQueueManager::scanAndEnqueueUnparsedTexts($client);
            $dispatched = LatteQueueManager::spawnWorkerProcess('--cron');
            $slotDetails = LatteQueueManager::getCurrentSlotDetails();
            $windowStatus = $slotDetails['is_active'] ? 'active schedule window' : 'outside schedule window (manual only)';
            if ($dispatched) {
                $message = "Background worker cycle dispatched (scanned {$scanCount} texts, {$windowStatus}).";
                $messageType = 'success';
            } else {
                $message = "Scanned {$scanCount} texts, but could not spawn background worker.";
                $messageType = 'error';
            }
        } catch (\Throwable $e) {
            $message = "Worker cycle failed: " . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'retry_failed') {
        $retried = LatteQueueManager::retryFailedTasks();
        $message = "{$retried} failed tasks reset to pending status.";
        $messageType = 'success';
    } elseif ($action === 'clear_completed') {
        $cleared = LatteQueueManager::clearCompletedTasks();
        $message = "{$cleared} completed task records removed from queue history.";
        $messageType = 'success';
    } elseif ($action === 'start_daemon') {
        $started = LatteQueueManager::startDaemon();
        if ($started) {
            $message = 'Background worker daemon started successfully.';
            $messageType = 'success';
        } else {
            $message = 'Could not start background worker daemon.';
            $messageType = 'error';
        }
    } elseif ($action === 'stop_daemon') {
        LatteQueueManager::stopDaemon();
        $message = 'Stop signal sent to background worker daemon.';
        $messageType = 'info';
    } elseif ($action === 'delete_task') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        if ($taskId > 0) {
            $pdo = LatteQueueManager::getPdo();
            $stmt = $pdo->prepare("DELETE FROM latte_queue WHERE id = :id");
            $stmt->execute([':id' => $taskId]);
            $message = "Task #{$taskId} deleted.";
            $messageType = 'success';
        }
    }
}

// Fetch current queue state, schedule, and metrics.
$settings = LatteQueueManager::getSettings();
$stats = LatteQueueManager::getQueueStats();
$slotDetails = LatteQueueManager::getCurrentSlotDetails();
$weeklySchedule = $settings['weekly_schedule'] ?? LatteQueueManager::getWeeklySchedule();
$daemonInfo = LatteQueueManager::isDaemonRunning();

$dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

$statusFilter = isset($_GET['status']) && in_array($_GET['status'], ['pending', 'processing', 'completed', 'failed']) ? $_GET['status'] : null;
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 25;

// Whitelist allowed sort columns.
$allowedSortCols = ['id', 'title', 'priority', 'status', 'attempts', 'queued_at', 'completed_at', 'default'];
$sortBy = isset($_GET['sort']) && in_array(strtolower($_GET['sort']), $allowedSortCols, true) ? strtolower($_GET['sort']) : 'default';
$sortDir = isset($_GET['dir']) && strtolower($_GET['dir']) === 'asc' ? 'asc' : 'desc';

$totalTasks = LatteQueueManager::getQueueItemsCount($statusFilter);
$totalPages = max(1, (int)ceil($totalTasks / $limit));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $limit;

$tasks = LatteQueueManager::getQueueItems($limit, $offset, $statusFilter, $sortBy, $sortDir);

function buildQueueUrl(array $params = []): string {
    global $statusFilter, $sortBy, $sortDir, $page;
    $current = [
        'status' => $statusFilter,
        'sort' => ($sortBy !== 'default') ? $sortBy : null,
        'dir' => ($sortDir !== 'desc') ? $sortDir : null,
        'page' => ($page > 1) ? $page : null,
    ];
    $merged = array_merge($current, $params);
    $filtered = array_filter($merged, function($v) { return $v !== null && $v !== ''; });
    $query = http_build_query($filtered);
    return 'connector_queue.php' . ($query ? ('?' . $query) : '');
}

function renderQueueTableContent() {
    global $tasks, $totalTasks, $totalPages, $page, $limit, $offset, $statusFilter, $sortBy, $sortDir, $stats, $message, $messageType;
    ?>
    <div id="queueMetricsData" class="hidden"
         data-total="<?php echo (int)($stats['total'] ?? 0); ?>"
         data-pending="<?php echo (int)($stats['pending'] ?? 0); ?>"
         data-processing="<?php echo (int)($stats['processing'] ?? 0); ?>"
         data-completed="<?php echo (int)($stats['completed'] ?? 0); ?>"
         data-failed="<?php echo (int)($stats['failed'] ?? 0); ?>">
    </div>
    <?php if (!empty($message)): ?>
    <?php
      $alertBg = ($messageType === 'error') ? 'bg-rose-50 border-rose-100 text-rose-800' : 'bg-emerald-50 border-emerald-100 text-emerald-800';
      $iconClass = ($messageType === 'error') ? 'fa-exclamation-circle text-rose-600' : 'fa-check-circle text-emerald-600';
    ?>
    <div class="px-6 py-2.5 <?php echo $alertBg; ?> border-b text-xs font-semibold flex items-center justify-between">
      <div class="flex items-center gap-2">
        <i class="fas <?php echo $iconClass; ?>"></i>
        <span><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <button type="button" onclick="this.parentElement.remove();" class="text-slate-400 hover:text-slate-700 text-xs"><i class="fas fa-times"></i></button>
    </div>
    <?php endif; ?>
    <!-- Top bar: Header, Total badge, and Filter tabs -->
    <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <h2 class="text-sm font-bold text-slate-900">Task queue entries</h2>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
          Total: <?php echo number_format($totalTasks); ?>
        </span>
        <span id="queueTableSpinner" class="hidden text-indigo-600 text-xs ml-2 font-medium">
          <i class="fas fa-spinner fa-spin"></i> Loading...
        </span>
      </div>

      <!-- Filter tabs -->
      <div class="flex items-center gap-1 text-xs font-medium">
        <a href="<?php echo buildQueueUrl(['status' => null, 'page' => 1]); ?>" class="queue-ajax-link px-2.5 py-1 rounded-lg <?php echo !$statusFilter ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-600 hover:bg-slate-100'; ?>">All (<?php echo number_format($stats['total'] ?? 0); ?>)</a>
        <a href="<?php echo buildQueueUrl(['status' => 'pending', 'page' => 1]); ?>" class="queue-ajax-link px-2.5 py-1 rounded-lg <?php echo ($statusFilter === 'pending') ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-600 hover:bg-slate-100'; ?>">Pending (<?php echo number_format($stats['pending'] ?? 0); ?>)</a>
        <a href="<?php echo buildQueueUrl(['status' => 'processing', 'page' => 1]); ?>" class="queue-ajax-link px-2.5 py-1 rounded-lg <?php echo ($statusFilter === 'processing') ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-600 hover:bg-slate-100'; ?>">Processing (<?php echo number_format($stats['processing'] ?? 0); ?>)</a>
        <a href="<?php echo buildQueueUrl(['status' => 'completed', 'page' => 1]); ?>" class="queue-ajax-link px-2.5 py-1 rounded-lg <?php echo ($statusFilter === 'completed') ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-600 hover:bg-slate-100'; ?>">Completed (<?php echo number_format($stats['completed'] ?? 0); ?>)</a>
        <a href="<?php echo buildQueueUrl(['status' => 'failed', 'page' => 1]); ?>" class="queue-ajax-link px-2.5 py-1 rounded-lg <?php echo ($statusFilter === 'failed') ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-600 hover:bg-slate-100'; ?>">Failed (<?php echo number_format($stats['failed'] ?? 0); ?>)</a>
      </div>
    </div>

    <!-- Pagination bar (placed ABOVE the table) -->
    <?php if ($totalTasks > 0): ?>
    <div class="px-6 py-3 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50/60 text-xs text-slate-600">
      <div>
        Showing <span class="font-semibold text-slate-900"><?php echo $totalTasks > 0 ? ($offset + 1) : 0; ?></span> to <span class="font-semibold text-slate-900"><?php echo min($totalTasks, $offset + count($tasks)); ?></span> of <span class="font-semibold text-slate-900"><?php echo number_format($totalTasks); ?></span> tasks
      </div>

      <?php if ($totalPages > 1): ?>
      <nav class="inline-flex items-center gap-1" aria-label="Queue pagination">
        <!-- Previous page link -->
        <?php if ($page > 1): ?>
          <a href="<?php echo buildQueueUrl(['page' => $page - 1]); ?>" class="queue-ajax-link px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 font-medium text-slate-700 transition">
            <i class="fas fa-chevron-left text-[10px] mr-1"></i> Previous
          </a>
        <?php else: ?>
          <span class="px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-100 text-slate-400 font-medium cursor-not-allowed">
            <i class="fas fa-chevron-left text-[10px] mr-1"></i> Previous
          </span>
        <?php endif; ?>

        <!-- Page numbers with smart ellipsis -->
        <?php
          $startPage = max(1, $page - 2);
          $endPage = min($totalPages, $page + 2);

          if ($startPage > 1) {
            echo '<a href="' . buildQueueUrl(['page' => 1]) . '" class="queue-ajax-link w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 bg-white hover:bg-slate-50 font-medium text-slate-700 transition">1</a>';
            if ($startPage > 2) {
              echo '<span class="w-5 text-center text-slate-400 font-bold">&hellip;</span>';
            }
          }

          for ($p = $startPage; $p <= $endPage; $p++) {
            if ($p === $page) {
              echo '<span class="w-7 h-7 flex items-center justify-center rounded-lg bg-indigo-600 text-white font-bold">' . $p . '</span>';
            } else {
              echo '<a href="' . buildQueueUrl(['page' => $p]) . '" class="queue-ajax-link w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 bg-white hover:bg-slate-50 font-medium text-slate-700 transition">' . $p . '</a>';
            }
          }

          if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) {
              echo '<span class="w-5 text-center text-slate-400 font-bold">&hellip;</span>';
            }
            echo '<a href="' . buildQueueUrl(['page' => $totalPages]) . '" class="queue-ajax-link w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 bg-white hover:bg-slate-50 font-medium text-slate-700 transition">' . $totalPages . '</a>';
          }
        ?>

        <!-- Next page link -->
        <?php if ($page < $totalPages): ?>
          <a href="<?php echo buildQueueUrl(['page' => $page + 1]); ?>" class="queue-ajax-link px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 font-medium text-slate-700 transition">
            Next <i class="fas fa-chevron-right text-[10px] ml-1"></i>
          </a>
        <?php else: ?>
          <span class="px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-100 text-slate-400 font-medium cursor-not-allowed">
            Next <i class="fas fa-chevron-right text-[10px] ml-1"></i>
          </span>
        <?php endif; ?>
      </nav>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Table area -->
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
            <th class="px-4 py-3">
              <a href="<?php echo buildQueueUrl(['sort' => 'id', 'dir' => ($sortBy === 'id' && $sortDir === 'asc') ? 'desc' : 'asc', 'page' => 1]); ?>" class="queue-ajax-link inline-flex items-center gap-1.5 text-slate-700 hover:text-indigo-600 transition group select-none">
                <span>ID</span>
                <?php if ($sortBy === 'id'): ?>
                  <i class="fas fa-sort-<?php echo ($sortDir === 'asc') ? 'up' : 'down'; ?> text-indigo-600"></i>
                <?php else: ?>
                  <i class="fas fa-sort text-slate-300 opacity-60 group-hover:opacity-100"></i>
                <?php endif; ?>
              </a>
            </th>
            <th class="px-4 py-3">
              <a href="<?php echo buildQueueUrl(['sort' => 'title', 'dir' => ($sortBy === 'title' && $sortDir === 'asc') ? 'desc' : 'asc', 'page' => 1]); ?>" class="queue-ajax-link inline-flex items-center gap-1.5 text-slate-700 hover:text-indigo-600 transition group select-none">
                <span>Text document</span>
                <?php if ($sortBy === 'title'): ?>
                  <i class="fas fa-sort-<?php echo ($sortDir === 'asc') ? 'up' : 'down'; ?> text-indigo-600"></i>
                <?php else: ?>
                  <i class="fas fa-sort text-slate-300 opacity-60 group-hover:opacity-100"></i>
                <?php endif; ?>
              </a>
            </th>
            <th class="px-4 py-3">
              <a href="<?php echo buildQueueUrl(['sort' => 'priority', 'dir' => ($sortBy === 'priority' && $sortDir === 'desc') ? 'asc' : 'desc', 'page' => 1]); ?>" class="queue-ajax-link inline-flex items-center gap-1.5 text-slate-700 hover:text-indigo-600 transition group select-none">
                <span>Priority</span>
                <?php if ($sortBy === 'priority'): ?>
                  <i class="fas fa-sort-<?php echo ($sortDir === 'asc') ? 'up' : 'down'; ?> text-indigo-600"></i>
                <?php else: ?>
                  <i class="fas fa-sort text-slate-300 opacity-60 group-hover:opacity-100"></i>
                <?php endif; ?>
              </a>
            </th>
            <th class="px-4 py-3">
              <a href="<?php echo buildQueueUrl(['sort' => 'status', 'dir' => ($sortBy === 'status' && $sortDir === 'asc') ? 'desc' : 'asc', 'page' => 1]); ?>" class="queue-ajax-link inline-flex items-center gap-1.5 text-slate-700 hover:text-indigo-600 transition group select-none">
                <span>Status</span>
                <?php if ($sortBy === 'status'): ?>
                  <i class="fas fa-sort-<?php echo ($sortDir === 'asc') ? 'up' : 'down'; ?> text-indigo-600"></i>
                <?php else: ?>
                  <i class="fas fa-sort text-slate-300 opacity-60 group-hover:opacity-100"></i>
                <?php endif; ?>
              </a>
            </th>
            <th class="px-4 py-3">
              <a href="<?php echo buildQueueUrl(['sort' => 'attempts', 'dir' => ($sortBy === 'attempts' && $sortDir === 'desc') ? 'asc' : 'desc', 'page' => 1]); ?>" class="queue-ajax-link inline-flex items-center gap-1.5 text-slate-700 hover:text-indigo-600 transition group select-none">
                <span>Attempts</span>
                <?php if ($sortBy === 'attempts'): ?>
                  <i class="fas fa-sort-<?php echo ($sortDir === 'asc') ? 'up' : 'down'; ?> text-indigo-600"></i>
                <?php else: ?>
                  <i class="fas fa-sort text-slate-300 opacity-60 group-hover:opacity-100"></i>
                <?php endif; ?>
              </a>
            </th>
            <th class="px-4 py-3">
              <a href="<?php echo buildQueueUrl(['sort' => 'queued_at', 'dir' => ($sortBy === 'queued_at' && $sortDir === 'desc') ? 'asc' : 'desc', 'page' => 1]); ?>" class="queue-ajax-link inline-flex items-center gap-1.5 text-slate-700 hover:text-indigo-600 transition group select-none">
                <span>Queued at</span>
                <?php if ($sortBy === 'queued_at'): ?>
                  <i class="fas fa-sort-<?php echo ($sortDir === 'asc') ? 'up' : 'down'; ?> text-indigo-600"></i>
                <?php else: ?>
                  <i class="fas fa-sort text-slate-300 opacity-60 group-hover:opacity-100"></i>
                <?php endif; ?>
              </a>
            </th>
            <th class="px-4 py-3">
              <a href="<?php echo buildQueueUrl(['sort' => 'completed_at', 'dir' => ($sortBy === 'completed_at' && $sortDir === 'desc') ? 'asc' : 'desc', 'page' => 1]); ?>" class="queue-ajax-link inline-flex items-center gap-1.5 text-slate-700 hover:text-indigo-600 transition group select-none">
                <span>Completed at</span>
                <?php if ($sortBy === 'completed_at'): ?>
                  <i class="fas fa-sort-<?php echo ($sortDir === 'asc') ? 'up' : 'down'; ?> text-indigo-600"></i>
                <?php else: ?>
                  <i class="fas fa-sort text-slate-300 opacity-60 group-hover:opacity-100"></i>
                <?php endif; ?>
              </a>
            </th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (empty($tasks)): ?>
          <tr>
            <td colspan="8" class="px-4 py-8 text-center text-slate-400">
              No queue records found for this filter.
            </td>
          </tr>
          <?php else: ?>
            <?php foreach ($tasks as $t): ?>
            <tr class="hover:bg-slate-50/60 transition">
              <td class="px-4 py-3 font-mono text-slate-500">#<?php echo (int)$t['id']; ?></td>
              <td class="px-4 py-3">
                <a href="/text/<?php echo (int)$t['text_prop_id']; ?>" target="_blank" class="font-semibold text-slate-900 hover:text-indigo-600 transition flex items-center gap-1.5">
                  <span><?php echo htmlspecialchars($t['title'] ?: ('Text #' . $t['text_prop_id']), ENT_QUOTES, 'UTF-8'); ?></span>
                  <i class="fas fa-external-link-alt text-[10px] text-slate-400"></i>
                </a>
                <span class="text-[11px] text-slate-400">Node ID: <?php echo (int)$t['text_neo_id']; ?> &bull; Text #<?php echo (int)$t['text_prop_id']; ?></span>
              </td>
              <td class="px-4 py-3">
                <?php if ((int)$t['priority'] >= 10): ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                    Manual (High)
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">
                    Automatic (Normal)
                  </span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3">
                <?php
                  $st = $t['status'];
                  $badgeClass = ($st === 'completed') ? 'bg-emerald-100 text-emerald-800' :
                                (($st === 'processing') ? 'bg-indigo-100 text-indigo-800' :
                                (($st === 'failed') ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700'));
                ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold <?php echo $badgeClass; ?>">
                  <?php echo ucfirst($st); ?>
                </span>
                <?php if (!empty($t['error_message'])): ?>
                  <p class="text-[10px] text-rose-600 mt-1 max-w-xs truncate" title="<?php echo htmlspecialchars($t['error_message'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($t['error_message'], ENT_QUOTES, 'UTF-8'); ?>
                  </p>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 font-mono text-slate-600"><?php echo (int)$t['attempts']; ?></td>
              <td class="px-4 py-3 text-slate-500 text-[11px]"><?php echo htmlspecialchars($t['queued_at'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="px-4 py-3 text-slate-500 text-[11px]"><?php echo htmlspecialchars($t['completed_at'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="px-4 py-3 text-right">
                <div class="inline-flex items-center gap-2">
                  <?php if (in_array($t['status'], ['pending', 'failed'], true)): ?>
                    <form method="POST" class="inline">
                      <input type="hidden" name="action" value="process_task_now">
                      <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                      <button type="submit" class="btn-task-action text-force" title="Force process this task now">
                        <i class="fas fa-play text-xs"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                  <form method="POST" onsubmit="return confirm('Delete this queue record?');" class="inline">
                    <input type="hidden" name="action" value="delete_task">
                    <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                    <button type="submit" class="btn-task-action text-delete" title="Delete record">
                      <i class="fas fa-trash-alt text-xs"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
}

// If request is an AJAX table update, output only the table card content and exit.
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    renderQueueTableContent();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LATTE connector queue - <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/stylePublic.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
      .sched-day-label {
        width: 100px !important;
        min-width: 100px !important;
        max-width: 100px !important;
        flex: 0 0 100px !important;
        box-sizing: border-box !important;
        text-align: left !important;
        padding-right: 8px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
      }
      .sched-row-track {
        display: flex !important;
        flex: 1 1 0% !important;
        width: 100% !important;
        min-width: 0 !important;
        align-items: center !important;
      }
      .sched-header-block {
        flex: 8 8 0% !important;
        min-width: 0 !important;
        box-sizing: border-box !important;
      }
      .sched-cell {
        flex: 1 1 0% !important;
        min-width: 0 !important;
        height: 24px !important;
        user-select: none;
        cursor: pointer;
        box-sizing: border-box !important;
        transition: background-color 0.05s ease;
      }
      .sched-cell[data-state="1"] {
        background-color: #10b981;
      }
      .sched-cell[data-state="1"]:hover {
        background-color: #059669;
      }
      .sched-cell[data-state="0"] {
        background-color: #e2e8f0;
      }
      .sched-cell[data-state="0"]:hover {
        background-color: #cbd5e1;
      }
      /* Prominent 2-hour vertical divider line in dark grey */
      .border-2hr {
        border-left: 2px solid #334155 !important;
      }
      /* Subtle 1-hour vertical divider line */
      .border-1hr {
        border-left: 1px solid #cbd5e1 !important;
      }
      /* Grid rightmost border */
      .border-grid-end {
        border-right: 2px solid #334155 !important;
      }
      .sched-cell.current-slot {
        outline: 2px solid #4f46e5;
        outline-offset: -1px;
        z-index: 20;
        position: relative;
      }
      /* Force process button styling */
      .btn-force-process {
        background-color: #d97706 !important;
        color: #ffffff !important;
        border: 1px solid #b45309 !important;
        display: inline-flex !important;
        width: auto !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
        white-space: nowrap !important;
      }
      .btn-force-process:hover {
        background-color: #b45309 !important;
        color: #ffffff !important;
      }
      .btn-force-process span,
      .btn-force-process i {
        color: #ffffff !important;
      }
      .btn-force-badge {
        background-color: rgba(0, 0, 0, 0.25) !important;
        color: #ffffff !important;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
      }
      .btn-task-action {
        color: #94a3b8 !important;
        background: transparent !important;
        border: none !important;
        cursor: pointer !important;
        padding: 4px !important;
        transition: color 0.15s ease !important;
      }
      .btn-task-action.text-force:hover {
        color: #059669 !important;
      }
      .btn-task-action.text-delete:hover {
        color: #e11d48 !important;
      }
      /* Subtle glow aura animation for live daemon polling indicator */
      @keyframes daemonAura {
        0% {
          box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.75);
        }
        50% {
          box-shadow: 0 0 0 7px rgba(16, 185, 129, 0.25);
        }
        100% {
          box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
        }
      }
      .daemon-pulse-aura {
        animation: daemonAura 1.2s cubic-bezier(0.4, 0, 0.2, 1);
      }
    </style>
  </head>
  <body class="bg-slate-100 text-slate-800 w-full antialiased min-h-screen">
  <?php
    $navbar = new Navbar($adminMode); 
    echo $navbar->getNav();
    include_once('admin_tasks.php');
  ?>
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
      <!-- Header section -->
      <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">LATTE connector queue management</h1>
        <p class="mt-1 text-sm text-slate-500">
          Configure 15-minute background processing schedules, monitor automated tasks, and manage priority queues for <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?>.
        </p>
      </div>

      <?php if ($message): ?>
      <div class="mb-6 p-4 rounded-xl border text-sm font-medium flex items-center gap-3 <?php
        echo ($messageType === 'success') ? 'bg-emerald-50 border-emerald-200 text-emerald-800' :
             (($messageType === 'error') ? 'bg-rose-50 border-rose-200 text-rose-800' : 'bg-blue-50 border-blue-200 text-blue-800');
      ?>">
        <i class="fas <?php echo ($messageType === 'success') ? 'fa-check-circle' : (($messageType === 'error') ? 'fa-exclamation-circle' : 'fa-info-circle'); ?> text-base"></i>
        <span><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <?php endif; ?>

      <!-- Metrics grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pending</span>
            <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm"><i class="fas fa-clock"></i></span>
          </div>
          <div id="metricCountPending" class="text-2xl font-bold text-slate-900 mt-2"><?php echo number_format($stats['pending'] ?? 0); ?></div>
          <p class="text-[11px] text-slate-500 mt-1">Awaiting worker turn</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Processing</span>
            <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm"><i class="fas fa-spinner fa-spin"></i></span>
          </div>
          <div id="metricCountProcessing" class="text-2xl font-bold text-slate-900 mt-2"><?php echo number_format($stats['processing'] ?? 0); ?></div>
          <p class="text-[11px] text-slate-500 mt-1">In flight with connector</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Completed</span>
            <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm"><i class="fas fa-check-circle"></i></span>
          </div>
          <div id="metricCountCompleted" class="text-2xl font-bold text-slate-900 mt-2"><?php echo number_format($stats['completed'] ?? 0); ?></div>
          <p class="text-[11px] text-slate-500 mt-1">Suggestions saved to text</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Failed</span>
            <span class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-sm"><i class="fas fa-exclamation-triangle"></i></span>
          </div>
          <div id="metricCountFailed" class="text-2xl font-bold text-slate-900 mt-2"><?php echo number_format($stats['failed'] ?? 0); ?></div>
          <p class="text-[11px] text-slate-500 mt-1">Errors logged</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Current slot</span>
            <span class="w-8 h-8 rounded-lg <?php echo $slotDetails['is_active'] ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500'; ?> flex items-center justify-center text-sm">
              <i class="fas <?php echo $slotDetails['is_active'] ? 'fa-play' : 'fa-pause'; ?>"></i>
            </span>
          </div>
          <div class="text-xs font-bold text-slate-900 mt-2 truncate" title="<?php echo htmlspecialchars($slotDetails['time_range'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($slotDetails['time_range'], ENT_QUOTES, 'UTF-8'); ?>
          </div>
          <p class="text-[11px] <?php echo $slotDetails['is_active'] ? 'text-emerald-600 font-semibold' : 'text-slate-500'; ?> mt-1">
            <?php echo $slotDetails['is_active'] ? 'Background processing active' : 'No background processing'; ?>
          </p>
        </div>
      </div>

      <!-- Weekly 15-minute Schedule Grid Card -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs mb-8">
        <form method="POST" id="scheduleForm" class="space-y-5">
          <input type="hidden" name="action" value="save_settings">
          <input type="hidden" name="weekly_schedule" id="weekly_schedule_input" value="<?php echo htmlspecialchars($weeklySchedule, ENT_QUOTES, 'UTF-8'); ?>">

          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
              <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-calendar-week text-indigo-500"></i>
                <span>Weekly background processing schedule (15-minute blocks)</span>
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">
                Click and drag to select intervals where the server is allowed to run background processing.
                <span class="inline-flex items-center gap-1.5 ml-2">
                  <span class="w-3 h-3 rounded-xs bg-emerald-500 border border-emerald-600 inline-block"></span>
                  <span class="text-slate-700 font-medium">Processing allowed (green)</span>
                </span>
                <span class="inline-flex items-center gap-1.5 ml-2">
                  <span class="w-3 h-3 rounded-xs bg-slate-200 border border-slate-300 inline-block"></span>
                  <span class="text-slate-700 font-medium">No processing (grey)</span>
                </span>
                <span class="inline-flex items-center gap-1 ml-2 text-slate-700 font-medium">
                  <i class="far fa-clock text-slate-400"></i>
                  <span>Timezone: <?php echo htmlspecialchars(date_default_timezone_get(), ENT_QUOTES, 'UTF-8'); ?></span>
                </span>
              </p>
            </div>

            <!-- Schedule summary badge -->
            <div class="flex items-center gap-2">
              <span id="activeBlocksSummaryBadge" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                <?php echo $slotDetails['active_count']; ?> / 672 blocks (<?php echo $slotDetails['percentage']; ?>%)
              </span>
            </div>
          </div>

          <!-- Quick presets toolbar -->
          <div class="flex flex-wrap items-center gap-2 pt-1 pb-2">
            <span class="text-xs font-semibold text-slate-500 mr-1 flex items-center gap-1">
              <i class="fas fa-magic text-slate-400"></i>
              <span>Presets:</span>
            </span>
            <button type="button" onclick="applyPreset('all')" class="px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
              Select all (24/7)
            </button>
            <button type="button" onclick="applyPreset('clear')" class="px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
              Clear all
            </button>
            <button type="button" onclick="applyPreset('overnight')" class="px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
              Overnight (00:00 - 06:00)
            </button>
            <button type="button" onclick="applyPreset('offhours')" class="px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
              Off-hours (evenings & weekends)
            </button>
            <button type="button" onclick="applyPreset('working')" class="px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
              Working hours (Mon-Fri 09:00 - 18:00)
            </button>
            <button type="button" onclick="applyPreset('weekends')" class="px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
              Weekends only
            </button>
            <button type="button" onclick="applyPreset('invert')" class="px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
              Invert selection
            </button>
          </div>

          <!-- Responsive interactive schedule square (fits available width, no horizontal sliding) -->
          <div class="w-full select-none rounded-xl border border-slate-200/90 bg-white p-3 sm:p-4 shadow-2xs">
            <!-- Hour header row: 00:00 until 23:45 with dark grey lines per 2 hours -->
            <div class="flex items-center pb-2 mb-1.5 w-full">
              <!-- Day label corner spacer -->
              <div class="sched-day-label text-[11px] font-bold text-slate-400 uppercase tracking-wider pl-1">
                Day
              </div>
              <!-- 96-column header flex track matching day rows exactly -->
              <div class="sched-row-track">
                <?php for ($h = 0; $h < 24; $h += 2): ?>
                  <div class="sched-header-block border-l-2 border-slate-700 <?= ($h === 22) ? 'border-r-2' : '' ?> pl-1 pr-0.5 py-0.5 flex items-center justify-between text-slate-700 select-none bg-slate-50/70">
                    <button type="button" onclick="toggle2Hour(<?= $h ?>)" title="Click to toggle 2-hour window <?= sprintf('%02d:00 - %02d:00', $h, $h + 2) ?> across all days" class="text-[10px] sm:text-[11px] font-mono font-bold hover:text-indigo-600 transition truncate">
                      <?= sprintf('%02d:00', $h) ?>
                    </button>
                    <span class="text-[9px] font-mono text-slate-400 hidden md:inline"><?= sprintf('%02d', $h + 1) ?></span>
                  </div>
                <?php endfor; ?>
              </div>
            </div>

            <!-- 7 Day Rows (Y-axis) -->
            <div id="scheduleGridBody" class="space-y-1 w-full">
              <?php for ($d = 0; $d < 7; $d++): ?>
              <div class="flex items-center w-full">
                <!-- Day label button (toggles whole day) -->
                <button type="button" onclick="toggleDay(<?= $d ?>)" title="Click to toggle all 96 blocks for <?= $dayNames[$d] ?>" class="sched-day-label text-xs font-semibold text-slate-700 hover:text-indigo-600 transition">
                  <?= $dayNames[$d] ?>
                </button>

                <!-- 96 15-minute blocks for this day matching 100% width -->
                <div class="sched-row-track">
                  <?php for ($s = 0; $s < 96; $s++):
                    $gridIndex = ($d * 96) + $s;
                    $isActive = isset($weeklySchedule[$gridIndex]) && $weeklySchedule[$gridIndex] === '1';
                    $h = (int)floor($s / 4);
                    $m = ($s % 4) * 15;
                    $endM = $m + 15;
                    $endH = ($endM === 60) ? ($h + 1) % 24 : $h;
                    $endMFormatted = ($endM === 60) ? 0 : $endM;
                    $timeLabel = sprintf('%s %02d:%02d - %02d:%02d', $dayNames[$d], $h, $m, $endH, $endMFormatted);
                    $isCurrent = ($gridIndex === $slotDetails['grid_index']);

                    $borderClass = '';
                    if ($s % 8 === 0) {
                      $borderClass = 'border-2hr';
                    } elseif ($s % 4 === 0) {
                      $borderClass = 'border-1hr';
                    }
                    if ($s === 95) {
                      $borderClass .= ' border-grid-end';
                    }
                  ?>
                  <div class="sched-cell cursor-pointer <?= $borderClass ?> <?= $isCurrent ? 'current-slot' : '' ?>"
                       data-index="<?= $gridIndex ?>"
                       data-day="<?= $d ?>"
                       data-slot="<?= $s ?>"
                       data-hour="<?= $h ?>"
                       data-state="<?= $isActive ? '1' : '0' ?>"
                       title="<?= htmlspecialchars($timeLabel, ENT_QUOTES, 'UTF-8') ?> (<?= $isActive ? 'Processing allowed' : 'No processing' ?>)">
                  </div>
                  <?php endfor; ?>
                </div>
              </div>
              <?php endfor; ?>
            </div>
          </div>

          <!-- Queue settings controls -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-slate-100">
            <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200/80">
              <input type="checkbox" name="queue_enabled" id="queue_enabled" value="1" <?php echo $settings['queue_enabled'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
              <label for="queue_enabled" class="text-xs font-semibold text-slate-800 cursor-pointer">
                Enable background automatic queue
              </label>
            </div>

            <div>
              <label for="max_retries" class="block text-xs font-semibold text-slate-700 mb-1">Max retry attempts</label>
              <input type="number" min="1" max="10" name="max_retries" id="max_retries" value="<?php echo htmlspecialchars($settings['max_retries'], ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs">
              <p class="text-[11px] text-slate-400 mt-1">Retry count before task is marked failed.</p>
            </div>
          </div>

          <div class="flex items-center justify-between pt-2">
            <div class="text-xs text-slate-500">
              <i class="fas fa-info-circle text-indigo-500 mr-1"></i>
              Changes take effect immediately on next worker check.
            </div>
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-2xs">
              <i class="fas fa-save text-[11px]"></i>
              <span>Save schedule settings</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Quick actions and automatic trigger setup grid -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8 items-start">
        <!-- Automatic Trigger Setup (Cron / Daemon) -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
          <div class="pb-3 border-b border-slate-100">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <i class="fas fa-clock text-indigo-500"></i>
              <span>Automatic trigger configuration</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
              The worker evaluates the 15-minute weekly schedule above. Manual interface triggers always take precedence and run immediately.
            </p>
          </div>

          <div class="space-y-3">
            <div id="daemonControlCard" class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div class="flex items-center gap-2.5">
                <span id="daemonStatusDot" class="w-2.5 h-2.5 rounded-full <?php echo $daemonInfo['running'] ? 'bg-emerald-500 daemon-pulse-aura' : 'bg-slate-400'; ?> transition-all duration-300"></span>
                <div>
                  <span id="daemonStatusTitle" class="text-xs font-bold <?php echo $daemonInfo['running'] ? 'text-emerald-800' : 'text-slate-700'; ?>">
                    <?php echo $daemonInfo['running'] ? 'Background daemon is active' : 'Background daemon is inactive'; ?>
                  </span>
                  <span id="daemonStatusSubtitle" class="text-[11px] text-slate-500 block">
                    <?php if ($daemonInfo['running']): ?>
                      Process ID: <?php echo (int)$daemonInfo['pid']; ?> 
                    <?php else: ?>
                      Start daemon to process scheduled blocks automatically
                    <?php endif; ?>
                  </span>
                </div>
              </div>
              <div id="daemonActionContainer">
                <button type="button" id="btnToggleDaemon"
                  data-action="<?php echo $daemonInfo['running'] ? 'stop_daemon' : 'start_daemon'; ?>"
                  class="px-3 py-1.5 rounded-lg text-xs font-medium <?php echo $daemonInfo['running'] ? 'bg-white hover:bg-rose-50 text-rose-700 border border-slate-200' : 'bg-emerald-600 hover:bg-emerald-700 text-white'; ?> shadow-2xs transition inline-flex items-center gap-1.5">
                  <span><?php echo $daemonInfo['running'] ? 'Stop daemon' : 'Start daemon'; ?></span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Operational triggers -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
          <div>
            <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
              <div>
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                  <i class="fas fa-sliders-h text-indigo-500"></i>
                  <span>Manual operational controls</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                  On-demand triggers and queue maintenance.
                </p>
              </div>
            </div>

            <!-- Primary actions -->
            <div class="py-3">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Processing triggers</span>
              <div class="flex flex-wrap items-center gap-2">
                <!-- Force process immediately, ignoring schedule -->
                <form method="POST" class="inline m-0">
                  <input type="hidden" name="action" value="force_process">
                  <button type="submit" class="btn-force-process px-3 py-1.5 rounded-lg text-xs font-semibold shadow-xs transition" title="Process pending tasks now in background, bypassing schedule">
                    <span class="flex items-center gap-1.5">
                      <i class="fas fa-bolt text-[11px]"></i>
                      <span>Force run now</span>
                    </span>
                  </button>
                </form>

                <!-- Run automated trigger cycle respecting schedule -->
                <form method="POST" class="inline m-0">
                  <input type="hidden" name="action" value="run_cron_cycle">
                  <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-medium transition" title="Run scheduled processing in background (respects schedule window)">
                    <i class="fas fa-sync-alt text-[11px] text-emerald-600"></i>
                    <span>Run scheduled cycle</span>
                  </button>
                </form>

                <!-- Scan unparsed texts -->
                <form method="POST" class="inline m-0">
                  <input type="hidden" name="action" value="scan">
                  <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 text-xs font-medium transition" title="Scan graph database for unparsed texts and enqueue them">
                    <i class="fas fa-search text-[11px] text-slate-500"></i>
                    <span>Scan texts</span>
                  </button>
                </form>
              </div>
            </div>
          </div>

          <!-- Secondary maintenance actions -->
          <div class="pt-3 border-t border-slate-100">
            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Queue maintenance</span>
            <div class="flex flex-wrap items-center gap-2">
              <form method="POST" class="inline m-0">
                <input type="hidden" name="action" value="retry_failed">
                <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-50 text-slate-600 border border-slate-200 text-xs font-medium transition" title="Reset failed tasks to pending status">
                  <i class="fas fa-redo text-[10px] text-slate-400"></i>
                  <span>Retry failed</span>
                </button>
              </form>

              <form method="POST" class="inline m-0">
                <input type="hidden" name="action" value="clear_completed">
                <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-white hover:bg-rose-50 text-slate-600 hover:text-rose-700 border border-slate-200 text-xs font-medium transition" title="Delete completed tasks from history">
                  <i class="fas fa-trash-alt text-[10px] text-slate-400"></i>
                  <span>Clear history</span>
                </button>
              </form>

              <button type="button" onclick="refreshQueueTable();" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-50 text-slate-600 border border-slate-200 text-xs font-medium transition" title="Reload queue table">
                <i class="fas fa-sync text-[10px] text-slate-400"></i>
                <span>Refresh table</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Queue task table card -->
      <div id="queueTableContainer" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden transition-opacity duration-150">
        <?php renderQueueTableContent(); ?>
      </div>
    </main>

    <!-- Client-side click and drag schedule editor script -->
    <script>
      (function() {
        const input = document.getElementById('weekly_schedule_input');
        const badge = document.getElementById('activeBlocksSummaryBadge');
        const cells = Array.from(document.querySelectorAll('.sched-cell'));

        let isDragging = false;
        let dragMode = '1'; // '1' = set to green, '0' = set to grey

        function updateScheduleString() {
          const arr = new Array(672);
          let activeCount = 0;
          for (let i = 0; i < cells.length; i++) {
            const st = cells[i].getAttribute('data-state') === '1' ? '1' : '0';
            arr[i] = st;
            if (st === '1') activeCount++;
          }
          const str = arr.join('');
          input.value = str;

          if (badge) {
            const pct = ((activeCount / 672) * 100).toFixed(1);
            badge.textContent = `${activeCount} / 672 blocks (${pct}%)`;
          }
        }

        function setCellState(cell, state) {
          cell.setAttribute('data-state', state);
          const title = cell.getAttribute('title') || '';
          const baseTitle = title.split(' (')[0];
          cell.setAttribute('title', baseTitle + (state === '1' ? ' (Processing allowed)' : ' (No processing)'));
        }

        cells.forEach(cell => {
          cell.addEventListener('mousedown', function(e) {
            e.preventDefault();
            isDragging = true;
            const current = this.getAttribute('data-state') === '1' ? '1' : '0';
            dragMode = (current === '1') ? '0' : '1';
            setCellState(this, dragMode);
            updateScheduleString();
          });

          cell.addEventListener('mouseenter', function() {
            if (isDragging) {
              setCellState(this, dragMode);
              updateScheduleString();
            }
          });
        });

        window.addEventListener('mouseup', function() {
          if (isDragging) {
            isDragging = false;
            updateScheduleString();
          }
        });

        window.toggleDay = function(dayIndex) {
          const dayCells = cells.filter(c => parseInt(c.getAttribute('data-day'), 10) === dayIndex);
          const allActive = dayCells.every(c => c.getAttribute('data-state') === '1');
          const target = allActive ? '0' : '1';
          dayCells.forEach(c => setCellState(c, target));
          updateScheduleString();
        };

        window.toggle2Hour = function(startHour) {
          const targetCells = cells.filter(c => {
            const h = parseInt(c.getAttribute('data-hour'), 10);
            return h === startHour || h === (startHour + 1);
          });
          const allActive = targetCells.every(c => c.getAttribute('data-state') === '1');
          const target = allActive ? '0' : '1';
          targetCells.forEach(c => setCellState(c, target));
          updateScheduleString();
        };

        window.toggleHour = function(hour) {
          const hourCells = cells.filter(c => parseInt(c.getAttribute('data-hour'), 10) === hour);
          const allActive = hourCells.every(c => c.getAttribute('data-state') === '1');
          const target = allActive ? '0' : '1';
          hourCells.forEach(c => setCellState(c, target));
          updateScheduleString();
        };

        window.applyPreset = function(preset) {
          for (let i = 0; i < cells.length; i++) {
            const c = cells[i];
            const day = parseInt(c.getAttribute('data-day'), 10); // 0=Mon, 6=Sun
            const slot = parseInt(c.getAttribute('data-slot'), 10); // 0..95
            const hour = parseInt(c.getAttribute('data-hour'), 10); // 0..23

            let target = '0';
            if (preset === 'all') {
              target = '1';
            } else if (preset === 'clear') {
              target = '0';
            } else if (preset === 'overnight') {
              // 00:00 to 06:00 (slots 0..23) every day
              target = (slot < 24) ? '1' : '0';
            } else if (preset === 'offhours') {
              // Mon-Fri: 20:00 to 08:00 (slots 0..31 and slots 80..95), weekends all day
              if (day >= 5) {
                target = '1';
              } else {
                target = (slot < 32 || slot >= 80) ? '1' : '0';
              }
            } else if (preset === 'working') {
              // Mon-Fri 09:00 - 18:00 (slots 36..71)
              target = (day < 5 && slot >= 36 && slot < 72) ? '1' : '0';
            } else if (preset === 'weekends') {
              target = (day >= 5) ? '1' : '0';
            } else if (preset === 'invert') {
              target = (c.getAttribute('data-state') === '1') ? '0' : '1';
            }
            setCellState(c, target);
          }
          updateScheduleString();
        };
      })();

      // Ajax queue table controller
      (function() {
        let isQueueLoading = false;
        let autoRefreshTimer = null;

        function updateMetricCounters() {
          const metricsEl = document.getElementById('queueMetricsData');
          if (!metricsEl) return;
          const fields = [
            { id: 'metricCountPending', key: 'pending' },
            { id: 'metricCountProcessing', key: 'processing' },
            { id: 'metricCountCompleted', key: 'completed' },
            { id: 'metricCountFailed', key: 'failed' }
          ];
          fields.forEach(f => {
            const el = document.getElementById(f.id);
            if (el && metricsEl.dataset[f.key] !== undefined) {
              el.textContent = Number(metricsEl.dataset[f.key]).toLocaleString();
            }
          });
        }

        function checkAutoRefresh() {
          if (autoRefreshTimer) {
            clearTimeout(autoRefreshTimer);
            autoRefreshTimer = null;
          }
          const metricsEl = document.getElementById('queueMetricsData');
          const processingCount = metricsEl
            ? parseInt(metricsEl.dataset.processing || '0', 10)
            : <?php echo (int)($stats['processing'] ?? 0); ?>;
          if (processingCount > 0) {
            autoRefreshTimer = setTimeout(function() {
              loadQueueTable(window.location.href, false);
            }, 3000);
          }
        }

        window.loadQueueTable = function(urlStr, pushHistory = true) {
          if (isQueueLoading) return;
          isQueueLoading = true;

          const container = document.getElementById('queueTableContainer');
          const spinner = document.getElementById('queueTableSpinner');
          if (spinner) spinner.classList.remove('hidden');
          if (container) container.classList.add('opacity-50', 'pointer-events-none');

          const fetchUrl = new URL(urlStr, window.location.origin);
          fetchUrl.searchParams.set('ajax', '1');

          fetch(fetchUrl.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          })
          .then(res => {
            if (!res.ok) throw new Error('Network error loading queue');
            return res.text();
          })
          .then(html => {
            if (container) {
              container.innerHTML = html;
            }
            if (pushHistory) {
              const cleanUrl = new URL(urlStr, window.location.origin);
              cleanUrl.searchParams.delete('ajax');
              window.history.pushState({ url: cleanUrl.toString() }, '', cleanUrl.toString());
            }
            updateMetricCounters();
            checkAutoRefresh();
          })
          .catch(err => {
            console.error('Ajax queue load error:', err);
            window.location.href = urlStr;
          })
          .finally(() => {
            isQueueLoading = false;
            if (container) container.classList.remove('opacity-50', 'pointer-events-none');
            const newSpinner = document.getElementById('queueTableSpinner');
            if (newSpinner) newSpinner.classList.add('hidden');
          });
        };

        window.refreshQueueTable = function() {
          loadQueueTable(window.location.href, false);
        };

        // Delegated click listener for ajax pagination, filtering, and sorting links
        document.addEventListener('click', function(e) {
          const link = e.target.closest('a.queue-ajax-link');
          if (!link) return;
          e.preventDefault();
          loadQueueTable(link.href, true);
        });

        // Browser back and forward navigation support
        window.addEventListener('popstate', function() {
          loadQueueTable(window.location.href, false);
        });

        // Intercept action forms inside the queue table container
        document.addEventListener('submit', function(e) {
          if (e.defaultPrevented) return;
          const form = e.target.closest('#queueTableContainer form');
          if (!form) return;
          e.preventDefault();

          const container = document.getElementById('queueTableContainer');
          if (container) container.classList.add('opacity-50', 'pointer-events-none');

          const postUrl = new URL(form.action || window.location.href, window.location.origin);
          postUrl.searchParams.set('ajax', '1');

          fetch(postUrl.toString(), {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          })
          .then(res => {
            if (!res.ok) throw new Error('Action failed');
            return res.text();
          })
          .then(html => {
            if (container) {
              container.innerHTML = html;
            }
            updateMetricCounters();
            checkAutoRefresh();
          })
          .catch(err => {
            console.error('Form submission error:', err);
            form.submit();
          })
          .finally(() => {
            if (container) container.classList.remove('opacity-50', 'pointer-events-none');
          });
        });

        // Initial check for active auto-refresh
        checkAutoRefresh();
      })();

      // Daemon status controller and live polling
      (function() {
        function triggerDotAura() {
          const currentDot = document.getElementById('daemonStatusDot');
          if (currentDot && currentDot.classList.contains('bg-emerald-500')) {
            currentDot.classList.remove('daemon-pulse-aura');
            void currentDot.offsetWidth;
            currentDot.classList.add('daemon-pulse-aura');
          }
        }

        function updateDaemonUI(data) {
          const currentDot = document.getElementById('daemonStatusDot');
          const currentTitle = document.getElementById('daemonStatusTitle');
          const currentSubtitle = document.getElementById('daemonStatusSubtitle');
          const currentContainer = document.getElementById('daemonActionContainer');

          if (!currentDot || !currentTitle || !currentSubtitle || !currentContainer) return;

          if (data.running) {
            currentDot.className = 'w-2.5 h-2.5 rounded-full bg-emerald-500 transition-all duration-300';
            currentTitle.textContent = 'Background daemon is active';
            currentTitle.className = 'text-xs font-bold text-emerald-800';
            currentSubtitle.innerHTML = 'Process ID: ' + (data.pid || '') + ' &bull; Automatically checks queue every 20 seconds';
            currentContainer.innerHTML = '<button type="button" id="btnToggleDaemon" data-action="stop_daemon" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-white hover:bg-rose-50 text-rose-700 border border-slate-200 shadow-2xs transition inline-flex items-center gap-1.5"><span>Stop daemon</span></button>';
          } else {
            currentDot.className = 'w-2.5 h-2.5 rounded-full bg-slate-400 transition-all duration-300';
            currentTitle.textContent = 'Background daemon is inactive';
            currentTitle.className = 'text-xs font-bold text-slate-700';
            currentSubtitle.textContent = 'Start daemon to process scheduled blocks automatically';
            currentContainer.innerHTML = '<button type="button" id="btnToggleDaemon" data-action="start_daemon" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs transition inline-flex items-center gap-1.5"><span>Start daemon</span></button>';
          }
        }

        window.pollDaemonStatus = function(animateAura = true) {
          fetch('connector_queue.php?ajax_daemon=1', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          })
          .then(res => {
            if (!res.ok) throw new Error('Status poll failed');
            return res.json();
          })
          .then(data => {
            updateDaemonUI(data);
            if (data.running && animateAura) {
              triggerDotAura();
            }
          })
          .catch(err => {
            console.error('Daemon status poll error:', err);
          });
        };

        // Delegated click handler for daemon start and stop button
        document.addEventListener('click', function(e) {
          const btn = e.target.closest('#btnToggleDaemon');
          if (!btn) return;
          e.preventDefault();

          const action = btn.getAttribute('data-action');
          if (!action) return;

          const isStarting = (action === 'start_daemon');
          btn.disabled = true;
          btn.classList.add('opacity-60', 'cursor-not-allowed', 'pointer-events-none');
          btn.innerHTML = `<i class="fas fa-spinner fa-spin text-xs mr-1"></i> <span>${isStarting ? 'Starting daemon...' : 'Stopping daemon...'}</span>`;

          fetch('connector_queue.php?ajax_daemon=1', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'action=' + encodeURIComponent(action)
          })
          .then(res => {
            if (!res.ok) throw new Error('Daemon action request failed');
            return res.json();
          })
          .then(data => {
            updateDaemonUI(data);
            if (data.running) {
              triggerDotAura();
            }
          })
          .catch(err => {
            console.error('Daemon toggle failed:', err);
            pollDaemonStatus(false);
          });
        });

        // Poll daemon status every 20 seconds
        setInterval(function() {
          pollDaemonStatus(true);
        }, 20000);
      })();
    </script>
  </body>
</html>
