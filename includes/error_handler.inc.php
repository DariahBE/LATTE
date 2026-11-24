<?php
/**
 * Global exception and error handler for database connectivity and application errors.
 *
 * Supports debug toggle ($debug / DEBUG constant):
 * - If debug is true: displays detailed error messages and stack traces natively.
 * - If debug is false: presents a generic controlled error message to the user.
 */

if (!function_exists('app_is_debug_enabled')) {
    /**
     * Determine if debug mode is active.
     */
    function app_is_debug_enabled(): bool {
        if (defined('DEBUG')) {
            return (bool)DEBUG;
        }
        if (isset($GLOBALS['debug'])) {
            return (bool)$GLOBALS['debug'];
        }
        return false;
    }
}

if (!function_exists('is_database_connection_error')) {
    /**
     * Check if a throwable is caused by database connectivity issues.
     */
    function is_database_connection_error(\Throwable $e): bool {
        $class = get_class($e);
        if (
            strpos($class, 'ConnectException') !== false ||
            strpos($class, 'ConnectionTimeoutException') !== false ||
            strpos($class, 'ConnectionException') !== false ||
            strpos($class, 'Bolt') !== false
        ) {
            return true;
        }

        $msg = strtolower($e->getMessage());
        $connectionPatterns = [
            'actively refused',
            'connection refused',
            'failed to connect',
            'could not connect',
            'cannot connect',
            'no connection could be made',
            'connection timed out',
            'connection reset by peer',
            'broken pipe',
            'network is unreachable',
            'host is down',
            'unable to connect'
        ];

        foreach ($connectionPatterns as $pattern) {
            if (strpos($msg, $pattern) !== false) {
                return true;
            }
        }

        // Inspect chained previous exceptions.
        $prev = $e->getPrevious();
        if ($prev instanceof \Throwable) {
            return is_database_connection_error($prev);
        }

        return false;
    }
}

if (!function_exists('is_database_connection_error_message')) {
    /**
     * Check if a fatal error message indicates a database connection failure.
     */
    function is_database_connection_error_message(string $msg): bool {
        $msgLower = strtolower($msg);
        $patterns = [
            'connectexception',
            'actively refused',
            'connection refused',
            'no connection could be made',
            'failed to connect',
            'could not connect',
            'cannot connect',
            'connection timed out',
            'connection reset',
            'unable to connect'
        ];

        foreach ($patterns as $pattern) {
            if (strpos($msgLower, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('app_render_database_error_page')) {
    /**
     * Render the database connection error page with status 503.
     */
    function app_render_database_error_page(): void {
        if (!headers_sent()) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            http_response_code(503);
            header('Retry-After: 30');
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
            || (isset($_SERVER['REQUEST_URI']) && preg_match('#/(ajax|api)/#i', $_SERVER['REQUEST_URI']));

        if ($isAjax) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'status' => 503,
                'error' => "Couldn't connect to the project database. Contact your webmaster.",
                'message' => "Couldn't connect to the project database. Contact your webmaster."
            ]);
            exit;
        }

        $baseUrl = defined('BASE_URL') ? BASE_URL : (defined('WEBURL') ? WEBURL : '');
        $projectName = defined('PROJECTNAME') ? PROJECTNAME : 'Project database';
        ?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database connection error - <?php echo htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>/CSS/stylePublic.css">
  </head>
  <body class="bg-slate-100 min-h-screen flex items-center justify-center p-4 antialiased text-slate-800">
    <main class="max-w-lg w-full bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8 text-center" role="alert" aria-live="assertive">
      <div class="w-16 h-16 bg-rose-50 text-rose-600 border border-rose-200 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
      </div>
      <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700 mb-3">
        <span>503</span>
        <span>•</span>
        <span>Service unavailable</span>
      </div>
      <h1 class="text-2xl font-bold text-slate-900 mb-2">
        Database connection error
      </h1>
      <p class="text-base font-semibold text-rose-600 mb-4">
        Couldn't connect to the project database. Contact your webmaster.
      </p>
      <div class="text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-xl p-3.5 mb-6 text-left space-y-1.5 leading-relaxed">
        <div class="font-medium text-slate-700">Diagnostics:</div>
        <p>The graph database server is currently offline, refusing connections, or unreachable.</p>
        <p class="text-slate-500">Please contact your webmaster to verify that the Neo4j database service is running and your LATTE config files are correct.</p>
      </div>
      <div class="flex flex-col sm:flex-row gap-2.5 justify-center">
        <button type="button" onclick="window.location.reload()" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          Retry connection
        </button>
        <a href="<?php echo htmlspecialchars($baseUrl ?: '/', ENT_QUOTES, 'UTF-8'); ?>" class="px-5 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-medium text-sm transition-colors shadow-sm">
          Return to homepage
        </a>
        <button type="button" onclick="window.history.back()" class="px-5 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-medium text-sm transition-colors">
          Go back
        </button>
      </div>
    </main>
  </body>
</html>
        <?php
        exit;
    }
}

if (!function_exists('app_render_generic_error_page')) {
    /**
     * Render the generic controlled error page with status 500.
     */
    function app_render_generic_error_page(): void {
        if (!headers_sent()) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            http_response_code(500);
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
            || (isset($_SERVER['REQUEST_URI']) && preg_match('#/(ajax|api)/#i', $_SERVER['REQUEST_URI']));

        if ($isAjax) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'status' => 500,
                'error' => "An unexpected error occurred. Contact your webmaster.",
                'message' => "An unexpected error occurred. Contact your webmaster."
            ]);
            exit;
        }

        $baseUrl = defined('BASE_URL') ? BASE_URL : (defined('WEBURL') ? WEBURL : '');
        $projectName = defined('PROJECTNAME') ? PROJECTNAME : 'LATTE platform';
        ?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application error - <?php echo htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>/CSS/stylePublic.css">
  </head>
  <body class="bg-slate-100 min-h-screen flex items-center justify-center p-4 antialiased text-slate-800">
    <main class="max-w-lg w-full bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8 text-center" role="alert" aria-live="assertive">
      <div class="w-16 h-16 bg-amber-50 text-amber-600 border border-amber-200 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
      </div>
      <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 mb-3">
        <span>500</span>
        <span>•</span>
        <span>Application error</span>
      </div>
      <h1 class="text-2xl font-bold text-slate-900 mb-2">
        An unexpected error occurred
      </h1>
      <p class="text-base font-medium text-slate-600 mb-6">
        The application encountered an internal error. Contact your webmaster.
      </p>
      <div class="flex flex-col sm:flex-row gap-2.5 justify-center">
        <button type="button" onclick="window.location.reload()" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          Try again
        </button>
        <a href="<?php echo htmlspecialchars($baseUrl ?: '/', ENT_QUOTES, 'UTF-8'); ?>" class="px-5 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-medium text-sm transition-colors shadow-sm">
          Return to homepage
        </a>
        <button type="button" onclick="window.history.back()" class="px-5 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-medium text-sm transition-colors">
          Go back
        </button>
      </div>
    </main>
  </body>
</html>
        <?php
        exit;
    }
}

if (!function_exists('app_global_exception_handler')) {
    /**
     * Intercept uncaught exceptions and render either debug traces or controlled views.
     */
    function app_global_exception_handler(\Throwable $e): void {
        error_log('Uncaught ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

        // When debug mode is on, output the exact error message and stack trace like native PHP.
        if (app_is_debug_enabled()) {
            if (!headers_sent()) {
                http_response_code(500);
            }
            echo "\nFatal error: Uncaught " . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            echo "  thrown in " . $e->getFile() . " on line " . $e->getLine() . "\n";
            exit(1);
        }

        // When debug mode is off, show controlled error views.
        if (is_database_connection_error($e)) {
            app_render_database_error_page();
        }

        app_render_generic_error_page();
    }
}

if (!function_exists('app_global_shutdown_handler')) {
    /**
     * Intercept fatal PHP errors during shutdown.
     */
    function app_global_shutdown_handler(): void {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $msg = $error['message'] ?? '';
            if (is_database_connection_error_message($msg)) {
                app_render_database_error_page();
            } else if (!app_is_debug_enabled()) {
                app_render_generic_error_page();
            }
        }
    }
}

// Configure display errors based on debug toggle.
if (app_is_debug_enabled()) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
}

// Register global exception and shutdown handlers.
set_exception_handler('app_global_exception_handler');
register_shutdown_function('app_global_shutdown_handler');
?>
