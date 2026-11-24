<?php
header('Content-Type: application/json; charset=utf-8');

// Base imports.
include_once(dirname(__DIR__, 2) . "/config/config.inc.php");
include_once(ROOT_DIR . "/includes/client.inc.php");
include_once(ROOT_DIR . "/includes/user.inc.php");
include_once(ROOT_DIR . "/includes/csrf.inc.php");
include_once(ROOT_DIR . "/includes/config_model_manager.inc.php");

$user = new User($client);
$userId = $user->checkSession();
if (!$userId) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit();
}

// Only allow administrators.
if (!$user->isAdmin()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden: administrator access required.']);
    exit();
}

// CSRF validation.
$tokenManager = new CsrfTokenManager();
$submittedToken = $_POST['token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!$tokenManager->checkToken($submittedToken)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired CSRF token.']);
    exit();
}

/**
 * Validate and extract settings for a single configuration section.
 */
function buildSectionSettings(string $section, array $post): array {
    switch ($section) {
        case 'project':
            $projectName = trim($post['project_name'] ?? '');
            if ($projectName === '') {
                throw new InvalidArgumentException('Project name cannot be empty.');
            }
            return [
                'project_name' => $projectName,
                'base_url' => trim($post['base_url'] ?? ''),
                'texts_public' => !empty($post['texts_public']) ? '1' : '0',
                'entities_public' => !empty($post['entities_public']) ? '1' : '0',
                'registration_policy' => (string)(int)($post['registration_policy'] ?? 1),
            ];

        case 'database':
            $dbHost = trim($post['db_host'] ?? '');
            if ($dbHost === '') {
                throw new InvalidArgumentException('Database hostname cannot be empty.');
            }
            $dbPort = (int)($post['db_port'] ?? 0);
            if ($dbPort <= 0) {
                throw new InvalidArgumentException('Database Bolt port must be a positive integer.');
            }
            $dbName = trim($post['db_name'] ?? '');
            if ($dbName === '') {
                throw new InvalidArgumentException('Database name cannot be empty.');
            }
            $dbUser = trim($post['db_user'] ?? '');
            if ($dbUser === '') {
                throw new InvalidArgumentException('Database username cannot be empty.');
            }
            $settings = [
                'db_host' => $dbHost,
                'db_port' => (string)$dbPort,
                'db_name' => $dbName,
                'db_user' => $dbUser,
            ];
            if (isset($post['db_password']) && $post['db_password'] !== '') {
                $settings['db_password'] = $post['db_password'];
            }
            return $settings;

        case 'latte':
            $useLatte = !empty($post['use_latte']);
            $latteConnector = trim($post['latte_connector'] ?? '');
            if ($useLatte && $latteConnector === '') {
                throw new InvalidArgumentException('Connector endpoint cannot be empty when LATTE is enabled.');
            }
            $languageEngine = trim($post['language_engine'] ?? 'langid');
            return [
                'use_latte' => $useLatte ? '1' : '0',
                'latte_connector' => $latteConnector !== '' ? $latteConnector : 'latte_connector:8000',
                'language_engine' => $languageEngine !== '' ? $languageEngine : 'langid',
            ];

        case 'email':
            $originEmail = trim($post['origin_email'] ?? '');
            if ($originEmail === '' || !filter_var($originEmail, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Sender address must be a valid email address.');
            }
            $settings = [
                'mail_protocol' => trim($post['mail_protocol'] ?? 'SMTP'),
                'origin_email' => $originEmail,
                'smtp_server' => trim($post['smtp_server'] ?? ''),
                'smtp_port' => (string)(int)($post['smtp_port'] ?? 25),
                'smtp_user' => trim($post['smtp_user'] ?? ''),
                'smtp_self_signed' => !empty($post['smtp_self_signed']) ? '1' : '0',
            ];
            if (isset($post['smtp_password']) && $post['smtp_password'] !== '') {
                $settings['smtp_password'] = $post['smtp_password'];
            }
            return $settings;

        case 'matomo':
            $matomoEnabled = !empty($post['matomo_enabled']);
            $matomoUrl = trim($post['matomo_url'] ?? '');
            $matomoSiteId = (int)($post['matomo_site_id'] ?? 0);
            $matomoJsPath = trim($post['matomo_js_path'] ?? '');
            $matomoPhpPath = trim($post['matomo_php_path'] ?? '');

            if ($matomoUrl !== '' && !preg_match('#^https?://#i', $matomoUrl)) {
                throw new InvalidArgumentException('Matomo base URL must start with http:// or https://.');
            }
            if ($matomoEnabled) {
                if ($matomoUrl === '') {
                    throw new InvalidArgumentException('Matomo base URL is required when Matomo tracking is enabled.');
                }
                if ($matomoSiteId <= 0) {
                    throw new InvalidArgumentException('Matomo site ID must be a positive integer when Matomo tracking is enabled.');
                }
            }

            return [
                'matomo_enabled' => $matomoEnabled ? '1' : '0',
                'matomo_url' => $matomoUrl,
                'matomo_site_id' => $matomoSiteId > 0 ? (string)$matomoSiteId : '',
                'matomo_js_path' => $matomoJsPath !== '' ? $matomoJsPath : 'matomo.js',
                'matomo_php_path' => $matomoPhpPath !== '' ? $matomoPhpPath : 'matomo.php',
                'matomo_exclude_logged_in' => !empty($post['matomo_exclude_logged_in']) ? '1' : '0',
                'matomo_honor_dnt' => !empty($post['matomo_honor_dnt']) ? '1' : '0',
            ];

        default:
            throw new InvalidArgumentException('Unknown settings section.');
    }
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'save_section':
            $section = trim($_POST['section'] ?? '');
            $settings = buildSectionSettings($section, $_POST);
            ConfigModelManager::saveSettings($settings);

            $sectionLabels = [
                'project' => 'Project identification & access policies',
                'database' => 'Neo4j graph database connection',
                'latte' => 'LATTE NLP connector',
                'email' => 'Email and SMTP settings',
                'matomo' => 'Matomo analytics',
            ];
            $label = $sectionLabels[$section] ?? 'Settings';

            echo json_encode([
                'status' => 'success',
                'message' => $label . ' saved and cache rebuilt successfully.',
                'section' => $section,
            ]);
            break;

        case 'save_settings':
            $settings = [];
            foreach (['project', 'database', 'latte', 'email', 'matomo'] as $sec) {
                $settings = array_merge($settings, buildSectionSettings($sec, $_POST));
            }
            ConfigModelManager::saveSettings($settings);

            echo json_encode([
                'status' => 'success',
                'message' => 'System settings updated and cache rebuilt successfully.',
            ]);
            break;

        default:
            throw new InvalidArgumentException('Unknown settings action.');
    }
} catch (\Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
