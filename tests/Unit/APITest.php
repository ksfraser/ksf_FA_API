<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FA\API;

use PHPUnit\Framework\TestCase;

class APITest extends TestCase
{
    public function testRESTRoutesExist(): void
    {
        $this->assertTrue(file_exists(__DIR__ . '/../../src/Ksfraser/REST/routes.php'));
    }

    public function testSOAPServiceExists(): void
    {
        $this->assertTrue(class_exists('Ksfraser\SOAP\EmployeeSoapService') || 
                         file_exists(__DIR__ . '/../../src/Ksfraser/SOAP/EmployeeSoapService.php'));
    }
}
