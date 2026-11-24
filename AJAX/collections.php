<?php
/**
 * AJAX endpoint for managing personal and project-level text collections.
 */

include_once(dirname(__DIR__) . '/config/config.inc.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

include_once(ROOT_DIR . '/includes/client.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');
include_once(ROOT_DIR . '/includes/collection.inc.php');

$user = new User($client);
$userNeoId = $user->neoId ? (int)$user->neoId : null;
$manager = new CollectionManager($client);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!TEXTSAREPUBLIC && !$user->checkSession()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required.']);
        exit();
    }

    $action = $_GET['action'] ?? 'list';
    if ($action === 'for_text') {
        $textNeoId = isset($_GET['text_neoid']) ? (int)$_GET['text_neoid'] : 0;
        if ($textNeoId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid text node ID.']);
            exit();
        }
        echo json_encode([
            'success' => true,
            'collections' => $manager->getCollectionsForText($textNeoId, $userNeoId, TEXTSAREPUBLIC, $user),
        ]);
        exit();
    }

    echo json_encode([
        'success' => true,
        'collections' => $manager->listAccessibleCollections($userNeoId, TEXTSAREPUBLIC),
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit();
}

if ($user->checkSession() === false) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required.']);
    exit();
}

$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true);
$data = is_array($jsonInput) ? $jsonInput : $_POST;

$submittedToken = $data['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
$tokenManager = new CsrfTokenManager();
if (!$tokenManager->checkToken($submittedToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid session token.']);
    exit();
}

$action = $data['action'] ?? '';

try {
    if ($action === 'create') {
        $name = (string)($data['name'] ?? '');
        $description = (string)($data['description'] ?? '');
        $scope = (string)($data['scope'] ?? 'personal');
        $created = $manager->createCollection($user, $name, $description, $scope);
        echo json_encode(['success' => true, 'collection' => $created]);
        exit();
    }

    if ($action === 'update') {
        $identifier = $data['id'] ?? ($data['uid'] ?? '');
        $name = (string)($data['name'] ?? '');
        $description = (string)($data['description'] ?? '');
        $manager->updateCollection($user, $identifier, $name, $description);
        echo json_encode(['success' => true]);
        exit();
    }

    if ($action === 'delete') {
        $identifier = $data['id'] ?? ($data['uid'] ?? '');
        $manager->deleteCollection($user, $identifier);
        echo json_encode(['success' => true]);
        exit();
    }

    if ($action === 'toggle_text') {
        $identifier = $data['id'] ?? ($data['uid'] ?? '');
        $textNeoId = isset($data['text_neoid']) ? (int)$data['text_neoid'] : 0;
        $member = filter_var($data['member'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($textNeoId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid text node ID.']);
            exit();
        }
        $manager->setTextMembership($user, $identifier, $textNeoId, $member);
        echo json_encode(['success' => true]);
        exit();
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unknown action.']);
} catch (\InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} catch (\RuntimeException $e) {
    http_response_code(403);
    echo json_encode(['error' => $e->getMessage()]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
