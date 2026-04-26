<?php

declare(strict_types=1);

namespace Ksfraser\FA\API\REST;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Ksfraser\HRM\Repository\EmployeeRepository;
use Ksfraser\HRM\Entity\Employee;

class EmployeeController
{
    private EmployeeRepository $repository;

    public function __construct(?EmployeeRepository $repository = null)
    {
        $this->repository = $repository ?? new EmployeeRepository();
    }

    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $filters = [];
        
        if (isset($params['status'])) {
            $filters['status'] = $params['status'];
        }
        if (isset($params['department'])) {
            $filters['department'] = $params['department'];
        }

        $employees = $this->repository->findAll($filters);

        $data = array_map(fn(Employee $e) => $this->toArray($e), $employees);

        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $data,
            'total' => count($data),
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int)$args['id'];
        $employee = $this->repository->findById($id);

        if (!$employee) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Employee not found',
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $this->toArray($employee),
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Invalid JSON',
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $employee = new Employee();
        $employee->setFirstName($data['first_name'] ?? '');
        $employee->setLastName($data['last_name'] ?? '');
        $employee->setEmail($data['email'] ?? null);
        $employee->setStatus($data['status'] ?? Employee::STATUS_ACTIVE);

        $saved = $this->repository->save($employee);

        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => ['id' => $saved->getId()],
        ]));

        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int)$args['id'];
        $employee = $this->repository->findById($id);

        if (!$employee) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Employee not found',
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $data = json_decode($request->getBody()->getContents(), true);

        foreach (['first_name', 'last_name', 'email', 'department', 'job_title', 'status'] as $field) {
            if (isset($data[$field])) {
                $method = 'set' . str_replace('_', '', ucwords($field, '_'));
                if (method_exists($employee, $method)) {
                    $employee->$method($data[$field]);
                }
            }
        }

        $this->repository->save($employee);

        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => ['id' => $employee->getId()],
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int)$args['id'];
        
        if (!$this->repository->findById($id)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Employee not found',
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $this->repository->delete($id);

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Employee deleted',
        ]));

        return $response->withHeader('Content-Type', 'application/json');
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