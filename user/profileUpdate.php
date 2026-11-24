<?php
/**
 * Handle user preference updates from standard form submissions.
 */

if (!defined('ROOT_DIR')) {
    include_once(dirname(__DIR__) . '/config/config.inc.php');
}
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');
include_once(ROOT_DIR . '/includes/client.inc.php');
include_once(ROOT_DIR . '/includes/wikidata_user_prefs.inc.php');

if (!isset($_SESSION['userid'])) {
    $target = defined('BASE_URL') ? BASE_URL . '/user/login.php' : '/user/login.php';
    header("Location: $target");
    exit();
}

$tokenManager = new CsrfTokenManager();
$submittedToken = $_POST['csrf_token'] ?? '';
if (!$tokenManager->checkToken($submittedToken)) {
    header("HTTP/1.0 403 Forbidden");
    die('Invalid session token');
}

$preferences = new Wikidata_user($client);
$key_to_tell_which_form = 'form_type_setting_application_value';
$formname = $_POST[$key_to_tell_which_form] ?? '';

$baseUrl = defined('BASE_URL') ? BASE_URL : '';
$userId = (int)$_SESSION['userid'];

if ($formname === 'links' || $formname === 'titles' || $formname === 'language') {
    $keys = array_keys($_POST);
    $keys = array_diff($keys, array($key_to_tell_which_form, 'csrf_token'));
    $actionGotThrough = $preferences->storeProfileSettings($formname, $keys);

    if ($actionGotThrough) {
        header('Location: ' . $baseUrl . '/user/mypage.php');
        exit();
    }
}

header('Location: ' . $baseUrl . '/user/mypage.php');
exit();
