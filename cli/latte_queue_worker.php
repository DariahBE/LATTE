<?php
/**
 * Latte background queue worker.
 * Processes automated and manual entity extraction tasks with priority ordering and 15-minute week schedule constraints.
 *
 * Usage:
 *   php cli/latte_queue_worker.php --cron
 *   php cli/latte_queue_worker.php --once
 *   php cli/latte_queue_worker.php --daemon
 *   php cli/latte_queue_worker.php --scan
 *   php cli/latte_queue_worker.php --force
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Access denied. This script can only be executed from the command line.\n");
}

define('ROOT_DIR', dirname(__DIR__));

include_once(ROOT_DIR . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/client.inc.php');
include_once(ROOT_DIR . '/includes/latte_queue_manager.inc.php');

// Parse CLI options.
$options = getopt('', ['cron', 'once', 'daemon', 'scan', 'force', 'help', 'task-id:']);

if (isset($options['help'])) {
    echo "Latte queue worker\n\n";
    echo "Options:\n";
    echo "  --cron         Run in cron mode (scans unparsed texts, respects 15-minute schedule, processes pending tasks).\n";
    echo "  --once         Process current pending tasks and exit.\n";
    echo "  --daemon       Run continuously as a background daemon.\n";
    echo "  --scan         Scan for unparsed texts in the graph database before processing.\n";
    echo "  --force        Force processing even outside permitted time window.\n";
    echo "  --task-id <id> Process a specific queue task by ID and exit.\n";
    echo "  --help         Show this help message.\n";
    exit(0);
}

$isCron = isset($options['cron']);
$isDaemon = isset($options['daemon']);
$forceMode = isset($options['force']);
$specificTaskId = (int)($options['task-id'] ?? 0);
$settings = LatteQueueManager::getSettings();

echo "[" . date('Y-m-d H:i:s') . "] Latte queue worker started.\n";

// Register process ID and shutdown handler for daemon mode.
if ($isDaemon) {
    $pidFile = ROOT_DIR . '/storage/latte_daemon.pid';
    @file_put_contents($pidFile, (string)getmypid());
    register_shutdown_function(function() use ($pidFile) {
        if (file_exists($pidFile) && (int)trim(@file_get_contents($pidFile)) === getmypid()) {
            @unlink($pidFile);
        }
    });
}

// If a specific task ID was given, process only that task and exit immediately.
if ($specificTaskId > 0) {
    $singleTask = LatteQueueManager::getTaskById($specificTaskId);
    if (!$singleTask) {
        echo "[" . date('Y-m-d H:i:s') . "] Task #{$specificTaskId} not found.\n";
        exit(1);
    }
    echo "[" . date('Y-m-d H:i:s') . "] Processing specific task #{$specificTaskId} (text #{$singleTask['text_prop_id']})...\n";
    try {
        $result = LatteQueueManager::processTask($singleTask, $client);
        $count = $result['count'] ?? 0;
        echo "[" . date('Y-m-d H:i:s') . "] Task #{$specificTaskId} completed successfully ({$count} suggestions extracted).\n";
        exit(0);
    } catch (\Throwable $e) {
        echo "[" . date('Y-m-d H:i:s') . "] Task #{$specificTaskId} failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// In cron mode or when explicitly requested, scan graph database for unparsed texts.
if ($isCron || isset($options['scan'])) {
    echo "[" . date('Y-m-d H:i:s') . "] Scanning graph database for unparsed texts...\n";
    $enqueued = LatteQueueManager::scanAndEnqueueUnparsedTexts($client);
    echo "[" . date('Y-m-d H:i:s') . "] Scan completed. {$enqueued} new texts enqueued.\n";
}

$iteration = 0;
$processedCount = 0;
$lastScanTimestamp = time();

while (true) {
    $iteration++;

    // Check for stop signal file in daemon mode.
    if ($isDaemon) {
        $stopFile = ROOT_DIR . '/storage/latte_daemon.stop';
        if (file_exists($stopFile)) {
            @unlink($stopFile);
            echo "[" . date('Y-m-d H:i:s') . "] Stop signal received. Shutting down daemon gracefully.\n";
            break;
        }

        // Periodic check for new unparsed texts in the graph every 15 minutes.
        if (time() - $lastScanTimestamp >= 900) {
            $lastScanTimestamp = time();
            try {
                LatteQueueManager::scanAndEnqueueUnparsedTexts($client);
            } catch (\Throwable $e) {
                echo "[" . date('Y-m-d H:i:s') . "] Periodic scan error: " . $e->getMessage() . "\n";
            }
        }
    }

    try {
        $task = LatteQueueManager::fetchNextPendingTask($forceMode);
    } catch (\Throwable $e) {
        echo "[" . date('Y-m-d H:i:s') . "] Error fetching pending task: " . $e->getMessage() . "\n";
        $task = null;
    }

    if ($task) {
        $taskId = (int)$task['id'];
        $neoId = (int)$task['text_neo_id'];
        $propId = (int)$task['text_prop_id'];
        $prio = (int)$task['priority'];
        $typeLabel = ($prio >= 10) ? 'Manual' : 'Automatic';

        echo "[" . date('Y-m-d H:i:s') . "] [{$typeLabel}] Processing task #{$taskId} (text node {$neoId}, text ID {$propId})...\n";

        try {
            $result = LatteQueueManager::processTask($task, $client);
            $count = $result['count'] ?? 0;
            $lang = $result['language'] ?? 'unknown';
            echo "[" . date('Y-m-d H:i:s') . "] Task #{$taskId} completed. {$count} suggestions saved (language: {$lang}).\n";
            $processedCount++;
        } catch (\Throwable $e) {
            echo "[" . date('Y-m-d H:i:s') . "] Task #{$taskId} failed: " . $e->getMessage() . "\n";
        }

        // Slight breathing pause between tasks.
        usleep(150000); // 150ms
        continue;
    }

    // No pending task available.
    if (!$isDaemon) {
        $slotInfo = LatteQueueManager::getCurrentSlotDetails();
        if (!LatteQueueManager::isQueueEnabled()) {
            echo "[" . date('Y-m-d H:i:s') . "] Queue is currently disabled in settings.\n";
        } elseif (!$slotInfo['is_active']) {
            echo "[" . date('Y-m-d H:i:s') . "] Current 15-minute slot ({$slotInfo['time_range']}) is not scheduled for background processing.\n";
        } else {
            echo "[" . date('Y-m-d H:i:s') . "] Queue is empty. No pending tasks.\n";
        }
        break;
    }

    // In daemon mode, sleep up to 20 seconds before checking again (checking stop file each second).
    for ($s = 0; $s < 20; $s++) {
        $stopFile = ROOT_DIR . '/storage/latte_daemon.stop';
        if (file_exists($stopFile)) {
            @unlink($stopFile);
            echo "[" . date('Y-m-d H:i:s') . "] Stop signal received. Shutting down daemon gracefully.\n";
            break 2;
        }
        sleep(1);
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Latte queue worker finished. Total processed tasks in this run: {$processedCount}.\n";
