<?php
/**
 * Matomo analytics tracking helper.
 * Renders the Matomo JavaScript snippet when tracking is enabled and the current visitor is not excluded.
 */

if (!function_exists('resolveMatomoEndpoint')) {
    /**
     * Resolve a Matomo tracker JS or PHP endpoint from a base URL and a configured filename or path.
     */
    function resolveMatomoEndpoint(string $baseUrl, string $path, string $default): string {
        $target = trim($path);
        if ($target === '') {
            $target = $default;
        }
        if (preg_match('#^https?://#i', $target) || strpos($target, '/') === 0) {
            return $target;
        }
        return rtrim($baseUrl, '/') . '/' . ltrim($target, '/');
    }
}

if (!function_exists('renderMatomoTracking')) {
    /**
     * Return the Matomo tracking script tag, or an empty string if tracking is disabled or excluded.
     */
    function renderMatomoTracking(): string {
        if (!defined('MATOMO_ENABLED') || !MATOMO_ENABLED) {
            return '';
        }

        $baseUrl = defined('MATOMO_URL') ? trim((string)MATOMO_URL) : '';
        $siteId = defined('MATOMO_SITE_ID') ? (int)MATOMO_SITE_ID : 0;
        if ($baseUrl === '' || $siteId <= 0 || !preg_match('#^https?://#i', $baseUrl)) {
            return '';
        }

        // Never track administration pages.
        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
        if ($scriptName !== '' && stripos($scriptName, '/admin/') !== false) {
            return '';
        }

        // Exclude logged-in users when configured; start session if the page has not loaded user.inc.php.
        if (defined('MATOMO_EXCLUDE_LOGGED_IN') && MATOMO_EXCLUDE_LOGGED_IN) {
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                session_start();
            }
            if (!empty($_SESSION['userid'])) {
                return '';
            }
        }

        $jsPath = defined('MATOMO_JS_PATH') ? (string)MATOMO_JS_PATH : 'matomo.js';
        $phpPath = defined('MATOMO_PHP_PATH') ? (string)MATOMO_PHP_PATH : 'matomo.php';

        $trackerPhpUrl = resolveMatomoEndpoint($baseUrl, $phpPath, 'matomo.php');
        $trackerJsUrl = resolveMatomoEndpoint($baseUrl, $jsPath, 'matomo.js');

        $flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES;
        $encodedPhpUrl = json_encode($trackerPhpUrl, $flags);
        $encodedJsUrl = json_encode($trackerJsUrl, $flags);
        $encodedSiteId = json_encode((string)$siteId, $flags);

        $dntLine = (defined('MATOMO_HONOR_DNT') && MATOMO_HONOR_DNT)
            ? "  _paq.push(['setDoNotTrack', true]);\n"
            : '';

        return <<<HTML
<!-- Matomo -->
<script>
  var _paq = window._paq = window._paq || [];
{$dntLine}  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    _paq.push(['setTrackerUrl', {$encodedPhpUrl}]);
    _paq.push(['setSiteId', {$encodedSiteId}]);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.async=true; g.src={$encodedJsUrl}; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Code -->

HTML;
    }
}
