<?php
/**
 * Remove a single automatic annotation suggestion from a text node.
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
    echo json_encode(['error' => 'Insufficient permissions to remove automatic suggestions.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$neoId = isset($input['text_id']) ? (int)$input['text_id'] : (isset($_POST['text_id']) ? (int)$_POST['text_id'] : 0);

$suggestionIds = [];
if (isset($input['suggestion_ids']) && is_array($input['suggestion_ids'])) {
    $suggestionIds = array_values(array_filter(array_map('trim', $input['suggestion_ids'])));
} elseif (isset($input['suggestion_id']) && trim($input['suggestion_id']) !== '') {
    $suggestionIds = [trim($input['suggestion_id'])];
} elseif (isset($_POST['suggestion_id']) && trim($_POST['suggestion_id']) !== '') {
    $suggestionIds = [trim($_POST['suggestion_id'])];
}

if ($neoId <= 0 || empty($suggestionIds)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid text node ID or suggestion IDs provided.']);
    exit();
}

try {
    $result = LatteQueueManager::removeMultipleSuggestions($client, $neoId, $suggestionIds);
    if ($result['success'] && $result['removed_count'] > 0) {
        echo json_encode([
            'success' => true,
            'message' => $result['removed_count'] . ' automatic suggestion(s) removed successfully.',
            'removed_ids' => $suggestionIds,
            'removed_count' => $result['removed_count']
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Suggestions not found or could not be removed.']);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while removing suggestions: ' . $e->getMessage()]);
}
