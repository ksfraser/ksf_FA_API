<?php
/**
 * KSF FA API — FA-bootstrapped REST front controller
 *
 * This is the HTTP entry point for the native KSF REST API.
 * It follows the standard FA module page pattern so that FA's session, database,
 * and hook system are all initialised before any controller code runs:
 *
 *   1. chdir(__DIR__)          — make relative includes resolve from module dir
 *   2. $page_security          — MUST be set before session.inc
 *   3. include session.inc     — bootstraps $db, TB_PREF, $Hooks[], auth check
 *   4. add_access_extensions() — populate $Hooks[] with extension modules
 *   5. route + dispatch        — parse path, call controller action
 *
 * No page() / end_page() are called — only JSON is returned.
 *
 * URL scheme:
 *   /modules/ksf_FA_API/api.php/<version>/<resource>[/<id>]
 *   e.g. /modules/ksf_FA_API/api.php/v1/calendar/entries
 *        /modules/ksf_FA_API/api.php/v1/calendar/entries/42
 *
 * The path after api.php is extracted from PATH_INFO (Apache mod_rewrite) or,
 * as a fallback, from the '_path' query parameter.
 *
 * PHP 7.4 compatible — no PHP 8+ syntax.
 *
 * @package Ksfraser\FA\API
 * @since 1.0.0
 */

// ---------------------------------------------------------------------------
// Step 1 — resolve all relative includes from this module directory.
// ---------------------------------------------------------------------------
chdir(__DIR__);

// ---------------------------------------------------------------------------
// Step 2 — security area MUST be set before session.inc is included.
//           SA_ksf_FA_APIVIEW is defined in hooks_ksf_FA_API::install_access().
// ---------------------------------------------------------------------------
$page_security = 'SA_ksf_FA_APIVIEW';

// ---------------------------------------------------------------------------
// Step 3 — bootstrap FA: defines $db, TB_PREF, session vars, hook functions…
// ---------------------------------------------------------------------------
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

// ---------------------------------------------------------------------------
// Step 4 — populate $Hooks[] with all installed extension modules so that
//           hook_invoke_first / hook_invoke_all resolve correctly.
// ---------------------------------------------------------------------------
add_access_extensions();

// ---------------------------------------------------------------------------
// Step 5 — load Composer autoloader so namespaced controller classes resolve.
// ---------------------------------------------------------------------------
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use Ksfraser\FA\API\REST\CalendarController;

// ---------------------------------------------------------------------------
// Always respond with JSON.
// ---------------------------------------------------------------------------
header('Content-Type: application/json; charset=utf-8');

// ---------------------------------------------------------------------------
// Parse the request path.
//
// Preferred: PATH_INFO  (requires AllowEncodedSlashes + AcceptPathInfo On in
//            Apache config, or an equivalent RewriteRule).
// Fallback:  ?_path=...  query parameter (works without server configuration).
// ---------------------------------------------------------------------------
$pathInfo = '';

if (!empty($_SERVER['PATH_INFO'])) {
    $pathInfo = $_SERVER['PATH_INFO'];
} elseif (!empty($_GET['_path'])) {
    $pathInfo = '/' . ltrim((string) $_GET['_path'], '/');
}

// Strip leading slash and split into segments.
$segments = array_values(array_filter(explode('/', ltrim($pathInfo, '/'))));
// Expected layout: [version, resource, sub-resource, id?]
// e.g. ['v1', 'calendar', 'entries']  or  ['v1', 'calendar', 'entries', '42']

$method = strtoupper($_SERVER['REQUEST_METHOD']);

// ---------------------------------------------------------------------------
// Router — dispatch to the appropriate controller action.
// ---------------------------------------------------------------------------
try {
    api_dispatch($method, $segments);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('success' => false, 'error' => 'Internal server error'));
    error_log('KSF FA API error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}
exit;

// ---------------------------------------------------------------------------
// Dispatcher
// ---------------------------------------------------------------------------

/**
 * Route the request to the correct controller and action.
 *
 * @param string $method   HTTP method (GET, POST, PUT, DELETE, …)
 * @param array  $segments URL path segments after api.php
 * @return void
 *
 * @since 1.0.0
 */
function api_dispatch($method, array $segments)
{
    // We need at least [version, resource, sub-resource].
    // version is currently accepted but not validated — future-proof.
    if (count($segments) < 3) {
        http_response_code(404);
        echo json_encode(array('success' => false, 'error' => 'Unknown endpoint'));
        return;
    }

    // $segments[0] = version  (e.g. 'v1')
    $resource    = strtolower($segments[1]);   // e.g. 'calendar'
    $subResource = strtolower($segments[2]);   // e.g. 'entries'
    $id          = isset($segments[3]) ? (int) $segments[3] : 0;

    $pathArgs = array('id' => $id);

    // ------------------------------------------------------------------
    // Calendar endpoints
    // ------------------------------------------------------------------
    if ($resource === 'calendar' && $subResource === 'entries') {
        $controller = new CalendarController();

        if ($method === 'GET') {
            if ($id > 0) {
                $controller->get($pathArgs);
            } else {
                $controller->list($pathArgs);
            }
            return;
        }

        if ($method === 'POST') {
            $controller->create($pathArgs);
            return;
        }

        if ($method === 'PUT' || $method === 'PATCH') {
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(array('success' => false, 'error' => 'Entry ID required for update'));
                return;
            }
            $controller->update($pathArgs);
            return;
        }

        if ($method === 'DELETE') {
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(array('success' => false, 'error' => 'Entry ID required for delete'));
                return;
            }
            $controller->delete($pathArgs);
            return;
        }

        http_response_code(405);
        echo json_encode(array('success' => false, 'error' => 'Method not allowed'));
        return;
    }

    // ------------------------------------------------------------------
    // Unknown resource
    // ------------------------------------------------------------------
    http_response_code(404);
    echo json_encode(array('success' => false, 'error' => 'Unknown endpoint'));
}
