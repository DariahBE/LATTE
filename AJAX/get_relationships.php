<?php
/**
 * Paginated, sortable, and filterable relationships endpoint for stable.php.
 */

header('Content-Type: application/json; charset=utf-8');

include_once(dirname(__DIR__) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/client.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');

$user = new User($client);
$user->checkAccess(ENTITIESAREPUBLIC);

$neoId = isset($_GET['neoid']) ? (int)$_GET['neoid'] : 0;
if ($neoId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid node ID.']);
    exit;
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 25;
$skip = ($page - 1) * $limit;

$sortParam = isset($_GET['sort']) ? strtolower(trim((string)$_GET['sort'])) : 'relation';
$dirParam = isset($_GET['dir']) ? strtolower(trim((string)$_GET['dir'])) : 'asc';
$sortExpr = ($sortParam === 'node') ? 'labels(t)[0]' : 'type(r)';
$dirExpr = ($dirParam === 'desc') ? 'DESC' : 'ASC';

$filterRelation = isset($_GET['relation']) ? trim((string)$_GET['relation']) : '';
$filterNode = isset($_GET['node']) ? trim((string)$_GET['node']) : '';
$filterQuery = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

try {
    // Collect distinct relation types and node labels for filter dropdowns.
    $facetsQuery = '
        MATCH (n)-[r]-(t)
        WHERE id(n) = $neoid AND NOT n:priv_user AND NOT t:priv_user AND NOT r:see_also
        RETURN collect(DISTINCT type(r)) AS relations, collect(DISTINCT labels(t)[0]) AS nodeLabels
    ';
    $facetsRes = $client->run($facetsQuery, ['neoid' => $neoId]);
    $availableRelations = [];
    $availableNodes = [];
    if ($facetsRes->count() > 0) {
        $fRow = $facetsRes->first();
        $rels = $fRow->get('relations');
        $nLabels = $fRow->get('nodeLabels');
        $availableRelations = $rels ? (is_array($rels) ? $rels : $rels->toArray()) : [];
        $availableNodes = $nLabels ? (is_array($nLabels) ? $nLabels : $nLabels->toArray()) : [];
        sort($availableRelations, SORT_STRING | SORT_FLAG_CASE);
        sort($availableNodes, SORT_STRING | SORT_FLAG_CASE);
    }

    $whereClauses = [
        'id(n) = $neoid',
        'NOT n:priv_user',
        'NOT t:priv_user',
        'NOT r:see_also'
    ];
    $params = ['neoid' => $neoId];

    if ($filterRelation !== '') {
        $whereClauses[] = 'type(r) = $filterRelation';
        $params['filterRelation'] = $filterRelation;
    }
    if ($filterNode !== '') {
        $whereClauses[] = 'labels(t)[0] = $filterNode';
        $params['filterNode'] = $filterNode;
    }
    if ($filterQuery !== '') {
        $whereClauses[] = '(toLower(type(r)) CONTAINS toLower($q) OR toLower(labels(t)[0]) CONTAINS toLower($q) OR any(k IN keys(t) WHERE k <> "priv_user" AND toLower(toString(t[k])) CONTAINS toLower($q)))';
        $params['q'] = $filterQuery;
    }

    $whereSql = implode(' AND ', $whereClauses);

    $countQuery = 'MATCH (n)-[r]-(t) WHERE ' . $whereSql . ' RETURN count(r) AS total';
    $total = (int)$client->run($countQuery, $params)->first()->get('total');
    $totalPages = max(1, (int)ceil($total / $limit));
    if ($page > $totalPages) {
        $page = $totalPages;
        $skip = ($page - 1) * $limit;
    }

    $dataQuery = '
        MATCH (n)-[r]-(t)
        WHERE ' . $whereSql . '
        RETURN type(r) AS relType, t
        ORDER BY ' . $sortExpr . ' ' . $dirExpr . ', id(r) ASC
        SKIP $skip LIMIT $limit
    ';
    $params['skip'] = $skip;
    $params['limit'] = $limit;

    $res = $client->run($dataQuery, $params);
    $rows = [];
    foreach ($res as $row) {
        $relType = (string)$row->get('relType');
        $relatedNode = $row->get('t');
        $labels = $relatedNode['labels'];
        $nodeLabel = isset($labels[0]) ? (string)$labels[0] : 'Node';
        $nodeProps = $relatedNode['properties'];
        $model = (defined('NODEMODEL') && isset(NODEMODEL[$nodeLabel])) ? NODEMODEL[$nodeLabel] : [];

        $formattedProps = [];
        foreach ($nodeProps as $propkey => $propValue) {
            if ($propkey !== 'priv_user' && array_key_exists($propkey, $model)) {
                $meta = $model[$propkey];
                $formattedProps[] = [
                    'name' => (string)$meta[0],
                    'type' => (string)$meta[1],
                    'value' => is_bool($propValue) ? ($propValue ? 'true' : 'false') : (string)$propValue
                ];
            }
        }

        $rows[] = [
            'relation' => $relType,
            'node_label' => $nodeLabel,
            'properties' => $formattedProps
        ];
    }

    echo json_encode([
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => $totalPages,
        'sort' => $sortParam === 'node' ? 'node' : 'relation',
        'dir' => $dirParam === 'desc' ? 'desc' : 'asc',
        'available_relations' => $availableRelations,
        'available_nodes' => $availableNodes,
        'rows' => $rows
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load relationships.']);
}
