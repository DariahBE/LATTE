<?php
/* System environment: base root directory */
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}

/* Debug mode: if true, detailed error messages and stack traces are displayed. */
$debug = false;
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

/* Default timezone configuration */
date_default_timezone_set($settings['timezone'] ?? 'Europe/Brussels');

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
