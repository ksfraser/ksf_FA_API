<?php

declare(strict_types=1);

namespace Ksfraser\SOAP;

use Ksfraser\HRM\Repository\EmployeeRepository;
use Ksfraser\HRM\Entity\Employee;

class EmployeeSoapService
{
    private EmployeeRepository $repository;

    public function __construct(?EmployeeRepository $repository = null)
    {
        $this->repository = $repository ?? new EmployeeRepository();
    }

    public function getEmployee(int $id): ?array
    {
        $employee = $this->repository->findById($id);
        return $employee ? $this->toArray($employee) : null;
    }

    public function getEmployeeByEmail(string $email): ?array
    {
        $employee = $this->repository->findByEmail($email);
        return $employee ? $this->toArray($employee) : null;
    }

    public function listEmployees(?string $status = null): array
    {
        $filters = $status ? ['status' => $status] : [];
        $employees = $this->repository->findAll($filters);
        return array_map([$this, 'toArray'], $employees);
    }

    public function createEmployee(array $data): array
    {
        $employee = new Employee();
        $employee->setFirstName($data['first_name'] ?? '');
        $employee->setLastName($data['last_name'] ?? '');
        $employee->setEmail($data['email'] ?? null);
        $employee->setStatus($data['status'] ?? Employee::STATUS_ACTIVE);

        $saved = $this->repository->save($employee);
        return $this->toArray($saved);
    }

    public function updateEmployee(int $id, array $data): ?array
    {
        $employee = $this->repository->findById($id);
        if (!$employee) {
            return null;
        }

        foreach ($data as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($employee, $method)) {
                $employee->$method($value);
            }
        }

        $this->repository->save($employee);
        return $this->toArray($employee);
    }

    public function deleteEmployee(int $id): bool
    {
        return $this->repository->delete($id);
    }

    private function toArray(Employee $employee): array
    {
        return [
            'id' => $employee->getId(),
            'employee_number' => $employee->getEmployeeNumber(),
            'first_name' => $employee->getFirstName(),
            'last_name' => $employee->getLastName(),
            'email' => $employee->getEmail(),
            'department' => $employee->getDepartment(),
            'status' => $employee->getStatus(),
        ];
    }
}