<?php
/**
 * Clear all automatic annotations for a text.
 * Requires user edit rights.
 * Clears suggestions from the text node without resetting it to a pending state.
 */

include_once(dirname(__DIR__) . "/config/config.inc.php");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

include_once(ROOT_DIR . '/includes/client.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');
include_once(ROOT_DIR . '/includes/latte_queue_manager.inc.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit();
}

$user = new User($client);
$userId = $user->checkSession();
if (!$userId || $user->hasEditRights($user->myRole) < 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Insufficient permissions to clear automatic annotations.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$submittedToken = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
$tokenManager = new CsrfTokenManager();
if (!$tokenManager->checkToken($submittedToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid session token.']);
    exit();
}

$neoId = isset($input['text_id']) ? (int)$input['text_id'] : (isset($_POST['text_id']) ? (int)$_POST['text_id'] : 0);

if ($neoId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid text node ID provided.']);
    exit();
}

try {
    $cleared = LatteQueueManager::clearTextSuggestions($client, $neoId);
    if ($cleared) {
        echo json_encode([
            'success' => true,
            'message' => 'Automatic annotations cleared successfully.',
            'text_id' => $neoId
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to clear automatic annotations.']);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while clearing annotations: ' . $e->getMessage()]);
}
