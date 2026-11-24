<?php
/**
 * Retrim an automatic annotation suggestion on a text node.
 * Requires user edit rights.
 */

include_once(dirname(__DIR__) . "/config/config.inc.php");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

include_once(ROOT_DIR . '/includes/client.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/latte_queue_manager.inc.php');

$user = new User($client);
$userId = $user->checkSession();
if (!$userId || $user->hasEditRights($user->myRole) < 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Insufficient permissions to retrim automatic suggestions.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$neoId = isset($input['text_id']) ? (int)$input['text_id'] : (isset($_POST['text_id']) ? (int)$_POST['text_id'] : 0);
$suggestionId = isset($input['suggestion_id']) ? trim($input['suggestion_id']) : (isset($_POST['suggestion_id']) ? trim($_POST['suggestion_id']) : '');
$newStart = isset($input['new_start']) ? (int)$input['new_start'] : (isset($_POST['new_start']) ? (int)$_POST['new_start'] : -1);
$newStop = isset($input['new_stop']) ? (int)$input['new_stop'] : (isset($_POST['new_stop']) ? (int)$_POST['new_stop'] : -1);

if ($neoId <= 0 || $suggestionId === '' || $newStart < 0 || $newStop < $newStart) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid retrim parameters provided.']);
    exit();
}

try {
    $updated = LatteQueueManager::retrimSuggestion($client, $neoId, $suggestionId, $newStart, $newStop);
    if ($updated) {
        echo json_encode([
            'success' => true,
            'message' => 'Automatic suggestion retrimmed successfully.',
            'suggestion' => $updated
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Suggestion not found or could not be retrimmed.']);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while retrimming suggestion: ' . $e->getMessage()]);
}
