<?php
/**
 * Latte queue manager.
 * Manages automated and manual entity extraction queues, time window constraints,
 * and persistent storage of suggestions on text nodes.
 */

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}

include_once(ROOT_DIR . '/includes/config_model_manager.inc.php');

class LatteQueueManager {
    protected static $pdoInstance = null;

    /**
     * Get SQLite PDO connection for the queue.
     */
    public static function getPdo(): PDO {
        if (self::$pdoInstance === null) {
            $pdo = ConfigModelManager::getPdo();
            self::initSchema($pdo);
            self::$pdoInstance = $pdo;
        }
        return self::$pdoInstance;
    }

    /**
     * Initialize queue and settings database tables if they do not exist.
     */
    public static function initSchema(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS latte_queue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                text_neo_id INTEGER NOT NULL,
                text_prop_id INTEGER NOT NULL,
                title TEXT,
                priority INTEGER NOT NULL DEFAULT 0,
                status TEXT NOT NULL DEFAULT 'pending',
                attempts INTEGER NOT NULL DEFAULT 0,
                error_message TEXT DEFAULT NULL,
                queued_at TEXT NOT NULL,
                started_at TEXT DEFAULT NULL,
                completed_at TEXT DEFAULT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_latte_queue_status_prio ON latte_queue (status, priority DESC, queued_at ASC);
            CREATE INDEX IF NOT EXISTS idx_latte_queue_text_neo ON latte_queue (text_neo_id);

            CREATE TABLE IF NOT EXISTS latte_queue_settings (
                key TEXT PRIMARY KEY,
                value TEXT
            );
        ");

        $defaultSettings = [
            'queue_enabled' => '1',
            'max_retries' => '3',
            'last_scan_at' => '',
            'weekly_schedule' => self::getDefaultWeeklySchedule()
        ];

        // Clean up deprecated batch_size setting if present.
        $pdo->exec("DELETE FROM latte_queue_settings WHERE key = 'batch_size'");

        $checkStmt = $pdo->prepare("SELECT value FROM latte_queue_settings WHERE key = :key");
        $insertStmt = $pdo->prepare("INSERT INTO latte_queue_settings (key, value) VALUES (:key, :value)");

        foreach ($defaultSettings as $k => $v) {
            $checkStmt->execute([':key' => $k]);
            if ($checkStmt->fetch() === false) {
                $insertStmt->execute([':key' => $k, ':value' => $v]);
            }
        }
    }

    /**
     * Get default weekly schedule (672 characters: 7 days x 96 15-minute blocks).
     * Defaults to all blocks inactive (0) so no tasks run automatically until configured.
     */
    public static function getDefaultWeeklySchedule(): string {
        return str_repeat('0', 672);
    }

    /**
     * Get the active weekly schedule string.
     */
    public static function getWeeklySchedule(): string {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("SELECT value FROM latte_queue_settings WHERE key = 'weekly_schedule'");
        $stmt->execute();
        $val = $stmt->fetchColumn();
        if ($val && strlen($val) === 672 && preg_match('/^[01]{672}$/', $val)) {
            return $val;
        }
        $default = self::getDefaultWeeklySchedule();
        self::updateSettings(['weekly_schedule' => $default]);
        return $default;
    }

    /**
     * Get all queue settings as an associative array.
     */
    public static function getSettings(): array {
        $pdo = self::getPdo();
        $stmt = $pdo->query("SELECT key, value FROM latte_queue_settings");
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        return [
            'queue_enabled' => ($rows['queue_enabled'] ?? '1') === '1',
            'max_retries' => (int)($rows['max_retries'] ?? 3),
            'last_scan_at' => $rows['last_scan_at'] ?? '',
            'weekly_schedule' => $rows['weekly_schedule'] ?? self::getWeeklySchedule()
        ];
    }

    /**
     * Update queue settings.
     */
    public static function updateSettings(array $settings): bool {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("INSERT INTO latte_queue_settings (key, value) VALUES (:key, :value) ON CONFLICT(key) DO UPDATE SET value = :value");
        foreach ($settings as $k => $v) {
            if ($k === 'queue_enabled') {
                $v = $v ? '1' : '0';
            }
            $stmt->execute([':key' => (string)$k, ':value' => (string)$v]);
        }
        return true;
    }

    /**
     * Check whether queue processing is currently enabled.
     */
    public static function isQueueEnabled(): bool {
        $settings = self::getSettings();
        return (bool)$settings['queue_enabled'];
    }

    /**
     * Check whether the given time (or current moment) falls inside an allowed 15-minute background processing block.
     */
    public static function isWithinQueueWindow(?\DateTimeInterface $dateTime = null): bool {
        $now = $dateTime ?: new \DateTime();
        $day = (int)$now->format('N') - 1; // 0 = Monday, 6 = Sunday
        $hour = (int)$now->format('G');     // 0 to 23
        $minute = (int)$now->format('i');   // 0 to 59
        $slotInHour = (int)floor($minute / 15);
        $slotInDay = ($hour * 4) + $slotInHour; // 0 to 95
        $index = ($day * 96) + $slotInDay;      // 0 to 671

        $schedule = self::getWeeklySchedule();
        return isset($schedule[$index]) && $schedule[$index] === '1';
    }

    /**
     * Get details for the current 15-minute slot and schedule statistics.
     */
    public static function getCurrentSlotDetails(?\DateTimeInterface $dateTime = null): array {
        $now = $dateTime ?: new \DateTime();
        $day = (int)$now->format('N') - 1;
        $hour = (int)$now->format('G');
        $minute = (int)$now->format('i');
        $slotInHour = (int)floor($minute / 15);
        $slotInDay = ($hour * 4) + $slotInHour;
        $index = ($day * 96) + $slotInDay;

        $schedule = self::getWeeklySchedule();
        $isActive = isset($schedule[$index]) && $schedule[$index] === '1';
        $activeCount = substr_count($schedule, '1');

        $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $startMin = $slotInHour * 15;
        $endMin = $startMin + 15;
        $endHour = ($endMin === 60) ? ($hour + 1) % 24 : $hour;
        $endMinFormatted = ($endMin === 60) ? 0 : $endMin;
        $timeRange = sprintf('%s %02d:%02d - %02d:%02d', $dayNames[$day] ?? 'Day', $hour, $startMin, $endHour, $endMinFormatted);

        return [
            'day_index' => $day,
            'day_name' => $dayNames[$day] ?? 'Unknown',
            'slot_index' => $slotInDay,
            'grid_index' => $index,
            'time_range' => $timeRange,
            'is_active' => $isActive,
            'active_count' => $activeCount,
            'total_slots' => 672,
            'percentage' => round(($activeCount / 672) * 100, 1)
        ];
    }

    /**
     * Scan Neo4j for text nodes without latte_parsed_at and enqueue them.
     */
    public static function scanAndEnqueueUnparsedTexts($client): int {
        $textLabel = defined('TEXNODE') ? TEXNODE : 'Text';
        $propIdKey = defined('PRIMARIES') && isset(PRIMARIES[$textLabel]) ? PRIMARIES[$textLabel] : 'text_id';

        $query = "
            MATCH (t:{$textLabel})
            WHERE t.latte_parsed_at IS NULL
            RETURN id(t) AS neo_id, t.{$propIdKey} AS prop_id, t.publication AS publication, t.place AS place, t.title AS title
            ORDER BY id(t) ASC
        ";

        $result = $client->run($query);
        $pdo = self::getPdo();

        $checkStmt = $pdo->prepare("
            SELECT id FROM latte_queue
            WHERE text_neo_id = :neo_id AND status IN ('pending', 'processing')
            LIMIT 1
        ");

        $insertStmt = $pdo->prepare("
            INSERT INTO latte_queue (text_neo_id, text_prop_id, title, priority, status, attempts, queued_at)
            VALUES (:neo_id, :prop_id, :title, 0, 'pending', 0, datetime('now'))
        ");

        $enqueuedCount = 0;
        foreach ($result as $record) {
            $neoId = (int)$record->get('neo_id');
            $propId = (int)($record->get('prop_id') ?? $neoId);
            $title = (string)($record->get('publication') ?: ($record->get('place') ?: ($record->get('title') ?: ('Text #' . $propId))));

            $checkStmt->execute([':neo_id' => $neoId]);
            if ($checkStmt->fetch() === false) {
                $insertStmt->execute([
                    ':neo_id' => $neoId,
                    ':prop_id' => $propId,
                    ':title' => $title
                ]);
                $enqueuedCount++;
            }
        }

        self::updateSettings(['last_scan_at' => date('Y-m-d H:i:s')]);
        return $enqueuedCount;
    }

    /**
     * Enqueue a text manually with highest priority (priority = 10).
     */
    public static function enqueueManualText($client, int $neoId): int {
        $pdo = self::getPdo();
        $textLabel = defined('TEXNODE') ? TEXNODE : 'Text';
        $propIdKey = defined('PRIMARIES') && isset(PRIMARIES[$textLabel]) ? PRIMARIES[$textLabel] : 'text_id';

        $query = "
            MATCH (t:{$textLabel})
            WHERE id(t) = \$neoId
            RETURN id(t) AS neo_id, t.{$propIdKey} AS prop_id, t.publication AS publication, t.place AS place, t.title AS title
            LIMIT 1
        ";
        $result = $client->run($query, ['neoId' => $neoId]);
        if ($result->count() === 0) {
            throw new \RuntimeException("Text node with ID {$neoId} not found in graph database.");
        }

        $record = $result->first();
        $propId = (int)($record->get('prop_id') ?? $neoId);
        $title = (string)($record->get('publication') ?: ($record->get('place') ?: ($record->get('title') ?: ('Text #' . $propId))));

        // Check if pending or processing record exists.
        $stmt = $pdo->prepare("SELECT id, status FROM latte_queue WHERE text_neo_id = :neo_id AND status IN ('pending', 'processing') LIMIT 1");
        $stmt->execute([':neo_id' => $neoId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Elevate priority of existing task to highest (10).
            $update = $pdo->prepare("UPDATE latte_queue SET priority = 10, status = 'pending', error_message = NULL WHERE id = :id");
            $update->execute([':id' => $existing['id']]);
            return (int)$existing['id'];
        }

        // Insert new task with high priority.
        $insert = $pdo->prepare("
            INSERT INTO latte_queue (text_neo_id, text_prop_id, title, priority, status, attempts, queued_at)
            VALUES (:neo_id, :prop_id, :title, 10, 'pending', 0, datetime('now'))
        ");
        $insert->execute([
            ':neo_id' => $neoId,
            ':prop_id' => $propId,
            ':title' => $title
        ]);
        return (int)$pdo->lastInsertId();
    }

    /**
     * Fetch the next pending task.
     * Fetch next pending task from the queue.
     * Manual triggers (priority >= 10) are always returned immediately.
     * Automatic tasks (priority < 10) are returned if within the configured window or if $ignoreSchedule is true.
     */
    public static function fetchNextPendingTask(bool $ignoreSchedule = false): ?array {
        $pdo = self::getPdo();

        // 1. Highest priority: look for manual trigger tasks first (priority >= 10).
        $manualStmt = $pdo->query("
            SELECT * FROM latte_queue
            WHERE status = 'pending' AND priority >= 10
            ORDER BY priority DESC, queued_at ASC
            LIMIT 1
        ");
        $manualTask = $manualStmt->fetch();
        if ($manualTask) {
            return $manualTask;
        }

        // 2. Normal priority tasks: require queue enabled and within permitted window (unless schedule ignored).
        if (!$ignoreSchedule && (!self::isQueueEnabled() || !self::isWithinQueueWindow())) {
            return null;
        }

        $autoStmt = $pdo->query("
            SELECT * FROM latte_queue
            WHERE status = 'pending'
            ORDER BY priority DESC, queued_at ASC
            LIMIT 1
        ");
        $autoTask = $autoStmt->fetch();
        return $autoTask ?: null;
    }

    /**
     * Get a queue task by its ID.
     */
    public static function getTaskById(int $id): ?array {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("SELECT * FROM latte_queue WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Spawn background queue worker process asynchronously without blocking the UI.
     */
    public static function spawnWorkerProcess(string $args = '--force'): bool {
        $scriptPath = defined('ROOT_DIR') ? (ROOT_DIR . '/cli/latte_queue_worker.php') : dirname(__DIR__) . '/cli/latte_queue_worker.php';
        $logPath = defined('ROOT_DIR') ? (ROOT_DIR . '/storage/latte_worker.log') : dirname(__DIR__) . '/storage/latte_worker.log';

        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }

        $phpBin = PHP_BINARY;
        if (!$phpBin || !@file_exists($phpBin)) {
            $phpBin = 'php';
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = "start /B \"\" \"{$phpBin}\" \"{$scriptPath}\" {$args} >> \"{$logPath}\" 2>&1";
            $handle = @popen($cmd, 'r');
            if ($handle !== false) {
                @pclose($handle);
                return true;
            }
            return false;
        } else {
            $cmd = escapeshellcmd($phpBin) . ' ' . escapeshellarg($scriptPath) . ' ' . $args . ' >> ' . escapeshellarg($logPath) . ' 2>&1 &';
            @exec($cmd);
            return true;
        }
    }

    /**
     * Mark a task as processing.
     */
    public static function markTaskStarted(int $id): bool {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("
            UPDATE latte_queue
            SET status = 'processing', started_at = datetime('now'), attempts = attempts + 1
            WHERE id = :id
        ");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Mark a task as completed.
     */
    public static function markTaskCompleted(int $id): bool {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("
            UPDATE latte_queue
            SET status = 'completed', completed_at = datetime('now'), error_message = NULL
            WHERE id = :id
        ");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Mark a task as failed, re-queueing if max retries have not been reached.
     */
    public static function markTaskFailed(int $id, string $errorMessage): bool {
        $pdo = self::getPdo();
        $settings = self::getSettings();
        $maxRetries = (int)$settings['max_retries'];

        $checkStmt = $pdo->prepare("SELECT attempts FROM latte_queue WHERE id = :id");
        $checkStmt->execute([':id' => $id]);
        $row = $checkStmt->fetch();
        $attempts = (int)($row['attempts'] ?? 1);

        $newStatus = ($attempts >= $maxRetries) ? 'failed' : 'pending';

        $stmt = $pdo->prepare("
            UPDATE latte_queue
            SET status = :status, error_message = :err, completed_at = CASE WHEN :status = 'failed' THEN datetime('now') ELSE NULL END
            WHERE id = :id
        ");
        return $stmt->execute([
            ':status' => $newStatus,
            ':err' => mb_substr($errorMessage, 0, 500, 'UTF-8'),
            ':id' => $id
        ]);
    }

    /**
     * Call the LATTE connector HTTP microservice.
     */
    public static function callConnector(string $method, array $payload): array {
        $servlet = defined('CONNECTORENDPOINT') ? CONNECTORENDPOINT : 'latte_connector:8000';
        if (!preg_match('#^https?://#i', $servlet)) {
            $servlet = 'http://' . $servlet;
        }
        $url = rtrim($servlet, '/') . '/' . ltrim($method, '/');

        $jsonData = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 240);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 128 * 1024);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($jsonData),
            'Expect:'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlErr) {
            throw new \RuntimeException("Failed to connect to LATTE connector at {$url}: {$curlErr}");
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException("LATTE connector returned HTTP {$httpCode}: {$response}");
        }

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            throw new \RuntimeException("Invalid JSON response from LATTE connector.");
        }

        return $decoded;
    }

    /**
     * Process a single queued task end-to-end.
     */
    public static function processTask(array $task, $client): array {
        $taskId = (int)$task['id'];
        $neoId = (int)$task['text_neo_id'];

        self::markTaskStarted($taskId);

        try {
            // Fetch text content from Neo4j.
            $textProperty = defined('TEXNODETEXT') ? TEXNODETEXT : 'text';
            $query = 'MATCH (n) WHERE id(n) = $nodeid RETURN n.' . $textProperty . ' AS textContent, n.language AS lang LIMIT 1';
            $result = $client->run($query, ['nodeid' => $neoId]);
            if ($result->count() === 0) {
                throw new \RuntimeException("Text node ID {$neoId} not found in Neo4j.");
            }

            $first = $result->first();
            $textContent = (string)($first->get('textContent') ?? '');
            $lang = (string)($first->get('lang') ?? '');

            if (trim($textContent) === '') {
                // Empty text, mark completed with 0 suggestions.
                self::saveSuggestionsToTextNode($client, $neoId, [], $lang ?: 'unknown', []);
                self::markTaskCompleted($taskId);
                return ['success' => true, 'count' => 0];
            }

            // Detect language if missing.
            if (!$lang) {
                $langPayload = [
                    'text' => $textContent,
                    'nodeid' => $neoId,
                    'uri' => defined('URI') ? URI : '',
                    'extractor' => defined('LANGEXTRACTOR') ? LANGEXTRACTOR : 'langid',
                    'username' => defined('USERNAME') ? USERNAME : '',
                    'password' => defined('PASSWORD') ? PASSWORD : '',
                    'database' => defined('DBNAME') ? DBNAME : '',
                    'textlabel' => defined('TEXNODE') ? TEXNODE : 'Text',
                    'textproperty' => $textProperty
                ];

                try {
                    $langRes = self::callConnector('detect_language', $langPayload);
                    $lang = $langRes['ISO_code'] ?? ($langRes['language'] ?? 'en');
                } catch (\Throwable $e) {
                    $lang = 'en';
                }
            }

            // Extract entities.
            $entityPayload = [
                'text' => $textContent,
                'lang' => $lang,
                'nodeid' => $neoId,
                'uri' => defined('URI') ? URI : '',
                'username' => defined('USERNAME') ? USERNAME : '',
                'password' => defined('PASSWORD') ? PASSWORD : '',
                'database' => defined('DBNAME') ? DBNAME : '',
                'textlabel' => defined('TEXNODE') ? TEXNODE : 'Text',
                'textproperty' => $textProperty
            ];

            $extractRes = self::callConnector('extract_entities', $entityPayload);
            $entitiesData = $extractRes['data'] ?? [];
            $metaData = $extractRes['meta'] ?? [];

            self::saveSuggestionsToTextNode($client, $neoId, $entitiesData, $lang, $metaData);
            self::markTaskCompleted($taskId);

            return [
                'success' => true,
                'count' => count($entitiesData),
                'language' => $lang
            ];
        } catch (\Throwable $e) {
            self::markTaskFailed($taskId, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Save extracted suggestions directly onto the text node in Neo4j.
     */
    public static function saveSuggestionsToTextNode($client, int $neoId, array $entities, string $lang, array $meta = []): array {
        $textLabel = defined('TEXNODE') ? TEXNODE : 'Text';
        $formattedSuggestions = [];

        foreach ($entities as $e) {
            $sugId = 'latte_sug_' . bin2hex(random_bytes(6));
            $start = (int)($e['startPos'] ?? ($e['start'] ?? 0));
            $stop = (int)($e['endPos'] ?? ($e['stop'] ?? 0));
            $label = (string)($e['labelTex'] ?? ($e['label'] ?? 'Entity'));
            $text = (string)($e['text'] ?? '');

            $formattedSuggestions[] = [
                'id' => $sugId,
                'annotation' => $sugId,
                'start' => $start,
                'stop' => $stop,
                'startPos' => $start,
                'endPos' => $stop,
                'label' => $label,
                'labelTex' => $label,
                'text' => $text,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        $jsonSuggestions = json_encode($formattedSuggestions, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        $query = "
            MATCH (t:{$textLabel})
            WHERE id(t) = \$neoId
            SET t.latte_suggestions = \$suggestions,
                t.latte_parsed_at = datetime(),
                t.latte_language = \$lang,
                t.latte_entity_count = \$entityCount
            RETURN t
        ";

        $client->run($query, [
            'neoId' => $neoId,
            'suggestions' => $jsonSuggestions,
            'lang' => $lang,
            'entityCount' => count($formattedSuggestions)
        ]);

        // Clean up any legacy Annotation_auto nodes for this text to prevent duplicated highlights.
        try {
            $client->run("
                OPTIONAL MATCH (t:{$textLabel})-[r:contains]->(a:Annotation_auto)
                WHERE id(t) = \$neoId
                DETACH DELETE a
            ", ['neoId' => $neoId]);
        } catch (\Throwable $e) {
            // Ignore if Annotation_auto does not exist.
        }

        // Synchronize SQLite queue: mark any pending or processing tasks for this text as completed.
        $pdo = self::getPdo();
        $syncStmt = $pdo->prepare("
            UPDATE latte_queue
            SET status = 'completed', completed_at = datetime('now'), error_message = NULL
            WHERE text_neo_id = :neo_id AND status IN ('pending', 'processing')
        ");
        $syncStmt->execute([':neo_id' => $neoId]);

        return $formattedSuggestions;
    }

    /**
     * Clear all automatic annotations from a text without resetting the text node to a pending state.
     * Note: t.latte_parsed_at is preserved so the background queue will not pick it up again.
     */
    public static function clearTextSuggestions($client, int $neoId): bool {
        $textLabel = defined('TEXNODE') ? TEXNODE : 'Text';

        $query = "
            MATCH (t:{$textLabel})
            WHERE id(t) = \$neoId
            SET t.latte_suggestions = '[]',
                t.latte_entity_count = 0,
                t.latte_parsed_at = CASE WHEN t.latte_parsed_at IS NULL THEN datetime() ELSE t.latte_parsed_at END
            RETURN t
        ";

        $client->run($query, ['neoId' => $neoId]);

        // Delete any legacy Annotation_auto nodes if present.
        try {
            $client->run("
                OPTIONAL MATCH (t:{$textLabel})-[r:contains]->(a:Annotation_auto)
                WHERE id(t) = \$neoId
                DETACH DELETE a
            ", ['neoId' => $neoId]);
        } catch (\Throwable $e) {
            // Non critical.
        }

        // Cancel any pending queue entry.
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("
            UPDATE latte_queue
            SET status = 'completed', completed_at = datetime('now')
            WHERE text_neo_id = :neo_id AND status IN ('pending', 'processing')
        ");
        $stmt->execute([':neo_id' => $neoId]);

        return true;
    }

    /**
     * Remove an individual suggested annotation from a text node.
     */
    /**
     * Remove multiple automatic annotation suggestions by id from a text node.
     */
    public static function removeMultipleSuggestions($client, int $neoId, array $suggestionIds): array {
        $textLabel = defined('TEXNODE') ? TEXNODE : 'Text';

        $query = "
            MATCH (t:{$textLabel})
            WHERE id(t) = \$neoId
            RETURN t.latte_suggestions AS suggestions
        ";

        $result = $client->run($query, ['neoId' => $neoId]);
        if ($result->count() === 0) {
            return ['success' => false, 'removed_count' => 0];
        }

        $raw = $result->first()->get('suggestions');
        $suggestions = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);
        if (!is_array($suggestions)) {
            $suggestions = [];
        }

        $idSet = array_flip($suggestionIds);
        $remaining = [];
        $removedCount = 0;
        foreach ($suggestions as $s) {
            $id = $s['id'] ?? ($s['annotation'] ?? '');
            if (!isset($idSet[$id])) {
                $remaining[] = $s;
            } else {
                $removedCount++;
            }
        }

        $newJson = json_encode($remaining, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        $updateQuery = "
            MATCH (t:{$textLabel})
            WHERE id(t) = \$neoId
            SET t.latte_suggestions = \$newJson,
                t.latte_entity_count = \$count
            RETURN t
        ";

        $client->run($updateQuery, [
            'neoId' => $neoId,
            'newJson' => $newJson,
            'count' => count($remaining)
        ]);

        return ['success' => true, 'removed_count' => $removedCount];
    }

    /**
     * Remove a single automatic annotation suggestion by id from a text node.
     */
    public static function removeSingleSuggestion($client, int $neoId, string $suggestionId): bool {
        $res = self::removeMultipleSuggestions($client, $neoId, [$suggestionId]);
        return ($res['success'] && $res['removed_count'] > 0);
    }

    /**
     * Retrim an automatic annotation suggestion's boundary offsets.
     */
    public static function retrimSuggestion($client, int $neoId, string $suggestionId, int $newStart, int $newStop): ?array {
        $textLabel = defined('TEXNODE') ? TEXNODE : 'Text';
        $textProperty = defined('TEXNODETEXT') ? TEXNODETEXT : 'text';

        $query = "
            MATCH (t:{$textLabel})
            WHERE id(t) = \$neoId
            RETURN t.latte_suggestions AS suggestions, t.{$textProperty} AS textContent
            LIMIT 1
        ";

        $result = $client->run($query, ['neoId' => $neoId]);
        if ($result->count() === 0) {
            return null;
        }

        $row = $result->first();
        $fullText = (string)($row->get('textContent') ?? '');
        $raw = $row->get('suggestions');
        $suggestions = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);
        if (!is_array($suggestions)) {
            $suggestions = [];
        }

        $textLen = mb_strlen($fullText);
        $newStart = max(0, min($newStart, $textLen - 1));
        $newStop = max($newStart, min($newStop, $textLen - 1));
        $newText = mb_substr($fullText, $newStart, ($newStop - $newStart + 1));

        $updatedSug = null;
        foreach ($suggestions as &$s) {
            $id = $s['id'] ?? ($s['annotation'] ?? '');
            if ($id === $suggestionId) {
                $s['start'] = $newStart;
                $s['stop'] = $newStop;
                $s['startPos'] = $newStart;
                $s['endPos'] = $newStop;
                $s['text'] = $newText;
                $s['updated_at'] = date('Y-m-d H:i:s');
                $updatedSug = $s;
                break;
            }
        }
        unset($s);

        if (!$updatedSug) {
            return null;
        }

        $updateQuery = "
            MATCH (t:{$textLabel})
            WHERE id(t) = \$neoId
            SET t.latte_suggestions = \$json
            RETURN t
        ";

        $client->run($updateQuery, [
            'neoId' => $neoId,
            'json' => json_encode($suggestions, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)
        ]);

        return $updatedSug;
    }

    /**
     * Promote an automatic suggestion to an entity annotation.
     * Removes overlapping suggestion entries from the text node's latte_suggestions.
     */
    public static function promoteSuggestion($client, int $neoId, int $start, int $stop): bool {
        $textLabel = defined('TEXNODE') ? TEXNODE : 'Text';

        $query = "
            MATCH (t:{$textLabel})
            WHERE id(t) = \$neoId
            RETURN t.latte_suggestions AS suggestions
        ";

        $result = $client->run($query, ['neoId' => $neoId]);
        if ($result->count() === 0) {
            return false;
        }

        $raw = $result->first()->get('suggestions');
        $suggestions = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);
        if (!is_array($suggestions) || empty($suggestions)) {
            return true;
        }

        $remaining = [];
        foreach ($suggestions as $s) {
            $sStart = (int)($s['start'] ?? ($s['startPos'] ?? -1));
            $sStop = (int)($s['stop'] ?? ($s['endPos'] ?? -1));

            // Exclude suggestion if it overlaps the newly annotated range.
            $overlaps = !($sStop < $start || $sStart > $stop);
            if (!$overlaps) {
                $remaining[] = $s;
            }
        }

        $newJson = json_encode($remaining, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        $updateQuery = "
            MATCH (t:{$textLabel})
            WHERE id(t) = \$neoId
            SET t.latte_suggestions = \$newJson,
                t.latte_entity_count = \$count
            RETURN t
        ";

        $client->run($updateQuery, [
            'neoId' => $neoId,
            'newJson' => $newJson,
            'count' => count($remaining)
        ]);

        return true;
    }

    /**
     * Get queue summary statistics.
     */
    public static function getQueueStats(): array {
        $pdo = self::getPdo();
        $stmt = $pdo->query("
            SELECT
                COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending,
                COUNT(CASE WHEN status = 'processing' THEN 1 END) AS processing,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) AS completed,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) AS failed,
                COUNT(*) AS total
            FROM latte_queue
        ");
        return $stmt->fetch() ?: ['pending' => 0, 'processing' => 0, 'completed' => 0, 'failed' => 0, 'total' => 0];
    }

    /**
     * Get total count of queue items matching status filter.
     */
    public static function getQueueItemsCount(?string $statusFilter = null): int {
        $pdo = self::getPdo();
        $sql = "SELECT COUNT(*) FROM latte_queue";
        $params = [];
        if ($statusFilter) {
            $sql .= " WHERE status = :status";
            $params[':status'] = $statusFilter;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get paginated and sorted queue items.
     */
    public static function getQueueItems(
        int $limit = 25,
        int $offset = 0,
        ?string $statusFilter = null,
        string $sortBy = 'default',
        string $sortDir = 'desc'
    ): array {
        $pdo = self::getPdo();
        $sql = "SELECT * FROM latte_queue";
        $params = [];

        if ($statusFilter) {
            $sql .= " WHERE status = :status";
            $params[':status'] = $statusFilter;
        }

        $dir = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';

        switch (strtolower($sortBy)) {
            case 'id':
                $sql .= " ORDER BY id {$dir}";
                break;
            case 'title':
                $sql .= " ORDER BY title {$dir}, id {$dir}";
                break;
            case 'priority':
                $sql .= " ORDER BY priority {$dir}, queued_at ASC";
                break;
            case 'status':
                $sql .= " ORDER BY status {$dir}, id {$dir}";
                break;
            case 'attempts':
                $sql .= " ORDER BY attempts {$dir}, id {$dir}";
                break;
            case 'queued_at':
                $sql .= " ORDER BY queued_at {$dir}, id {$dir}";
                break;
            case 'completed_at':
                $sql .= " ORDER BY completed_at {$dir}, id {$dir}";
                break;
            case 'default':
            default:
                $sql .= " ORDER BY status = 'pending' DESC, priority DESC, id DESC";
                break;
        }

        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Check if the background worker daemon is currently active.
     */
    public static function isDaemonRunning(): array {
        $pidFile = defined('ROOT_DIR') ? (ROOT_DIR . '/storage/latte_daemon.pid') : dirname(__DIR__) . '/storage/latte_daemon.pid';
        if (!file_exists($pidFile)) {
            return ['running' => false, 'pid' => null];
        }
        $pid = (int)trim(@file_get_contents($pidFile));
        if ($pid <= 0) {
            @unlink($pidFile);
            return ['running' => false, 'pid' => null];
        }
        if (file_exists("/proc/{$pid}")) {
            $cmd = @file_get_contents("/proc/{$pid}/cmdline");
            if ($cmd !== false && strpos($cmd, 'latte_queue_worker.php') !== false) {
                return ['running' => true, 'pid' => $pid];
            }
        }
        if (function_exists('posix_kill')) {
            if (@posix_kill($pid, 0)) {
                return ['running' => true, 'pid' => $pid];
            }
        }
        @unlink($pidFile);
        return ['running' => false, 'pid' => null];
    }

    /**
     * Start the background worker daemon process.
     */
    public static function startDaemon(): bool {
        $status = self::isDaemonRunning();
        if ($status['running']) {
            return true;
        }
        $stopFile = defined('ROOT_DIR') ? (ROOT_DIR . '/storage/latte_daemon.stop') : dirname(__DIR__) . '/storage/latte_daemon.stop';
        if (file_exists($stopFile)) {
            @unlink($stopFile);
        }
        $spawned = self::spawnWorkerProcess('--daemon');
        if (!$spawned) {
            return false;
        }
        // Poll for up to 1.5 seconds for the daemon PID to be registered and confirmed active
        for ($i = 0; $i < 15; $i++) {
            usleep(100000); // 100ms
            $check = self::isDaemonRunning();
            if ($check['running']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Signal the background worker daemon process to stop.
     */
    public static function stopDaemon(): bool {
        $status = self::isDaemonRunning();
        $stopFile = defined('ROOT_DIR') ? (ROOT_DIR . '/storage/latte_daemon.stop') : dirname(__DIR__) . '/storage/latte_daemon.stop';
        $pidFile = defined('ROOT_DIR') ? (ROOT_DIR . '/storage/latte_daemon.pid') : dirname(__DIR__) . '/storage/latte_daemon.pid';
        @file_put_contents($stopFile, (string)time());
        if ($status['running'] && $status['pid']) {
            $sigterm = defined('SIGTERM') ? SIGTERM : 15;
            if (function_exists('posix_kill')) {
                @posix_kill((int)$status['pid'], $sigterm);
            } elseif (DIRECTORY_SEPARATOR !== '\\') {
                @exec('kill -15 ' . (int)$status['pid'] . ' 2>&1');
            }
            // Wait up to 1.5 seconds for the process to exit
            for ($i = 0; $i < 15; $i++) {
                usleep(100000); // 100ms
                $check = self::isDaemonRunning();
                if (!$check['running']) {
                    break;
                }
            }
        }
        if (file_exists($pidFile)) {
            $pid = (int)trim(@file_get_contents($pidFile));
            if (!file_exists("/proc/{$pid}")) {
                @unlink($pidFile);
            }
        }
        if (file_exists($stopFile)) {
            @unlink($stopFile);
        }
        return true;
    }

    /**
     * Reset failed tasks to pending.
     */
    public static function retryFailedTasks(): int {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("
            UPDATE latte_queue
            SET status = 'pending', attempts = 0, error_message = NULL, completed_at = NULL
            WHERE status = 'failed'
        ");
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Delete completed tasks from queue history.
     */
    public static function clearCompletedTasks(): int {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("DELETE FROM latte_queue WHERE status = 'completed'");
        $stmt->execute();
        return $stmt->rowCount();
    }
}
