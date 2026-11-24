<?php
include_once(dirname(__DIR__) . "/config/config.inc.php");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$x = $_GET;
if (isset($x['node']) && isset($x['lang'])) {
    $nodeid = (int)$x['node'];
    $lang = $x['lang'];

    // increase memory and execution time for processing long texts
    ini_set('memory_limit', '512M');
    set_time_limit(300);

    // fetch text content directly from neo4j to transmit payload securely
    include_once(ROOT_DIR . '/includes/client.inc.php');
    $textContent = '';
    try {
        $textProperty = defined('TEXNODETEXT') ? TEXNODETEXT : 'text';
        $textQuery = 'MATCH (n) WHERE id(n) = $nodeid RETURN n.' . $textProperty . ' AS textContent LIMIT 1';
        $result = $client->run($textQuery, ['nodeid' => $nodeid]);
        if ($result->count() > 0) {
            $textContent = (string)($result->first()->get('textContent') ?? '');
        }
    } catch (\Throwable $e) {
        error_log('Failed to fetch text content for node ' . $nodeid . ': ' . $e->getMessage());
    }

    $json_body = array(
        "text" => $textContent,
        "lang" => $lang,
        "nodeid" => $nodeid,
        "uri" => defined('URI') ? URI : '',
        "username" => defined('USERNAME') ? USERNAME : '',
        "password" => defined('PASSWORD') ? PASSWORD : '',
        "database" => defined('DBNAME') ? DBNAME : '',
        "textlabel" => defined('TEXNODE') ? TEXNODE : 'Text',
        "textproperty" => defined('TEXNODETEXT') ? TEXNODETEXT : 'text'
    );

    $method_name = 'extract_entities';
    $gunicorn_servlet = defined('CONNECTORENDPOINT') ? CONNECTORENDPOINT : 'localhost:8000';
    if (!preg_match('#^https?://#i', $gunicorn_servlet)) {
        $gunicorn_servlet = 'http://' . $gunicorn_servlet;
    }

    $url = rtrim($gunicorn_servlet, '/') . '/' . $method_name;

    // convert payload to json with unicode preserved and invalid bytes substituted
    $json_data = json_encode($json_body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    // initialize curl request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 240); // allow up to 4 minutes for long text nlp
    curl_setopt($ch, CURLOPT_BUFFERSIZE, 128 * 1024);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charset=utf-8',
        'Content-Length: ' . strlen($json_data),
        'Expect:' // disable expect 100-continue for large payloads
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlErr) {
        http_response_code(502);
        echo json_encode(["error" => "Failed to reach LATTE connector: " . $curlErr]);
        exit();
    }

    if ($httpCode >= 400) {
        http_response_code($httpCode);
        echo $response ?: json_encode(["error" => "LATTE connector returned HTTP " . $httpCode]);
        exit();
    }

    // Persist extracted suggestions directly to text node and update queue status.
    $decodedResponse = json_decode($response, true);
    if (is_array($decodedResponse) && isset($decodedResponse['data']) && is_array($decodedResponse['data'])) {
        include_once(ROOT_DIR . '/includes/latte_queue_manager.inc.php');
        try {
            $persistedSuggestions = LatteQueueManager::saveSuggestionsToTextNode(
                $client,
                $nodeid,
                $decodedResponse['data'],
                $lang,
                $decodedResponse['meta'] ?? []
            );
            $decodedResponse['persisted_suggestions'] = $persistedSuggestions;
            echo json_encode($decodedResponse, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit();
        } catch (\Throwable $e) {
            error_log('Failed to persist automatic suggestions to text node: ' . $e->getMessage());
        }
    }

    echo $response;
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request. Missing node or lang parameter."]);
}
?>
