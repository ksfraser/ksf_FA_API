<?php

declare(strict_types=1);

use Ksfraser\FA\API\REST\EmployeeController;
use Slim\Routing\RouteCollectorProxy;

return function (RouteCollectorProxy $app): void {
    $employeeController = new EmployeeController();

    $app->group('/api/v1', function (RouteCollectorProxy $group) use ($employeeController) {
        $group->get('/employees', [$employeeController, 'list']);
        $group->get('/employees/{id:\d+}', [$employeeController, 'get']);
        $group->post('/employees', [$employeeController, 'create']);
        $group->put('/employees/{id:\d+}', [$employeeController, 'update']);
        $group->delete('/employees/{id:\d+}', [$employeeController, 'delete']);
    });
};