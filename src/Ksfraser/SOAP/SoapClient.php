<?php

declare(strict_types=1);

namespace Ksfraser\FA\API\SOAP;

use Ksfraser\Exceptions\API\SoapConnectionException;
use Ksfraser\Exceptions\API\SoapFaultException;

class SoapClient
{
    private string $endpoint;
    private string $wsdlPath;
    private ?\SoapClient $client = null;
    private array $options;

    public function __construct(
        string $endpoint = 'https://ksfraser.com/api/soap/v1/employees',
        string $wsdlPath = __DIR__ . '/../../wsdl/KSFAPI.wsdl',
        array $options = []
    ) {
        $this->endpoint = $endpoint;
        $this->wsdlPath = $wsdlPath;
        $this->options = array_merge([
            'trace' => true,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'soap_version' => SOAP_1_1,
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
        ], $options);
    }

    public function connect(): void
    {
        if ($this->client !== null) {
            return;
        }

        if (!extension_loaded('soap')) {
            throw new SoapConnectionException('SOAP extension is not available');
        }

        try {
            $this->client = new \SoapClient($this->wsdlPath, $this->options);
        } catch (\SoapFault $e) {
            throw new SoapConnectionException(
                'Failed to connect to SOAP service: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function getEmployee(int $id): ?array
    {
        $this->connect();

        try {
            $result = $this->client->GetEmployee(['id' => $id]);
            return $this->stdToArray($result);
        } catch (\SoapFault $e) {
            throw new SoapFaultException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getEmployeeByEmail(string $email): ?array
    {
        $this->connect();

        try {
            $result = $this->client->GetEmployeeByEmail(['email' => $email]);
            return $this->stdToArray($result);
        } catch (\SoapFault $e) {
            throw new SoapFaultException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getEmployeeByNumber(string $employeeNumber): ?array
    {
        $this->connect();

        try {
            $result = $this->client->GetEmployeeByNumber(['employeeNumber' => $employeeNumber]);
            return $this->stdToArray($result);
        } catch (\SoapFault $e) {
            throw new SoapFaultException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function listEmployees(?string $status = null): array
    {
        $this->connect();

        try {
            $result = $this->client->ListEmployees(['status' => $status]);
            $data = $this->stdToArray($result);
            return $data['employee'] ?? [];
        } catch (\SoapFault $e) {
            throw new SoapFaultException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function createEmployee(array $data): array
    {
        $this->connect();

        try {
            $result = $this->client->CreateEmployee($this->prepareCreateData($data));
            return $this->stdToArray($result);
        } catch (\SoapFault $e) {
            throw new SoapFaultException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function updateEmployee(int $id, array $data): array
    {
        $this->connect();

        try {
            $data['id'] = $id;
            $result = $this->client->UpdateEmployee($this->prepareUpdateData($data));
            return $this->stdToArray($result);
        } catch (\SoapFault $e) {
            throw new SoapFaultException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function deleteEmployee(int $id): bool
    {
        $this->connect();

        try {
            $result = $this->client->DeleteEmployee(['id' => $id]);
            $data = $this->stdToArray($result);
            return $data['success'] ?? false;
        } catch (\SoapFault $e) {
            throw new SoapFaultException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getLastRequest(): ?string
    {
        return $this->client?->__getLastRequest();
    }

    public function getLastResponse(): ?string
    {
        return $this->client?->__getLastResponse();
    }

    private function stdToArray(object|array $std): array
    {
        return json_decode(json_encode($std), true) ?? [];
    }

    private function prepareCreateData(array $data): array
    {
        return [
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'email' => $data['email'] ?? null,
            'status' => $data['status'] ?? 'active',
            'department' => $data['department'] ?? null,
        ];
    }

    private function prepareUpdateData(array $data): array
    {
        $update = ['id' => $data['id']];
        $allowed = ['first_name', 'last_name', 'email', 'status', 'department', 'job_title'];

        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $update[$field] = $data[$field];
            }
        }

        return $update;
    }
}