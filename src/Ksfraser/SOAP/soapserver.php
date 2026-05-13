<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Ksfraser\Database\DbManager;
use Ksf\HRM\Api\Soap\EmployeeSoapService;
use Ksf\HRM\Repository\EmployeeRepository;

header('Content-Type: application/xml');

$requestBody = file_get_contents('php://input');
$dom = new \DOMDocument();

if (!$dom->loadXML($requestBody)) {
    echo '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
        <soap:Body>
            <soap:Fault>
                <faultcode>soap:Client</faultcode>
                <faultstring>Invalid XML</faultstring>
            </soap:Fault>
        </soap:Body>
    </soap:Envelope>';
    exit;
}

$employeeRepo = new EmployeeRepository();
$soapService = new EmployeeSoapService($employeeRepo);

$envelope = $dom->getElementsByTagNameNS('http://schemas.xmlsoap.org/soap/envelope/', 'Envelope')[0] ?? null;
$body = $envelope?->getElementsByTagNameNS('http://schemas.xmlsoap.org/soap/envelope/', 'Body')[0] ?? null;

if (!$body || $body->childNodes->length === 0) {
    echo '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
        <soap:Body>
            <soap:Fault>
                <faultcode>soap:Client</faultcode>
                <faultstring>Missing SOAP Body</faultstring>
            </soap:Fault>
        </soap:Body>
    </soap:Envelope>';
    exit;
}

$action = $body->firstChild;
$actionName = $action->localName ?? $action->nodeName;

function generateResponse(string $bodyContent): string
{
    return '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        ' . $bodyContent . '
    </soap:Body>
</soap:Envelope>';
}

function arrayToXml(array $data, string $rootName): string
{
    $xml = '<' . $rootName . '>';
    foreach ($data as $key => $value) {
        $key = is_numeric($key) ? 'item' : $key;
        if (is_array($value)) {
            $xml .= arrayToXml($value, $key);
        } else {
            $xml .= '<' . $key . '>' . htmlspecialchars((string)$value) . '</' . $key . '>';
        }
    }
    $xml .= '</' . $rootName . '>';
    return $xml;
}

try {
    switch ($actionName) {
        case 'GetEmployee':
            $id = (int)$action->getElementsByTagName('id')[0]->textContent;
            $result = $soapService->getEmployee($id);
            echo generateResponse(arrayToXml($result ?: [], 'GetEmployeeResponse'));
            break;

        case 'GetEmployeeByEmail':
            $email = $action->getElementsByTagName('email')[0]->textContent;
            $result = $soapService->getEmployeeByEmail($email);
            echo generateResponse(arrayToXml($result ?: [], 'GetEmployeeByEmailResponse'));
            break;

        case 'GetEmployeeByNumber':
            $number = $action->getElementsByTagName('employeeNumber')[0]->textContent;
            $result = $soapService->getEmployeeByNumber($number);
            echo generateResponse(arrayToXml($result ?: [], 'GetEmployeeByNumberResponse'));
            break;

        case 'ListEmployees':
            $status = $action->getElementsByTagName('status')[0]?->textContent;
            $result = $soapService->listEmployees($status);
            echo generateResponse(arrayToXml(['employee' => $result], 'ListEmployeesResponse'));
            break;

        case 'CreateEmployee':
            $data = [];
            foreach ($action->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $data[$child->localName] = $child->textContent;
                }
            }
            $result = $soapService->createEmployee($data);
            echo generateResponse(arrayToXml($result, 'CreateEmployeeResponse'));
            break;

        case 'UpdateEmployee':
            $id = (int)$action->getElementsByTagName('id')[0]->textContent;
            $data = [];
            foreach ($action->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE && $child->localName !== 'id') {
                    $data[$child->localName] = $child->textContent;
                }
            }
            $result = $soapService->updateEmployee($id, $data);
            if (!$result) {
                echo generateResponse('<NotFound/>');
            } else {
                echo generateResponse(arrayToXml($result, 'UpdateEmployeeResponse'));
            }
            break;

        case 'DeleteEmployee':
            $id = (int)$action->getElementsByTagName('id')[0]->textContent;
            $result = $soapService->deleteEmployee($id);
            echo generateResponse('<DeleteEmployeeResponse><success>' . ($result ? 'true' : 'false') . '</success></DeleteEmployeeResponse>');
            break;

        default:
            echo generateResponse('<soap:Fault><faultcode>soap:Client</faultcode><faultstring>Unknown action: ' . htmlspecialchars($actionName) . '</faultstring></soap:Fault>');
    }
} catch (\Throwable $e) {
    echo generateResponse('<soap:Fault><faultcode>soap:Server</faultcode><faultstring>' . htmlspecialchars($e->getMessage()) . '</faultstring></soap:Fault>');
}