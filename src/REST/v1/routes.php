<?php

declare(strict_types=1);

namespace Ksf\HRM\Api;

use Slim\Routing\RouteCollectorProxy;
use Ksf\HRM\Repository\EmployeeRepository;
use Ksf\HRM\Controller\Api\EmployeeController;

return function (RouteCollectorProxy $app): void {
    $employeeRepo = new EmployeeRepository();
    $employeeController = new EmployeeController($employeeRepo);

    $app->group('/api/v1', function (RouteCollectorProxy $group) use ($employeeController) {
        $group->get('/employees', [$employeeController, 'listEmployees']);
        $group->get('/employees/{id:\d+}', [$employeeController, 'getEmployee']);
        $group->post('/employees', [$employeeController, 'createEmployee']);
        $group->put('/employees/{id:\d+}', [$employeeController, 'updateEmployee']);
        $group->delete('/employees/{id:\d+}', [$employeeController, 'deleteEmployee']);
    });
};