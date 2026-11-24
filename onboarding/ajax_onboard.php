<?php
header('Content-Type: application/json; charset=utf-8');

define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/includes/config_model_manager.inc.php';

// If system is already installed, reject all onboarding requests immediately.
if (ConfigModelManager::isInstalled()) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Onboarding is closed because the base configuration is already active.',
    ]);
    exit();
}

// Ensure requester is authorized via setup token or localhost.
if (!ConfigModelManager::isSetupAuthorized()) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized setup access: valid setup token required.',
    ]);
    exit();
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'test_db':
            $host = trim($_POST['db_host'] ?? 'localhost');
            $port = (int)($_POST['db_port'] ?? 7700);
            $user = trim($_POST['db_user'] ?? 'neo4j');
            $pass = trim($_POST['db_password'] ?? '');
            $dbname = trim($_POST['db_name'] ?? 'neo4j');

            if (file_exists(ROOT_DIR . '/vendor/autoload.php')) {
                require_once ROOT_DIR . '/vendor/autoload.php';
            }

            if (!class_exists('Laudis\Neo4j\ClientBuilder')) {
                throw new RuntimeException('Neo4j client library is not available.');
            }

            $auth = \Laudis\Neo4j\Authentication\Authenticate::basic($user, $pass);
            $client = \Laudis\Neo4j\ClientBuilder::create()
                ->withDriver('bolt', "bolt://{$host}:{$port}?database={$dbname}", $auth)
                ->withDefaultDriver('bolt')
                ->build();

            $result = $client->run("RETURN 1 AS test");
            if (isset($result[0]['test']) && (int)$result[0]['test'] === 1) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Connection to Neo4j verified successfully.',
                ]);
            } else {
                throw new RuntimeException('Unexpected response from Neo4j.');
            }
            break;

        case 'test_latte':
            $endpoint = trim($_POST['latte_connector'] ?? 'localhost:8000');
            $url = (strpos($endpoint, 'http://') === 0 || strpos($endpoint, 'https://') === 0) ? $endpoint : "http://{$endpoint}";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err && $httpCode === 0) {
                throw new RuntimeException("Could not connect to LATTE endpoint at {$endpoint}: {$err}");
            }

            echo json_encode([
                'status' => 'success',
                'message' => "LATTE endpoint responded (HTTP {$httpCode}).",
            ]);
            break;

        case 'test_smtp':
            $server = trim($_POST['smtp_server'] ?? '');
            $port = (int)($_POST['smtp_port'] ?? 25);

            if ($server === '') {
                throw new InvalidArgumentException('Please provide an SMTP server address to test.');
            }

            $fp = @fsockopen($server, $port, $errno, $errstr, 3);
            if (!$fp) {
                throw new RuntimeException("Could not connect to SMTP server {$server}:{$port} ($errstr)");
            }
            fclose($fp);

            echo json_encode([
                'status' => 'success',
                'message' => "Successfully connected to SMTP server at {$server}:{$port}.",
            ]);
            break;

        case 'complete_onboarding':
            // Validate required inputs.
            $projectName = trim($_POST['project_name'] ?? 'HIPE data');
            $baseUrl = trim($_POST['base_url'] ?? '');
            $dbHost = trim($_POST['db_host'] ?? 'localhost');
            $dbPort = (int)($_POST['db_port'] ?? 7700);
            $dbName = trim($_POST['db_name'] ?? 'finaldocwriteup.db');
            $dbUser = trim($_POST['db_user'] ?? 'neo4j');
            $dbPass = trim($_POST['db_password'] ?? '');

            $useLatte = !empty($_POST['use_latte']) ? '1' : '0';
            $latteConnector = trim($_POST['latte_connector'] ?? 'latte_connector:8000');
            $languageEngine = trim($_POST['language_engine'] ?? 'langid');

            $mailProtocol = trim($_POST['mail_protocol'] ?? 'SMTP');
            $originEmail = trim($_POST['origin_email'] ?? 'siteadmin@example.org');
            $smtpServer = trim($_POST['smtp_server'] ?? '');
            $smtpPort = (int)($_POST['smtp_port'] ?? 25);
            $smtpUser = trim($_POST['smtp_user'] ?? '');
            $smtpPass = trim($_POST['smtp_password'] ?? '');
            $smtpSelfSigned = !empty($_POST['smtp_self_signed']) ? '1' : '0';

            $textsPublic = !empty($_POST['texts_public']) ? '1' : '0';
            $entitiesPublic = !empty($_POST['entities_public']) ? '1' : '0';
            $regPolicy = (int)($_POST['registration_policy'] ?? 1);

            $adminUser = trim($_POST['admin_username'] ?? '');
            $adminEmail = trim($_POST['admin_email'] ?? '');
            $adminPass = $_POST['admin_password'] ?? '';
            $adminPassRepeat = $_POST['admin_password_repeat'] ?? '';

            if ($projectName === '') {
                throw new InvalidArgumentException('Project name is required.');
            }
            if ($adminUser === '' || strlen($adminUser) < 3) {
                throw new InvalidArgumentException('Admin username must be at least 3 characters.');
            }
            if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Valid admin email address is required.');
            }
            if (strlen($adminPass) < 6) {
                throw new InvalidArgumentException('Admin password must be at least 6 characters.');
            }
            if ($adminPass !== $adminPassRepeat) {
                throw new InvalidArgumentException('Passwords do not match.');
            }

            // Create admin user in users.sqlite.
            ConfigModelManager::createAdminUser($adminUser, $adminEmail, $adminPass);

            // Save system settings in schema_config.sqlite.
            $settings = [
                'project_name' => $projectName,
                'base_url' => $baseUrl,
                'db_host' => $dbHost,
                'db_port' => (string)$dbPort,
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_password' => $dbPass,
                'use_latte' => $useLatte,
                'latte_connector' => $latteConnector,
                'language_engine' => $languageEngine,
                'mail_protocol' => $mailProtocol,
                'origin_email' => $originEmail,
                'smtp_server' => $smtpServer,
                'smtp_port' => (string)$smtpPort,
                'smtp_user' => $smtpUser,
                'smtp_password' => $smtpPass,
                'smtp_self_signed' => $smtpSelfSigned,
                'texts_public' => $textsPublic,
                'entities_public' => $entitiesPublic,
                'registration_policy' => (string)$regPolicy,
                'is_installed' => '1',
            ];
            ConfigModelManager::saveSettings($settings, false);

            // Ensure database schema tables exist. Entity types remain empty upon initialization.
            $db = ConfigModelManager::getPdo();
            ConfigModelManager::initSchema($db);

            // Ensure config/config.inc.php exists on disk.
            ConfigModelManager::ensureConfigFile();

            // Destroy one-time setup secret.
            ConfigModelManager::consumeSetupSecret();

            // Rebuild cache file.
            ConfigModelManager::rebuildCache();

            echo json_encode([
                'status' => 'success',
                'message' => 'Onboarding completed successfully. The base configuration is now active.',
                'redirect' => (defined('BASE_URL') ? BASE_URL : '') . '/user/login.php',
            ]);
            break;

        default:
            throw new InvalidArgumentException('Unknown onboarding action.');
    }
} catch (\Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
