<?php
/**
 * AJAX endpoint for entity deduplication discovery, merge preview, and transactional execution.
 */

include_once(dirname(__DIR__) . '/config/config.inc.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

include_once(ROOT_DIR . '/includes/client.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');
include_once(ROOT_DIR . '/includes/entity_merge.inc.php');

$user = new User($client);
if ($user->checkSession() === false) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required.']);
    exit();
}

$merger = new EntityMerger($client);
if (!$merger->canMerge($user)) {
    http_response_code(403);
    echo json_encode(['error' => 'Only project leads and administrators can merge entities.']);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        if ($action === 'candidates') {
            $type = isset($_GET['type']) && $_GET['type'] !== '' ? (string)$_GET['type'] : null;
            echo json_encode([
                'success' => true,
                'candidates' => $merger->findDuplicateCandidates($type),
            ]);
            exit();
        }
        if ($action === 'search') {
            $q = (string)($_GET['q'] ?? '');
            $type = isset($_GET['type']) && $_GET['type'] !== '' ? (string)$_GET['type'] : null;
            echo json_encode([
                'success' => true,
                'entities' => $merger->searchEntities($q, $type),
            ]);
            exit();
        }
        if ($action === 'preview') {
            $sourceId = isset($_GET['source_id']) ? (int)$_GET['source_id'] : 0;
            $targetId = isset($_GET['target_id']) ? (int)$_GET['target_id'] : 0;
            echo json_encode([
                'success' => true,
                'preview' => $merger->previewMerge($sourceId, $targetId),
            ]);
            exit();
        }

        http_response_code(400);
        echo json_encode(['error' => 'Unknown GET action.']);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed.']);
        exit();
    }

    $rawInput = file_get_contents('php://input');
    $jsonInput = json_decode($rawInput, true);
    $data = is_array($jsonInput) ? $jsonInput : $_POST;

    $submittedToken = $data['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $tokenManager = new CsrfTokenManager();
    if (!$tokenManager->checkToken($submittedToken)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid or expired CSRF token.']);
        exit();
    }

    $action = $data['action'] ?? '';
    if ($action === 'merge') {
        $sourceId = isset($data['source_id']) ? (int)$data['source_id'] : 0;
        $targetId = isset($data['target_id']) ? (int)$data['target_id'] : 0;
        $resolved = is_array($data['resolved_properties'] ?? null) ? $data['resolved_properties'] : [];
        $result = $merger->executeMerge($sourceId, $targetId, $resolved, $user);
        echo json_encode($result);
        exit();
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unknown POST action.']);
} catch (\InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} catch (\RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Merge failed: ' . $e->getMessage()]);
}
