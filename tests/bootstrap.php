<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

if (!defined('TB_PREF')) {
    define('TB_PREF', 'fa_');
}

function db_query(string $sql) {
    return true;
}

function db_fetch_assoc($result): ?array {
    return null;
}

function db_escape(string $value): string {
    return "'" . addslashes($value) . "'";
}

function db_insert(string $table) {
    return 1;
}

function db_insert_id() {
    return 1;
}

function get_option(string $name, mixed $default = ''): mixed {
    return $default;
}

/**
 * Stub for FA's hook_invoke_first().
 *
 * Tests control return values via $GLOBALS['_test_hook_results']:
 *   $GLOBALS['_test_hook_results']['calendar_entry_create'] = 42;
 *   // or a callable for dynamic responses:
 *   $GLOBALS['_test_hook_results']['calendar_entries_query'] = function(string $method, array &$data) {
 *       $data['entries'] = [['id'=>1]];
 *       return $data['entries'];
 *   };
 *
 * If no entry exists for $method, null is returned (no handler installed).
 */
function hook_invoke_first(string $method, array &$data, array $opts = [])
{
    if (!isset($GLOBALS['_test_hook_results'][$method])) {
        return null;
    }
    $result = $GLOBALS['_test_hook_results'][$method];
    if (is_callable($result)) {
        return $result($method, $data, $opts);
    }
    return $result;
}