<?php

declare(strict_types=1);

namespace Ksfraser\Compat\v4_1;

class SoapHandler
{
    private CRMController $crm;
    private AuthHandler $auth;

    public function __construct(?CRMController $crm = null, ?AuthHandler $auth = null)
    {
        $this->auth = $auth ?? new AuthHandler();
        $this->crm = $crm ?? new CRMController($this->auth);
    }

    public function handle(string $requestXml): string
    {
        try {
            $dom = new \DOMDocument();
            $dom->loadXML($requestXml);

            $envelope = $dom->getElementsByTagNameNS('http://schemas.xmlsoap.org/soap/envelope/', 'Body')[0] ?? null;
            if (!$envelope || $envelope->childNodes->length === 0) {
                return $this->fault('Client', 'Missing SOAP Body');
            }

            $action = $envelope->firstChild;
            $methodName = $action->localName ?? '';

            $params = $this->parseSoapParams($action);

            $result = $this->dispatchSoapMethod($methodName, $params);

            return $this->buildResponse($methodName . 'Response', $result);
        } catch (\Throwable $e) {
            return $this->fault('Server', $e->getMessage());
        }
    }

    private function parseSoapParams(\DOMElement $action): array
    {
        $params = [];

        foreach ($action->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $value = trim($child->textContent);

            if ($child->firstChild && $child->firstChild->nodeType === XML_ELEMENT_NODE) {
                $params[$child->localName] = $this->parseComplexType($child);
            } elseif ($value !== '' || $child->attributes->length > 0) {
                if ($child->attributes->length > 0) {
                    foreach ($child->attributes as $attr) {
                        $params[$attr->localName] = $attr->nodeValue;
                    }
                } else {
                    $params[$child->localName] = $this->castValue($value);
                }
            }
        }

        return $params;
    }

    private function parseComplexType(\DOMElement $element): array
    {
        $result = [];

        foreach ($element->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $value = trim($child->textContent);
            if ($child->firstChild && $child->firstChild->nodeType === XML_ELEMENT_NODE) {
                $result[$child->localName] = $this->parseComplexType($child);
            } else {
                $result[$child->localName] = $this->castValue($value);
            }
        }

        return $result;
    }

    private function castValue(string $value): mixed
    {
        if ($value === 'true' || $value === 'false') {
            return $value === 'true';
        }

        if (is_numeric($value)) {
            if (strpos($value, '.') !== false) {
                return (float)$value;
            }
            return (int)$value;
        }

        return $value;
    }

    private function dispatchSoapMethod(string $method, array $params): mixed
    {
        return match ($method) {
            'do_login', 'login' => $this->crm->login([
                'user_auth' => $params['user_auth'] ?? $params,
            ]),
            'do_logout', 'logout' => $this->crm->logout($params),
            'get_entry' => $this->crm->getEntry($params),
            'get_entry_list' => $this->crm->getEntryList($params),
            'get_entries_count' => $this->crm->getEntriesCount($params),
            'set_entry' => $this->crm->setEntry($params),
            'set_entries' => $this->crm->setEntries($params),
            'set_relationship' => $this->crm->setRelationship($params),
            'delete_entry' => $this->crm->deleteEntry($params),
            'get_module_fields' => $this->crm->getModuleFields($params),
            'get_module_field_md5' => $this->crm->getModuleFieldMd5($params),
            'get_available_modules' => $this->crm->getAvailableModules($params),
            'get_user_id' => $this->crm->getUserId($params),
            'get_user_team_id' => $this->crm->getUserTeamId($params),
            'seamless_login' => $this->crm->seamlessLogin($params),
            'is_loopback_available' => $this->crm->isLoopbackAvailable($params),
            default => throw new \BadMethodCallException("Unknown method: $method"),
        };
    }

    private function buildResponse(string $elementName, mixed $data): string
    {
        $xmlData = $this->arrayToXml($data, $elementName);

        return '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        ' . $xmlData . '
    </soap:Body>
</soap:Envelope>';
    }

    private function arrayToXml(mixed $data, string $rootName): string
    {
        if (is_null($data)) {
            return '<' . $rootName . ' xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>';
        }

        if (is_scalar($data)) {
            return '<' . $rootName . '>' . htmlspecialchars((string)$data, ENT_XML1) . '</' . $rootName . '>';
        }

        if (is_array($data)) {
            $xml = '<' . $rootName . '>';

            foreach ($data as $key => $value) {
                $keyName = is_numeric($key) ? 'item' : $key;
                $xml .= $this->arrayToXml($value, $keyName);
            }

            $xml .= '</' . $rootName . '>';
            return $xml;
        }

        return '<' . $rootName . '>' . htmlspecialchars((string)$data, ENT_XML1) . '</' . $rootName . '>';
    }

    private function fault(string $code, string $message): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <soap:Fault>
            <faultcode>soap:' . $code . '</faultcode>
            <faultstring>' . htmlspecialchars($message, ENT_XML1) . '</faultstring>
        </soap:Fault>
    </soap:Body>
</soap:Envelope>';
    }

    public function getCrmController(): CRMController
    {
        return $this->crm;
    }

    public function getAuthHandler(): AuthHandler
    {
        return $this->auth;
    }
}