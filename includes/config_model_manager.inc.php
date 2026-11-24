<?php
/**
 * Configuration model manager.
 * Handles persistence of entity models, property definitions, and system settings in SQLite,
 * and maintains an optimized compiled PHP cache for zero-query runtime performance.
 */

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}

class ConfigModelManager {
    protected static $dbPath;
    protected static $cachePath;
    protected static $pdoInstance = null;

    /**
     * Get the absolute path to the SQLite configuration database.
     */
    public static function getDatabasePath(): string {
        if (!self::$dbPath) {
            self::$dbPath = ROOT_DIR . '/storage/schema_config.sqlite';
        }
        return self::$dbPath;
    }

    /**
     * Get the absolute path to the compiled cache file.
     */
    public static function getCachePath(): string {
        if (!self::$cachePath) {
            self::$cachePath = ROOT_DIR . '/storage/cache/entity_model.cache.php';
        }
        return self::$cachePath;
    }

    /**
     * Ensure config/config.inc.php exists on disk; if missing, generate it automatically.
     */
    public static function ensureConfigFile(): void {
        $configFile = ROOT_DIR . '/config/config.inc.php';
        if (file_exists($configFile)) {
            return;
        }

        $configDir = dirname($configFile);
        if (!is_dir($configDir)) {
            mkdir($configDir, 0777, true);
        }

        $code = <<<'PHP'
<?php
/* System environment: base root directory */
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}

/* Debug mode: if true, detailed error messages and stack traces are displayed. */
$debug = true;
if (!defined('DEBUG')) {
    define('DEBUG', $debug);
}

/* Load configuration manager */
include_once(ROOT_DIR . '/includes/config_model_manager.inc.php');

/* Base URL calculation */
if (!defined('BASE_URL')) {
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
    $appRoot = str_replace('\\', '/', realpath(ROOT_DIR));
    if ($docRoot && $appRoot && strpos($appRoot, $docRoot) === 0) {
        $subPath = substr($appRoot, strlen($docRoot));
        define('BASE_URL', rtrim(str_replace('\\', '/', $subPath), '/'));
    } else {
        define('BASE_URL', '');
    }
}

/* Guard: show holding page if base configuration is missing */
if (!ConfigModelManager::isInstalled()) {
    $currentScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (strpos($currentScript, '/onboarding/') === false && php_sapi_name() !== 'cli') {
        ConfigModelManager::renderHoldingPage();
    }
}

$modelConfig = ConfigModelManager::getModel();
$settings = $modelConfig['settings'] ?? [];

/* Define database and connection constants */
define("PROJECTNAME", $settings['project_name'] ?? 'HIPE data');
define("DBNAME", $settings['db_name'] ?? 'finaldocwriteup.db');
define("USERNAME", $settings['db_user'] ?? 'neo4j');
define("PASSWORD", $settings['db_password'] ?? '');
define("HOSTNAME", $settings['db_host'] ?? 'localhost');
define("HOSTPORT", (int)($settings['db_port'] ?? 7700));
define("URI", $settings['uri'] ?? ("neo4j://" . HOSTNAME . ":" . HOSTPORT));

/* Latte connector integration */
define("LATTECONNECTOR", !empty($settings['use_latte']));
define("LANGEXTRACTOR", $settings['language_engine'] ?? 'langid');
define("CONNECTORENDPOINT", $settings['latte_connector'] ?? 'latte_connector:8000');

/* Email and SMTP connection */
define("PROTOCOL", $settings['mail_protocol'] ?? 'SMTP');
define("SERVERORIGMAIL", $settings['origin_email'] ?? 'siteadmin@example.org');
define("SMTPSERVERADR", $settings['smtp_server'] ?? '');
define("SMTPPORT", (int)($settings['smtp_port'] ?? 25));
define("SMTPUSER", !empty($settings['smtp_user']) ? $settings['smtp_user'] : false);
define("SMTPPASSWORD", !empty($settings['smtp_password']) ? $settings['smtp_password'] : false);
define("SMTPPATCH", !empty($settings['smtp_self_signed']));

/* Public visibility and registration policy */
define("TEXTSAREPUBLIC", !empty($settings['texts_public']));
define("ENTITIESAREPUBLIC", !empty($settings['entities_public']));
define("REGISTRATIONPOLICY", (int)($settings['registration_policy'] ?? 1));

/* Matomo analytics integration */
define("MATOMO_ENABLED", !empty($settings['matomo_enabled']));
define("MATOMO_URL", trim($settings['matomo_url'] ?? ''));
define("MATOMO_SITE_ID", (int)($settings['matomo_site_id'] ?? 0));
define("MATOMO_JS_PATH", trim($settings['matomo_js_path'] ?? 'matomo.js') ?: 'matomo.js');
define("MATOMO_PHP_PATH", trim($settings['matomo_php_path'] ?? 'matomo.php') ?: 'matomo.php');
define("MATOMO_EXCLUDE_LOGGED_IN", !empty($settings['matomo_exclude_logged_in']));
define("MATOMO_HONOR_DNT", !empty($settings['matomo_honor_dnt']));

/* Schema model constants */
define("NODES", $modelConfig['nodes'] ?? []);
define("NODEMODEL", $modelConfig['nodesDatamodel'] ?? []);
define("CORENODES", $modelConfig['matchOnNodes'] ?? []);
define("NODETRANSLATIONS", $modelConfig['nodes_translate'] ?? []);
define("PRIMARIES", $modelConfig['primaryKeys'] ?? []);

/* System-critical node mappings */
define("ANNOSTART", 'starts');
define("ANNOSTOP", 'stops');
define("TEXNODE", 'Text');
define("ANNONODE", 'Annotation');
define("TEXNODETEXT", 'text');
define("PRIVATEPROPERTIES", ['uid']);
define("EDGETRANSLATIONS", [
    'same_as' => 'Variant',
    'see_also' => 'Knowledgebase relations',
]);

/* Entity extractor */
define("ENTITYEXTRACTOR", 'local');
define("NERCOLOR", 'rgba(94,94,94,0.6)');

/* Configured or auto-detected website base URL */
$configuredWebUrl = trim($settings['base_url'] ?? '');
define("WEBURL", $configuredWebUrl !== '' ? rtrim($configuredWebUrl, '/') : BASE_URL);

/* Global error and exception handling */
include_once(ROOT_DIR . '/includes/error_handler.inc.php');
PHP;

        file_put_contents($configFile, $code, LOCK_EX);
    }

    /**
     * Get or create PDO connection to the SQLite database.
     */
    public static function getPdo(): PDO {
        if (self::$pdoInstance !== null) {
            return self::$pdoInstance;
        }

        $dbFile = self::getDatabasePath();
        $dbDir = dirname($dbFile);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
        }

        $isNew = !file_exists($dbFile);
        $pdo = new PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        $pdo->exec('PRAGMA journal_mode = WAL;');

        self::$pdoInstance = $pdo;

        if ($isNew) {
            self::initSchema($pdo);
        }

        return self::$pdoInstance;
    }

    /**
     * Initialize database schema if tables do not exist.
     */
    public static function initSchema(?PDO $pdo = null): void {
        $db = $pdo ?? self::getPdo();

        $db->exec("
            CREATE TABLE IF NOT EXISTS entity_types (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL,
                plural_label TEXT NOT NULL,
                color TEXT NOT NULL DEFAULT 'rgba(39, 123, 245, 0.6)',
                is_searchable INTEGER NOT NULL DEFAULT 1,
                is_system INTEGER NOT NULL DEFAULT 0,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS entity_properties (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                entity_type_id INTEGER NOT NULL REFERENCES entity_types(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                display_name TEXT NOT NULL,
                data_type TEXT NOT NULL DEFAULT 'string',
                is_unique INTEGER NOT NULL DEFAULT 0,
                is_visual_distinguishing INTEGER NOT NULL DEFAULT 0,
                is_searchable INTEGER NOT NULL DEFAULT 1,
                is_system INTEGER NOT NULL DEFAULT 0,
                sort_order INTEGER NOT NULL DEFAULT 0,
                UNIQUE(entity_type_id, name)
            );

            CREATE TABLE IF NOT EXISTS extra_translations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                trans_key TEXT UNIQUE NOT NULL,
                trans_value TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS system_settings (
                setting_key TEXT PRIMARY KEY,
                setting_value TEXT
            );
        ");
    }

    /**
     * Get all system settings from database.
     * Returns empty array if no configuration has been entered yet.
     */
    public static function getAllSettings(): array {
        $db = self::getPdo();
        self::initSchema($db);

        $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings");
        $rows = $stmt->fetchAll();

        $saved = [];
        foreach ($rows as $r) {
            $saved[$r['setting_key']] = $r['setting_value'];
        }

        if (isset($saved['db_host'], $saved['db_port']) && $saved['db_host'] !== '' && $saved['db_port'] !== '') {
            $saved['uri'] = 'neo4j://' . $saved['db_host'] . ':' . $saved['db_port'];
        }

        return $saved;
    }

    /**
     * Get path to the setup secret token file.
     */
    public static function getSetupSecretPath(): string {
        return ROOT_DIR . '/storage/setup_secret.txt';
    }

    /**
     * Get existing setup secret or create a fresh cryptographic token.
     */
    public static function getOrCreateSetupSecret(): string {
        $secretFile = self::getSetupSecretPath();
        if (file_exists($secretFile)) {
            $token = trim(file_get_contents($secretFile));
            if ($token !== '') {
                return $token;
            }
        }

        $token = bin2hex(random_bytes(24));
        $dir = dirname($secretFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($secretFile, $token, LOCK_EX);
        return $token;
    }

    /**
     * Check if client is authorized to access setup.
     * Authorized if:
     * - Request is running in CLI.
     * - Request is originating from localhost / loopback.
     * - Valid setup token is provided matching storage/setup_secret.txt.
     */
    public static function isSetupAuthorized(?string $submittedToken = null): bool {
        if (php_sapi_name() === 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
            return true;
        }

        // Local development bypass (IPv4 loopback, IPv6 loopback, localhost).
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        if (in_array($remoteAddr, ['127.0.0.1', '::1', 'fe80::1'], true)) {
            return true;
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $secret = self::getOrCreateSetupSecret();
        if ($submittedToken === null) {
            $submittedToken = $_POST['setup_token'] ?? $_GET['token'] ?? ($_SESSION['setup_token'] ?? null);
        }

        if (is_string($submittedToken) && hash_equals($secret, trim($submittedToken))) {
            $_SESSION['setup_token'] = trim($submittedToken);
            return true;
        }

        return false;
    }

    /**
     * Consume setup secret token when onboarding completes.
     */
    public static function consumeSetupSecret(): void {
        $secretFile = self::getSetupSecretPath();
        if (file_exists($secretFile)) {
            unlink($secretFile);
        }
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['setup_token'])) {
            unset($_SESSION['setup_token']);
        }
    }

    /**
     * Render the public 'Waiting for setup to complete' holding page.
     */
    public static function renderHoldingPage(
        string $title = 'Waiting for setup to complete',
        string $message = 'This service is currently being initialized by the system administrator. Please check back shortly.'
    ): void {
        if (!headers_sent()) {
            http_response_code(503);
            header('Retry-After: 300');
        }
        $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

        // Calculate base url
        $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
        $appRoot = str_replace('\\', '/', realpath(ROOT_DIR));
        $baseUrl = '';
        if ($docRoot && $appRoot && strpos($appRoot, $docRoot) === 0) {
            $subPath = substr($appRoot, strlen($docRoot));
            $baseUrl = rtrim(str_replace('\\', '/', $subPath), '/');
        }
        $onboardingUrl = $baseUrl . '/onboarding/index.php';
        ?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/CSS/stylePublic.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl border border-slate-200/90 shadow-sm p-8 text-center">
        <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl mx-auto mb-4">
            <i class="fas fa-tools"></i>
        </div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="mt-2 text-sm text-slate-600 leading-relaxed">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </p>

        <?php if ($isLocal): ?>
            <div class="mt-6 pt-6 border-t border-slate-100">
                <p class="text-xs text-slate-400 mb-3">Administrator shortcut (local development only):</p>
                <a href="<?= $onboardingUrl ?>"
                   class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition-colors shadow-xs">
                    <i class="fas fa-rocket text-xs"></i>
                    <span>Launch setup wizard</span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
        <?php
        exit();
    }

    /**
     * Get a specific system setting.
     */
    public static function getSetting(string $key, $default = null) {
        $settings = self::getAllSettings();
        return $settings[$key] ?? $default;
    }

    /**
     * Save multiple system settings into SQLite.
     */
    public static function saveSettings(array $settings, bool $triggerRebuild = true): void {
        $db = self::getPdo();
        self::initSchema($db);

        $stmt = $db->prepare("
            INSERT INTO system_settings (setting_key, setting_value)
            VALUES (:key, :value)
            ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value
        ");

        $db->beginTransaction();
        try {
            foreach ($settings as $key => $val) {
                if (is_bool($val)) {
                    $val = $val ? '1' : '0';
                } elseif ($val === null) {
                    $val = '';
                } else {
                    $val = (string)$val;
                }
                $stmt->execute([':key' => $key, ':value' => $val]);
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        if ($triggerRebuild) {
            self::rebuildCache();
        }
    }

    /**
     * Check if system is installed with a valid base configuration and active admin.
     */
    public static function isInstalled(): bool {
        try {
            $settings = self::getAllSettings();
            if (empty($settings['is_installed']) || (string)$settings['is_installed'] !== '1') {
                return false;
            }

            if (empty($settings['db_host']) || empty($settings['db_port'])) {
                return false;
            }

            $userDb = ROOT_DIR . '/storage/users.sqlite';
            if (!file_exists($userDb)) {
                return false;
            }

            $pdo = new PDO('sqlite:' . $userDb);
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM userdata WHERE LOWER(role) = 'admin' AND blocked = 0");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ((int)($row['count'] ?? 0)) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Create an administrator account in users.sqlite.
     */
    public static function createAdminUser(string $username, string $email, string $password): array {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address.');
        }

        if (strlen($username) < 3) {
            throw new InvalidArgumentException('Username must be at least 3 characters long.');
        }

        if (strlen($password) < 6) {
            throw new InvalidArgumentException('Password must be at least 6 characters long.');
        }

        $userDb = ROOT_DIR . '/storage/users.sqlite';
        $userDir = dirname($userDb);
        if (!is_dir($userDir)) {
            mkdir($userDir, 0777, true);
        }

        $pdo = new PDO('sqlite:' . $userDb);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS userdata (
                id INTEGER PRIMARY KEY,
                uuid TEXT,
                logon_attempts INTEGER DEFAULT 0,
                mail TEXT,
                username TEXT,
                password TEXT,
                role TEXT,
                wd_property_preferences TEXT DEFAULT '',
                wd_titlestring_preferences TEXT DEFAULT '',
                wd_wikilink_preferences TEXT DEFAULT '',
                wd_language_preferences TEXT DEFAULT '',
                token TEXT DEFAULT NULL,
                completed INTEGER DEFAULT 1,
                blocked INTEGER DEFAULT 0
            )
        ");

        // Check if admin with this email exists.
        $check = $pdo->prepare("SELECT COUNT(*) as count FROM userdata WHERE mail = :mail");
        $check->execute([':mail' => $email]);
        $row = $check->fetch(PDO::FETCH_ASSOC);
        if ((int)$row['count'] > 0) {
            throw new RuntimeException('A user with this email address already exists.');
        }

        // Generate UUID v4.
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        $hashedPw = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare("
            INSERT INTO userdata (uuid, logon_attempts, mail, username, password, role, completed, blocked)
            VALUES (:uuid, 0, :mail, :username, :password, 'Admin', 1, 0)
        ");
        $insert->execute([
            ':uuid' => $uuid,
            ':mail' => $email,
            ':username' => $username,
            ':password' => $hashedPw,
        ]);

        return [
            'id' => (int)$pdo->lastInsertId(),
            'uuid' => $uuid,
            'username' => $username,
            'email' => $email,
        ];
    }

    /**
     * Seed database from raw arrays if empty.
     */
    public static function seedFromArrays(array $nodesDatamodel, array $matchOnNodes, array $nodesTranslate): bool {
        $db = self::getPdo();
        self::initSchema($db);

        $check = $db->query("SELECT COUNT(*) as count FROM entity_types")->fetch();
        if ((int)$check['count'] > 0) {
            return false;
        }

        $db->beginTransaction();
        try {
            $insertType = $db->prepare("
                INSERT INTO entity_types (name, plural_label, color, is_searchable, is_system, sort_order)
                VALUES (:name, :plural_label, :color, :is_searchable, :is_system, :sort_order)
            ");

            $insertProp = $db->prepare("
                INSERT INTO entity_properties 
                (entity_type_id, name, display_name, data_type, is_unique, is_visual_distinguishing, is_searchable, is_system, sort_order)
                VALUES (:entity_type_id, :name, :display_name, :data_type, :is_unique, :is_visual_distinguishing, :is_searchable, :is_system, :sort_order)
            ");

            $insertTrans = $db->prepare("
                INSERT OR IGNORE INTO extra_translations (trans_key, trans_value)
                VALUES (:trans_key, :trans_value)
            ");

            $sortOrder = 0;
            $systemEntities = ['Text', 'Annotation', 'Variant'];

            foreach ($nodesDatamodel as $typeName => $properties) {
                // Force capitalization of node names (e.g. Text); and force lowercasing of node properties)
                $typeName = ucfirst(preg_replace('/\s+/', '', trim((string)$typeName)));
                $sortOrder += 10;
                $plural = $nodesTranslate[$typeName] ?? $typeName;
                $color = $matchOnNodes[$typeName] ?? 'rgba(100, 100, 100, 0.6)';
                $isSearchable = array_key_exists($typeName, $matchOnNodes) ? 1 : 0;
                $isSystem = in_array($typeName, $systemEntities, true) ? 1 : 0;

                $insertType->execute([
                    ':name' => $typeName,
                    ':plural_label' => $plural,
                    ':color' => $color,
                    ':is_searchable' => $isSearchable,
                    ':is_system' => $isSystem,
                    ':sort_order' => $sortOrder,
                ]);
                $entityTypeId = (int)$db->lastInsertId();

                $propSort = 0;
                foreach ($properties as $propName => $propDef) {
                    // Force capitalization of node names (e.g. Text); and force lowercasing of node properties)
                    $propName = strtolower(preg_replace('/\s+/', '', trim((string)$propName)));
                    $propSort += 10;
                    $displayName = $propDef[0] ?? $propName;
                    $dataType = $propDef[1] ?? 'string';
                    $isUnique = !empty($propDef[2]) ? 1 : 0;
                    $isVisual = !empty($propDef[3]) ? 1 : 0;
                    $isPropSearchable = !empty($propDef[4]) ? 1 : 0;

                    $isPropSystem = 0;
                    if ($typeName === 'Annotation' && in_array($propName, ['starts', 'stops'], true)) {
                        $isPropSystem = 1;
                    } elseif ($typeName === 'Variant' && $propName === 'variant') {
                        $isPropSystem = 1;
                    } elseif ($typeName === 'Text' && $propName === 'text') {
                        $isPropSystem = 1;
                    }

                    $insertProp->execute([
                        ':entity_type_id' => $entityTypeId,
                        ':name' => $propName,
                        ':display_name' => $displayName,
                        ':data_type' => $dataType,
                        ':is_unique' => $isUnique,
                        ':is_visual_distinguishing' => $isVisual,
                        ':is_searchable' => $isPropSearchable,
                        ':is_system' => $isPropSystem,
                        ':sort_order' => $propSort,
                    ]);
                }
            }

            foreach ($nodesTranslate as $key => $val) {
                if (!array_key_exists($key, $nodesDatamodel)) {
                    $insertTrans->execute([
                        ':trans_key' => $key,
                        ':trans_value' => $val,
                    ]);
                }
            }

            $db->commit();
            self::rebuildCache();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Ensure existing schema entity and property names conform to naming rules.
     */
    public static function normalizeSchemaNames(?PDO $pdo = null): void {
        $db = $pdo ?? self::getPdo();
        // Force capitalization of node names (e.g. Text); and force lowercasing of node properties)
        $types = $db->query("SELECT id, name FROM entity_types")->fetchAll();
        foreach ($types as $t) {
            $cleanName = ucfirst(preg_replace('/\s+/', '', (string)$t['name']));
            if ($cleanName !== $t['name'] && $cleanName !== '') {
                $stmt = $db->prepare("UPDATE entity_types SET name = :clean WHERE id = :id");
                $stmt->execute([':clean' => $cleanName, ':id' => $t['id']]);
            }
        }

        $props = $db->query("SELECT id, name FROM entity_properties")->fetchAll();
        foreach ($props as $p) {
            $cleanProp = strtolower(preg_replace('/\s+/', '', (string)$p['name']));
            if ($cleanProp !== $p['name'] && $cleanProp !== '') {
                $stmt = $db->prepare("UPDATE entity_properties SET name = :clean WHERE id = :id");
                $stmt->execute([':clean' => $cleanProp, ':id' => $p['id']]);
            }
        }
    }

    /**
     * Load raw configuration data and settings from SQLite database.
     */
    public static function loadFromDatabase(): array {
        $db = self::getPdo();
        self::initSchema($db);
        self::normalizeSchemaNames($db);

        $typesStmt = $db->query("
            SELECT id, name, plural_label, color, is_searchable, is_system, sort_order
            FROM entity_types
            ORDER BY sort_order ASC, name ASC
        ");
        $types = $typesStmt->fetchAll();

        $propsStmt = $db->query("
            SELECT id, entity_type_id, name, display_name, data_type, is_unique, is_visual_distinguishing, is_searchable, is_system, sort_order
            FROM entity_properties
            ORDER BY sort_order ASC, name ASC
        ");
        $allProps = $propsStmt->fetchAll();

        $propsByTypeId = [];
        foreach ($allProps as $p) {
            $propsByTypeId[(int)$p['entity_type_id']][] = $p;
        }

        $nodesDatamodel = [];
        $matchOnNodes = [];
        $nodesTranslate = [];
        $nodes = [];
        $primaryKeys = [];

        foreach ($types as $t) {
            // Force capitalization of node names (e.g. Text); and force lowercasing of node properties)
            $tName = ucfirst(preg_replace('/\s+/', '', (string)$t['name']));
            $typeId = (int)$t['id'];

            if (!empty($t['plural_label'])) {
                $nodesTranslate[$tName] = $t['plural_label'];
            }

            if (!empty($t['is_searchable'])) {
                $matchOnNodes[$tName] = $t['color'] ?? 'rgba(39, 123, 245, 0.6)';
            }

            $typeProperties = [];
            $typePropertyKeys = [];
            $primaryKeyFound = false;

            if (isset($propsByTypeId[$typeId])) {
                foreach ($propsByTypeId[$typeId] as $prop) {
                    // Force capitalization of node names (e.g. Text); and force lowercasing of node properties)
                    $pName = strtolower(preg_replace('/\s+/', '', (string)$prop['name']));
                    $typePropertyKeys[] = $pName;

                    $isUnique = (bool)$prop['is_unique'];
                    $isVisual = (bool)$prop['is_visual_distinguishing'];
                    $isSearch = (bool)$prop['is_searchable'];

                    $typeProperties[$pName] = [
                        $prop['display_name'],
                        $prop['data_type'],
                        $isUnique,
                        $isVisual,
                        $isSearch,
                    ];

                    if ($isUnique && $primaryKeyFound === false) {
                        $primaryKeyFound = $pName;
                    }
                }
            }

            $nodesDatamodel[$tName] = $typeProperties;
            $nodes[$tName] = $typePropertyKeys;
            $primaryKeys[$tName] = $primaryKeyFound;
        }

        $transStmt = $db->query("SELECT trans_key, trans_value FROM extra_translations");
        $extraTrans = $transStmt->fetchAll();
        foreach ($extraTrans as $et) {
            $nodesTranslate[$et['trans_key']] = $et['trans_value'];
        }

        $settings = self::getAllSettings();
        $isInstalled = self::isInstalled();

        return [
            'nodesDatamodel' => $nodesDatamodel,
            'matchOnNodes' => $matchOnNodes,
            'nodes_translate' => $nodesTranslate,
            'nodes' => $nodes,
            'primaryKeys' => $primaryKeys,
            'settings' => $settings,
            'is_installed' => $isInstalled,
        ];
    }

    /**
     * Atomically rebuild the compiled PHP cache file.
     */
    public static function rebuildCache(): array {
        $data = self::loadFromDatabase();

        $cacheFile = self::getCachePath();
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $exported = var_export($data, true);
        $phpCode = "<?php\n// Auto-generated configuration cache.\n// Do not edit directly. Rebuild via administration interface or ConfigModelManager::rebuildCache().\nreturn " . $exported . ";\n";

        $tempFile = $cacheDir . '/cache_' . bin2hex(random_bytes(6)) . '.tmp';
        file_put_contents($tempFile, $phpCode, LOCK_EX);
        rename($tempFile, $cacheFile);

        return $data;
    }

    /**
     * Get compiled model from cache, or build cache if not present.
     */
    public static function getModel(): array {
        $cacheFile = self::getCachePath();
        if (file_exists($cacheFile)) {
            $cached = include $cacheFile;
            if (is_array($cached) && isset($cached['nodesDatamodel']) && isset($cached['settings'])) {
                return $cached;
            }
        }
        return self::rebuildCache();
    }

    /**
     * Fetch all entities and their nested properties for administration.
     */
    public static function getAllEntitiesWithProperties(): array {
        $db = self::getPdo();
        self::initSchema($db);

        $typesStmt = $db->query("
            SELECT id, name, plural_label, color, is_searchable, is_system, sort_order, created_at, updated_at
            FROM entity_types
            ORDER BY sort_order ASC, name ASC
        ");
        $types = $typesStmt->fetchAll();

        $propsStmt = $db->query("
            SELECT id, entity_type_id, name, display_name, data_type, is_unique, is_visual_distinguishing, is_searchable, is_system, sort_order
            FROM entity_properties
            ORDER BY sort_order ASC, name ASC
        ");
        $allProps = $propsStmt->fetchAll();

        $propsByTypeId = [];
        foreach ($allProps as $p) {
            $propsByTypeId[(int)$p['entity_type_id']][] = $p;
        }

        foreach ($types as &$t) {
            $tid = (int)$t['id'];
            $t['properties'] = $propsByTypeId[$tid] ?? [];
        }

        return $types;
    }

    /**
     * Specification of mandated core architecture entities and their required properties.
     */
    public static function getMandatedCoreEntities(): array {
        return [
            'Text' => [
                'name' => 'Text',
                'plural_label' => 'Texts',
                'color' => 'rgba(28, 200, 28, 0.6)',
                'is_searchable' => 1,
                'description' => 'Mandatory document container holding textual content for reading, text mining, and annotations.',
                'required_properties' => [
                    'text' => [
                        'name' => 'text',
                        'display_name' => 'Text content',
                        'data_type' => 'string',
                        'is_unique' => 0,
                        'is_visual_distinguishing' => 0,
                        'is_searchable' => 1,
                        'is_system' => 1,
                        'description' => 'Document text payload referenced by TEXNODETEXT and reader interfaces.'
                    ]
                ]
            ],
            'Annotation' => [
                'name' => 'Annotation',
                'plural_label' => 'Annotations',
                'color' => 'rgba(200, 28, 28, 0.6)',
                'is_searchable' => 1,
                'description' => 'Mandatory annotation node connecting character ranges in texts to knowledge graph entities.',
                'required_properties' => [
                    'starts' => [
                        'name' => 'starts',
                        'display_name' => 'Start offset',
                        'data_type' => 'int',
                        'is_unique' => 0,
                        'is_visual_distinguishing' => 0,
                        'is_searchable' => 0,
                        'is_system' => 1,
                        'description' => 'Character index where text selection begins (ANNOSTART).'
                    ],
                    'stops' => [
                        'name' => 'stops',
                        'display_name' => 'End offset',
                        'data_type' => 'int',
                        'is_unique' => 0,
                        'is_visual_distinguishing' => 0,
                        'is_searchable' => 0,
                        'is_system' => 1,
                        'description' => 'Character index where text selection ends (ANNOSTOP).'
                    ]
                ]
            ],
            'Variant' => [
                'name' => 'Variant',
                'plural_label' => 'Spelling variants',
                'color' => 'rgba(100, 100, 100, 0.6)',
                'is_searchable' => 0,
                'description' => 'Mandatory spelling variant node storing alternative names and surface forms linked via same_as relations.',
                'required_properties' => [
                    'variant' => [
                        'name' => 'variant',
                        'display_name' => 'Variant spelling',
                        'data_type' => 'string',
                        'is_unique' => 0,
                        'is_visual_distinguishing' => 1,
                        'is_searchable' => 1,
                        'is_system' => 1,
                        'description' => 'Surface form string queried by variant linking routines (n.variant).'
                    ]
                ]
            ]
        ];
    }

    /**
     * Check if all mandated core entities and their required properties are defined.
     */
    public static function getCoreEntitiesStatus(): array {
        $db = self::getPdo();
        self::initSchema($db);

        $typesStmt = $db->query("SELECT id, name, plural_label, color, is_system FROM entity_types");
        $types = $typesStmt->fetchAll();
        $typesByName = [];
        foreach ($types as $t) {
            $typesByName[$t['name']] = $t;
        }

        $propsStmt = $db->query("SELECT entity_type_id, name, data_type, is_system FROM entity_properties");
        $props = $propsStmt->fetchAll();
        $propsByTypeId = [];
        foreach ($props as $p) {
            $propsByTypeId[(int)$p['entity_type_id']][] = $p;
        }

        $specs = self::getMandatedCoreEntities();
        $details = [];
        $allDefined = true;
        $missingCoreTypes = [];
        $incompleteCoreTypes = [];

        foreach ($specs as $specName => $spec) {
            $typeExists = isset($typesByName[$specName]);
            $typeId = $typeExists ? (int)$typesByName[$specName]['id'] : null;

            $existingProps = [];
            if ($typeExists && isset($propsByTypeId[$typeId])) {
                foreach ($propsByTypeId[$typeId] as $ep) {
                    $existingProps[$ep['name']] = $ep['data_type'];
                }
            }

            $missingProps = [];
            foreach ($spec['required_properties'] as $reqPropName => $reqPropDef) {
                if (!isset($existingProps[$reqPropName]) || $existingProps[$reqPropName] !== $reqPropDef['data_type']) {
                    $missingProps[$reqPropName] = $reqPropDef;
                }
            }

            $isComplete = $typeExists && empty($missingProps);
            if (!$typeExists) {
                $allDefined = false;
                $missingCoreTypes[] = $specName;
            } elseif (!empty($missingProps)) {
                $allDefined = false;
                $incompleteCoreTypes[] = $specName;
            }

            $details[$specName] = [
                'name' => $specName,
                'exists' => $typeExists,
                'id' => $typeId,
                'is_complete' => $isComplete,
                'existing_properties' => array_keys($existingProps),
                'missing_properties' => $missingProps,
                'required_properties' => $spec['required_properties'],
                'spec' => $spec,
            ];
        }

        return [
            'all_core_defined' => $allDefined,
            'can_create_custom_entities' => $allDefined,
            'missing_core_types' => $missingCoreTypes,
            'incomplete_core_types' => $incompleteCoreTypes,
            'details' => $details,
        ];
    }

    /**
     * Get entity types defined beyond the mandated core architecture components.
     *
     * @return array List of non-core entity type names.
     */
    public static function getNonCoreEntityTypes(): array {
        $db = self::getPdo();
        self::initSchema($db);
        $mandated = array_keys(self::getMandatedCoreEntities());
        $stmt = $db->query("SELECT name FROM entity_types WHERE is_system = 0 ORDER BY name ASC");
        $types = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        return array_values(array_filter($types, function($name) use ($mandated) {
            return !in_array($name, $mandated, true);
        }));
    }

    /**
     * Check whether any non-core entity types are currently defined in the schema.
     *
     * @return bool True if at least one custom entity type exists, false otherwise.
     */
    public static function hasNonCoreEntityTypes(): bool {
        return count(self::getNonCoreEntityTypes()) > 0;
    }

    /**
     * Scaffold a mandated core entity type with all its required properties.
     */
    public static function scaffoldCoreEntity(string $typeName): int {
        // Force capitalization of node names (e.g. Text); and force lowercasing of node properties)
        $typeName = ucfirst(preg_replace('/\s+/', '', trim($typeName)));
        $specs = self::getMandatedCoreEntities();
        if (!isset($specs[$typeName])) {
            throw new InvalidArgumentException("Unrecognized core entity type: {$typeName}");
        }
        $spec = $specs[$typeName];
        $db = self::getPdo();
        self::initSchema($db);

        $db->beginTransaction();
        try {
            // Check if entity type already exists.
            $stmt = $db->prepare("SELECT id FROM entity_types WHERE name = :name");
            $stmt->execute([':name' => $typeName]);
            $existing = $stmt->fetch();

            if ($existing) {
                $entityId = (int)$existing['id'];
                $db->prepare("UPDATE entity_types SET is_system = 1 WHERE id = :id")->execute([':id' => $entityId]);
            } else {
                $insertType = $db->prepare("
                    INSERT INTO entity_types (name, plural_label, color, is_searchable, is_system, sort_order)
                    VALUES (:name, :plural_label, :color, :is_searchable, 1, :sort_order)
                ");
                $insertType->execute([
                    ':name' => $typeName,
                    ':plural_label' => $spec['plural_label'],
                    ':color' => $spec['color'],
                    ':is_searchable' => $spec['is_searchable'] ?? 1,
                    ':sort_order' => $typeName === 'Text' ? 10 : ($typeName === 'Annotation' ? 20 : 30),
                ]);
                $entityId = (int)$db->lastInsertId();
            }

            // Insert missing required properties.
            $propCheck = $db->prepare("SELECT name FROM entity_properties WHERE entity_type_id = :tid");
            $propCheck->execute([':tid' => $entityId]);
            $existingProps = array_column($propCheck->fetchAll(), 'name');

            $insertProp = $db->prepare("
                INSERT INTO entity_properties 
                (entity_type_id, name, display_name, data_type, is_unique, is_visual_distinguishing, is_searchable, is_system, sort_order)
                VALUES (:entity_type_id, :name, :display_name, :data_type, :is_unique, :is_visual_distinguishing, :is_searchable, 1, :sort_order)
            ");

            $propSort = 10;
            foreach ($spec['required_properties'] as $pName => $pDef) {
                if (!in_array($pName, $existingProps, true)) {
                    $insertProp->execute([
                        ':entity_type_id' => $entityId,
                        ':name' => $pDef['name'],
                        ':display_name' => $pDef['display_name'],
                        ':data_type' => $pDef['data_type'],
                        ':is_unique' => $pDef['is_unique'] ?? 0,
                        ':is_visual_distinguishing' => $pDef['is_visual_distinguishing'] ?? 0,
                        ':is_searchable' => $pDef['is_searchable'] ?? 1,
                        ':sort_order' => $propSort,
                    ]);
                    $propSort += 10;
                }
            }

            $db->commit();
            self::rebuildCache();
            return $entityId;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Scaffold all three mandated core entities.
     */
    public static function scaffoldAllCoreEntities(): array {
        $created = [];
        foreach (array_keys(self::getMandatedCoreEntities()) as $coreType) {
            $created[$coreType] = self::scaffoldCoreEntity($coreType);
        }
        return $created;
    }

    /**
     * Reset all entity types and properties to the clean initial empty state.
     */
    public static function resetEntityModel(): void {
        $db = self::getPdo();
        self::initSchema($db);
        $db->exec("DELETE FROM entity_properties; DELETE FROM entity_types; DELETE FROM extra_translations;");
        self::rebuildCache();
    }

    /**
     * Create or update an entity type.
     */
    public static function saveEntityType(array $data): int {
        $db = self::getPdo();
        $id = !empty($data['id']) ? (int)$data['id'] : null;
        // Force capitalization of node names (e.g. Text); and force lowercasing of node properties)
        $name = ucfirst(preg_replace('/\s+/', '', trim($data['name'] ?? '')));
        $plural = trim($data['plural_label'] ?? $name);
        $color = trim($data['color'] ?? 'rgba(39, 123, 245, 0.6)');
        $isSearchable = !empty($data['is_searchable']) ? 1 : 0;
        $sortOrder = isset($data['sort_order']) ? (int)$data['sort_order'] : 100;

        if ($name === '') {
            throw new InvalidArgumentException('Entity name cannot be empty.');
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new InvalidArgumentException('Entity name must contain only alphanumeric characters and underscores, and cannot contain spaces.');
        }

        $coreSpecs = self::getMandatedCoreEntities();
        $isCoreType = isset($coreSpecs[$name]);

        if ($id) {
            $existing = $db->prepare("SELECT id, name, is_system FROM entity_types WHERE id = :id");
            $existing->execute([':id' => $id]);
            $current = $existing->fetch();
            if (!$current) {
                throw new RuntimeException('Entity type not found.');
            }

            if (!empty($current['is_system']) && $current['name'] !== $name) {
                throw new RuntimeException('System entity names cannot be renamed.');
            }

            $update = $db->prepare("
                UPDATE entity_types
                SET name = :name, plural_label = :plural_label, color = :color,
                    is_searchable = :is_searchable, is_system = :is_system, sort_order = :sort_order, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $update->execute([
                ':name' => $name,
                ':plural_label' => $plural,
                ':color' => $color,
                ':is_searchable' => $isSearchable,
                ':is_system' => $isCoreType ? 1 : ($current['is_system'] ?? 0),
                ':sort_order' => $sortOrder,
                ':id' => $id,
            ]);
            self::rebuildCache();
            return $id;
        } else {
            // Lock creating custom entity types until all three core types are defined.
            $status = self::getCoreEntitiesStatus();
            if (!$status['all_core_defined'] && !$isCoreType) {
                $missingList = implode(', ', array_merge($status['missing_core_types'], $status['incomplete_core_types']));
                throw new RuntimeException(
                    "Creating custom entity types is locked until all three core entity types (Text, Annotation, and Variant) and their required properties are defined. Remaining: {$missingList}."
                );
            }

            $insert = $db->prepare("
                INSERT INTO entity_types (name, plural_label, color, is_searchable, is_system, sort_order)
                VALUES (:name, :plural_label, :color, :is_searchable, :is_system, :sort_order)
            ");
            $insert->execute([
                ':name' => $name,
                ':plural_label' => $plural,
                ':color' => $color,
                ':is_searchable' => $isSearchable,
                ':is_system' => $isCoreType ? 1 : 0,
                ':sort_order' => $sortOrder,
            ]);
            $newId = (int)$db->lastInsertId();
            self::rebuildCache();
            return $newId;
        }
    }

    /**
     * Delete an entity type and all its properties.
     */
    public static function deleteEntityType(int $id): bool {
        $db = self::getPdo();
        $stmt = $db->prepare("SELECT id, name, is_system FROM entity_types WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $entity = $stmt->fetch();

        if (!$entity) {
            throw new RuntimeException('Entity type not found.');
        }

        $coreSpecs = self::getMandatedCoreEntities();
        if (isset($coreSpecs[$entity['name']]) || !empty($entity['is_system'])) {
            throw new RuntimeException("Core architecture entity type '{$entity['name']}' is mandated and cannot be deleted.");
        }

        $delete = $db->prepare("DELETE FROM entity_types WHERE id = :id");
        $delete->execute([':id' => $id]);
        self::rebuildCache();
        return true;
    }

    /**
     * Create or update a property for an entity type.
     */
    public static function saveProperty(array $data): int {
        $db = self::getPdo();
        $id = !empty($data['id']) ? (int)$data['id'] : null;
        $entityTypeId = (int)($data['entity_type_id'] ?? 0);
        // Force capitalization of node names (e.g. Text); and force lowercasing of node properties)
        $name = strtolower(preg_replace('/\s+/', '', trim($data['name'] ?? '')));
        $displayName = trim($data['display_name'] ?? $name);
        $dataType = trim($data['data_type'] ?? 'string');
        $isUnique = !empty($data['is_unique']) ? 1 : 0;
        $isVisual = !empty($data['is_visual_distinguishing']) ? 1 : 0;
        $isSearchable = !empty($data['is_searchable']) ? 1 : 0;
        $sortOrder = isset($data['sort_order']) ? (int)$data['sort_order'] : 100;

        if ($entityTypeId <= 0) {
            throw new InvalidArgumentException('Invalid entity type ID.');
        }

        if ($name === '') {
            throw new InvalidArgumentException('Property name cannot be empty.');
        }

        if (!preg_match('/^[a-z0-9_]+$/', $name)) {
            throw new InvalidArgumentException('Property name must be lowercase and contain only alphanumeric characters and underscores, without spaces.');
        }

        $allowedTypes = ['string', 'int', 'float', 'bool', 'uri', 'wikidata'];
        if (!in_array($dataType, $allowedTypes, true)) {
            throw new InvalidArgumentException('Invalid data type: ' . $dataType);
        }

        // An entity type cannot have more than one property with the wikidata type.
        if ($dataType === 'wikidata') {
            $existingWdStmt = $db->prepare("SELECT id, name FROM entity_properties WHERE entity_type_id = :tid AND data_type = 'wikidata' AND id != :current_id");
            $existingWdStmt->execute([
                ':tid' => $entityTypeId,
                ':current_id' => $id ?? -1,
            ]);
            $existingWd = $existingWdStmt->fetch();
            if ($existingWd) {
                throw new InvalidArgumentException("An entity type cannot have more than one property with the wikidata type (already assigned to '{$existingWd['name']}').");
            }
        }

        // Check if property belongs to a core entity type and has a mandated specification.
        $ownerStmt = $db->prepare("SELECT name FROM entity_types WHERE id = :tid");
        $ownerStmt->execute([':tid' => $entityTypeId]);
        $ownerType = $ownerStmt->fetch();
        $ownerName = $ownerType ? $ownerType['name'] : '';
        $coreSpecs = self::getMandatedCoreEntities();
        $isMandatedProperty = isset($coreSpecs[$ownerName]['required_properties'][$name]);

        if ($isMandatedProperty) {
            $requiredDef = $coreSpecs[$ownerName]['required_properties'][$name];
            if ($dataType !== $requiredDef['data_type']) {
                throw new InvalidArgumentException(
                    "Mandated property '{$name}' on {$ownerName} must use data type '{$requiredDef['data_type']}' (got '{$dataType}')."
                );
            }
        }

        if ($id) {
            $existing = $db->prepare("SELECT id, name, is_system FROM entity_properties WHERE id = :id");
            $existing->execute([':id' => $id]);
            $current = $existing->fetch();
            if (!$current) {
                throw new RuntimeException('Property not found.');
            }

            if (!empty($current['is_system']) && $current['name'] !== $name) {
                throw new RuntimeException('System properties cannot be renamed.');
            }

            $update = $db->prepare("
                UPDATE entity_properties
                SET name = :name, display_name = :display_name, data_type = :data_type,
                    is_unique = :is_unique, is_visual_distinguishing = :is_visual,
                    is_searchable = :is_searchable, is_system = :is_system, sort_order = :sort_order
                WHERE id = :id
            ");
            $update->execute([
                ':name' => $name,
                ':display_name' => $displayName,
                ':data_type' => $dataType,
                ':is_unique' => $isUnique,
                ':is_visual' => $isVisual,
                ':is_searchable' => $isSearchable,
                ':is_system' => $isMandatedProperty ? 1 : ($current['is_system'] ?? 0),
                ':sort_order' => $sortOrder,
                ':id' => $id,
            ]);
            self::rebuildCache();
            return $id;
        } else {
            $insert = $db->prepare("
                INSERT INTO entity_properties 
                (entity_type_id, name, display_name, data_type, is_unique, is_visual_distinguishing, is_searchable, is_system, sort_order)
                VALUES (:entity_type_id, :name, :display_name, :data_type, :is_unique, :is_visual, :is_searchable, :is_system, :sort_order)
            ");
            $insert->execute([
                ':entity_type_id' => $entityTypeId,
                ':name' => $name,
                ':display_name' => $displayName,
                ':data_type' => $dataType,
                ':is_unique' => $isUnique,
                ':is_visual' => $isVisual,
                ':is_searchable' => $isSearchable,
                ':is_system' => $isMandatedProperty ? 1 : 0,
                ':sort_order' => $sortOrder,
            ]);
            $newId = (int)$db->lastInsertId();
            self::rebuildCache();
            return $newId;
        }
    }

    /**
     * Delete a property from an entity.
     */
    public static function deleteProperty(int $id): bool {
        $db = self::getPdo();
        $stmt = $db->prepare("SELECT id, entity_type_id, name, is_system FROM entity_properties WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $prop = $stmt->fetch();

        if (!$prop) {
            throw new RuntimeException('Property not found.');
        }

        $ownerStmt = $db->prepare("SELECT name FROM entity_types WHERE id = :tid");
        $ownerStmt->execute([':tid' => $prop['entity_type_id']]);
        $ownerType = $ownerStmt->fetch();
        $ownerName = $ownerType ? $ownerType['name'] : '';
        $coreSpecs = self::getMandatedCoreEntities();

        if (isset($coreSpecs[$ownerName]['required_properties'][$prop['name']]) || !empty($prop['is_system'])) {
            throw new RuntimeException("Mandated core property '{$prop['name']}' is required for {$ownerName} and cannot be deleted.");
        }

        $delete = $db->prepare("DELETE FROM entity_properties WHERE id = :id");
        $delete->execute([':id' => $id]);
        self::rebuildCache();
        return true;
    }
}
