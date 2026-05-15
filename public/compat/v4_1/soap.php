<?php
/**
 * SuiteCRM v4_1 SOAP API Compatibility Endpoint
 * 
 * This endpoint provides backwards compatibility with SuiteCRM/SugarCRM SOAP API
 * allowing existing integrations to migrate without code changes.
 * 
 * Endpoint: /api/compat/v4_1/soap.php
 */

declare(strict_types=1);

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../tests/bootstrap.php';

use Ksfraser\Compat\v4_1\SoapHandler;

header('Content-Type: application/xml');

$requestBody = file_get_contents('php://input');

$handler = new SoapHandler();
$response = $handler->handle($requestBody);

echo $response;