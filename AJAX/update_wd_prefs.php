<?php
/**
 * AJAX endpoint for saving per-entity Wikidata preferences.
 */

header('Content-Type: application/json; charset=utf-8');

if (!defined('ROOT_DIR')) {
    include_once(dirname(__DIR__) . '/config/config.inc.php');
}
include_once(ROOT_DIR . '/includes/client.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');
include_once(ROOT_DIR . '/includes/wikidata_user_prefs.inc.php');

$user = new User($client);
if (!$user->checkSession()) {
    http_response_code(401);
    echo json_encode(array('success' => false, 'message' => 'Unauthorized session. Please log in again.'));
    exit();
}

$tokenManager = new CsrfTokenManager();
$submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (!$tokenManager->checkToken($submittedToken)) {
    http_response_code(403);
    echo json_encode(array('success' => false, 'message' => 'Invalid or expired CSRF token.'));
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!is_array($data)) {
    $data = $_POST;
}

$action = $data['action'] ?? 'save_entity_preferences';
$preferencesManager = new Wikidata_user($client);
$userId = (int)$_SESSION['userid'];

try {
    if ($action === 'save_entity_preferences') {
        $entityType = trim($data['entity_type'] ?? '');
        $properties = $data['properties'] ?? null;

        if ($entityType === '' || !is_array($properties)) {
            throw new Exception('Invalid entity type or property list provided.');
        }

        $allPreferences = $preferencesManager->getUserPreferences($userId);
        $cleanProps = array();

        foreach ($properties as $pCode => $pDef) {
            $code = strtoupper(trim($pCode));
            if (!preg_match('/^P[0-9]+$/', $code)) {
                continue;
            }

            $label = trim($pDef['label'] ?? $code);
            $type = in_array($pDef['type'] ?? '', array('str', 'uri', 'geo', 'img'), true)
                ? $pDef['type']
                : 'str';
            $enabled = !empty($pDef['enabled']);
            $showMissing = !empty($pDef['show_missing']);
            $temporalMode = in_array($pDef['temporal_mode'] ?? '', array('latest', 'history'), true)
                ? $pDef['temporal_mode']
                : 'latest';

            $cleanProps[$code] = array(
                'label' => $label,
                'type' => $type,
                'enabled' => $enabled,
                'show_missing' => $showMissing,
                'temporal_mode' => $temporalMode,
            );
        }

        $allPreferences[$entityType] = $cleanProps;
        $preferencesManager->saveUserPreferences($userId, $allPreferences);

        echo json_encode(array(
            'success' => true,
            'message' => 'Preferences for ' . $entityType . ' saved successfully.',
            'preferences' => $allPreferences,
        ));
        exit();
    }

    if ($action === 'add_property') {
        $entityType = trim($data['entity_type'] ?? '');
        $code = strtoupper(trim($data['property_code'] ?? ''));
        $label = trim($data['label'] ?? '');
        $type = in_array($data['type'] ?? '', array('str', 'uri', 'geo', 'img'), true)
            ? $data['type']
            : 'str';
        $showMissing = !empty($data['show_missing']);
        $temporalMode = in_array($data['temporal_mode'] ?? '', array('latest', 'history'), true)
            ? $data['temporal_mode']
            : 'latest';

        if ($entityType === '' || !preg_match('/^P[0-9]+$/', $code)) {
            throw new Exception('Invalid property identifier. Must start with P followed by digits.');
        }

        $allPreferences = $preferencesManager->getUserPreferences($userId);
        if (!isset($allPreferences[$entityType]) || !is_array($allPreferences[$entityType])) {
            $allPreferences[$entityType] = array();
        }

        $allPreferences[$entityType][$code] = array(
            'label' => $label !== '' ? $label : $code,
            'type' => $type,
            'enabled' => true,
            'show_missing' => $showMissing,
            'temporal_mode' => $temporalMode,
        );

        $preferencesManager->saveUserPreferences($userId, $allPreferences);

        echo json_encode(array(
            'success' => true,
            'message' => 'Property ' . $code . ' added to ' . $entityType . '.',
            'preferences' => $allPreferences,
        ));
        exit();
    }

    if ($action === 'remove_property') {
        $entityType = trim($data['entity_type'] ?? '');
        $code = strtoupper(trim($data['property_code'] ?? ''));

        if ($entityType === '' || !preg_match('/^P[0-9]+$/', $code)) {
            throw new Exception('Invalid property code.');
        }

        $allPreferences = $preferencesManager->getUserPreferences($userId);
        if (isset($allPreferences[$entityType][$code])) {
            unset($allPreferences[$entityType][$code]);
            $preferencesManager->saveUserPreferences($userId, $allPreferences);
        }

        echo json_encode(array(
            'success' => true,
            'message' => 'Property removed.',
            'preferences' => $allPreferences,
        ));
        exit();
    }

    if ($action === 'save_title_preferences') {
        $prioritizeDetected = !empty($data['prioritize_detected_language']);
        $fallbackLanguages = $data['fallback_languages'] ?? array();
        if (!is_array($fallbackLanguages)) {
            $fallbackLanguages = array();
        }

        $preferencesManager->saveTitlePreferences($userId, $prioritizeDetected, $fallbackLanguages);

        echo json_encode(array(
            'success' => true,
            'message' => 'String matching preferences saved successfully.',
            'prioritize_detected_language' => $prioritizeDetected,
            'fallback_languages' => $fallbackLanguages,
        ));
        exit();
    }

    throw new Exception('Unrecognized action.');
} catch (\Throwable $e) {
    http_response_code(400);
    echo json_encode(array('success' => false, 'message' => $e->getMessage()));
    exit();
}
