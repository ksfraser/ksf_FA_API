<?php
/**
 * SuiteCRM v4_1 REST API Compatibility Endpoint
 * 
 * This endpoint provides backwards compatibility with SuiteCRM/SugarCRM REST API v4_1
 * allowing existing integrations to migrate without code changes.
 * 
 * Supported CRM Systems:
 * - SuiteCRM
 * - SugarCRM (all versions)
 * - vtiger CRM
 * - Salesforce (via wrapper)
 * - Dynamics 365 (via wrapper)
 * 
 * Endpoint: /api/compat/v4_1/rest.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../tests/bootstrap.php';

use Ksfraser\Compat\v4_1\RestHandler;

$handler = new RestHandler();
$handler->handle();