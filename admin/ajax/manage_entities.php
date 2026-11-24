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

// CSRF validation for modifying actions.
$tokenManager = new CsrfTokenManager();
$submittedToken = $_POST['token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!$tokenManager->checkToken($submittedToken)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired CSRF token.']);
    exit();
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_all':
            $entities = ConfigModelManager::getAllEntitiesWithProperties();
            $coreStatus = ConfigModelManager::getCoreEntitiesStatus();
            echo json_encode([
                'status' => 'success',
                'data' => $entities,
                'core_status' => $coreStatus,
            ]);
            break;

        case 'scaffold_core_entities':
            $scaffolded = ConfigModelManager::scaffoldAllCoreEntities();
            echo json_encode([
                'status' => 'success',
                'message' => 'Core architecture entities (Text, Annotation, and Variant) scaffolded successfully with their required properties.',
                'data' => $scaffolded,
            ]);
            break;

        case 'scaffold_single_core':
            // Force capitalization of node names (e.g. Text); and force lowercasing of node properties)
            $typeName = ucfirst(preg_replace('/\s+/', '', trim($_POST['type_name'] ?? '')));
            if ($typeName === '') {
                throw new InvalidArgumentException('Entity type name is required.');
            }
            $scaffoldedId = ConfigModelManager::scaffoldCoreEntity($typeName);
            echo json_encode([
                'status' => 'success',
                'message' => "Core entity '{$typeName}' scaffolded successfully with required properties.",
                'id' => $scaffoldedId,
            ]);
            break;

        case 'reset_entity_model':
            ConfigModelManager::resetEntityModel();
            echo json_encode([
                'status' => 'success',
                'message' => 'Entity model reset successfully to the empty post-initialization state.',
            ]);
            break;

        case 'save_entity':
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            // Force capitalization of node names (e.g. Text); and force lowercasing of node properties)
            $name = ucfirst(preg_replace('/\s+/', '', trim($_POST['name'] ?? '')));
            $plural = trim($_POST['plural_label'] ?? '');
            $color = trim($_POST['color'] ?? 'rgba(39, 123, 245, 0.6)');
            $isSearchable = !empty($_POST['is_searchable']) ? 1 : 0;
            $sortOrder = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 100;

            if ($name === '') {
                throw new InvalidArgumentException('Entity name is required.');
            }

            $savedId = ConfigModelManager::saveEntityType([
                'id' => $id,
                'name' => $name,
                'plural_label' => $plural !== '' ? $plural : $name,
                'color' => $color,
                'is_searchable' => $isSearchable,
                'sort_order' => $sortOrder,
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => $id ? 'Entity type updated successfully.' : 'Entity type created successfully.',
                'id' => $savedId,
            ]);
            break;

        case 'delete_entity':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new InvalidArgumentException('Invalid entity ID.');
            }
            ConfigModelManager::deleteEntityType($id);
            echo json_encode(['status' => 'success', 'message' => 'Entity type deleted successfully.']);
            break;

        case 'save_property':
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $entityTypeId = (int)($_POST['entity_type_id'] ?? 0);
            // Force capitalization of node names (e.g. Text); and force lowercasing of node properties)
            $name = strtolower(preg_replace('/\s+/', '', trim($_POST['name'] ?? '')));
            $displayName = trim($_POST['display_name'] ?? '');
            $dataType = trim($_POST['data_type'] ?? 'string');
            $isUnique = !empty($_POST['is_unique']) ? 1 : 0;
            $isVisual = !empty($_POST['is_visual_distinguishing']) ? 1 : 0;
            $isSearchable = !empty($_POST['is_searchable']) ? 1 : 0;
            $sortOrder = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 100;

            if ($name === '') {
                throw new InvalidArgumentException('Property name is required.');
            }

            $savedId = ConfigModelManager::saveProperty([
                'id' => $id,
                'entity_type_id' => $entityTypeId,
                'name' => $name,
                'display_name' => $displayName !== '' ? $displayName : $name,
                'data_type' => $dataType,
                'is_unique' => $isUnique,
                'is_visual_distinguishing' => $isVisual,
                'is_searchable' => $isSearchable,
                'sort_order' => $sortOrder,
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => $id ? 'Property updated successfully.' : 'Property added successfully.',
                'id' => $savedId,
            ]);
            break;

        case 'delete_property':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new InvalidArgumentException('Invalid property ID.');
            }
            ConfigModelManager::deleteProperty($id);
            echo json_encode(['status' => 'success', 'message' => 'Property deleted successfully.']);
            break;

        case 'rebuild_cache':
            ConfigModelManager::rebuildCache();
            echo json_encode(['status' => 'success', 'message' => 'Configuration cache rebuilt successfully.']);
            break;

        case 'sync_neo4j':
            $model = ConfigModelManager::getModel()['nodesDatamodel'];
            $createdCount = 0;

            foreach ($model as $key => $value) {
                // Force capitalization of node names (e.g. Text); and force lowercasing of node properties)
                $nodeLabel = ucfirst(preg_replace('/\s+/', '', (string)$key));
                foreach ($value as $propName => $propertyList) {
                    // Force capitalization of node names (e.g. Text); and force lowercasing of node properties)
                    $cleanPropName = strtolower(preg_replace('/\s+/', '', (string)$propName));
                    $nameForUQConstraint = $nodeLabel . '_' . $cleanPropName . '_uq';
                    $nameForIXConstraint = $nodeLabel . '_' . $cleanPropName . '_index';
                    $addUniqueness = $propertyList[2];
                    $addIndex = $propertyList[4];

                    $uniqueQuery = "CREATE CONSTRAINT $nameForUQConstraint IF NOT EXISTS ON (n:$nodeLabel) ASSERT n.$cleanPropName IS UNIQUE;";
                    $indexQuery = "CREATE INDEX $nameForIXConstraint IF NOT EXISTS FOR (n:$nodeLabel) on (n.$cleanPropName)";

                    try {
                        if ($addUniqueness) {
                            $client->run($uniqueQuery);
                            $createdCount++;
                        } elseif ($addIndex) {
                            $client->run($indexQuery);
                            $createdCount++;
                        }
                    } catch (\Throwable $neoError) {
                        // Continue on individual index failure.
                    }
                }
            }
            echo json_encode([
                'status' => 'success',
                'message' => "Neo4j schema synchronized successfully ($createdCount constraints/indexes processed).",
            ]);
            break;

        default:
            throw new InvalidArgumentException('Unknown action requested.');
    }
} catch (\Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
