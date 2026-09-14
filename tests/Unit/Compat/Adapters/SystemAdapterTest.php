<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\Compat\Adapters;

use Ksfraser\Compat\Adapters\SystemAdapter;
use PHPUnit\Framework\TestCase;

class SystemAdapterTest extends TestCase
{
    private SystemAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = SystemAdapter::getInstance();
    }

    public function testGetSupportedSystems(): void
    {
        $systems = $this->adapter->getSupportedSystems();

        $this->assertContains('suitecrm', $systems);
        $this->assertContains('sugarcrm', $systems);
        $this->assertContains('vtiger', $systems);
        $this->assertContains('orangehrm', $systems);
        $this->assertContains('odoo', $systems);
        $this->assertContains('dolibarr', $systems);
        $this->assertContains('dotproject', $systems);
        $this->assertContains('openproject', $systems);
        $this->assertContains('libreproject', $systems);
    }

    public function testGetConfigForSuiteCRM(): void
    {
        $config = $this->adapter->getConfig(SystemAdapter::SUITECRM);

        $this->assertEquals('v4_1', $config['version']);
        $this->assertEquals('rest', $config['protocol']);
        $this->assertEquals('Y-m-d H:i:s', $config['date_format']);
    }

    public function testGetConfigForOdoo(): void
    {
        $config = $this->adapter->getConfig(SystemAdapter::ODOO);

        $this->assertEquals('13', $config['version']);
        $this->assertEquals('xmlrpc', $config['protocol']);
        $this->assertArrayHasKey('module_overrides', $config);
    }

    public function testGetConfigForOrangeHRM(): void
    {
        $config = $this->adapter->getConfig(SystemAdapter::ORANGEHRM);

        $this->assertEquals('o365', $config['version']);
        $this->assertEquals('rest', $config['protocol']);
        $this->assertArrayHasKey('field_overrides', $config);
    }

    public function testTranslateModule(): void
    {
        $this->assertEquals('Accounts', $this->adapter->translateModule('Accounts'));
        $this->assertEquals('Accounts', $this->adapter->translateModule('res.partner'));
        $this->assertEquals('Contacts', $this->adapter->translateModule('contacts'));
    }

    public function testTranslateFieldToKsf(): void
    {
        $adapter = SystemAdapter::getInstance(SystemAdapter::ORANGEHRM);

        $this->assertEquals('first_name', $adapter->translateField('Employees', 'emp_firstname', 'to_ksf'));
        $this->assertEquals('last_name', $adapter->translateField('Employees', 'emp_lastname', 'to_ksf'));
        $this->assertEquals('email', $adapter->translateField('Employees', 'emp_work_email', 'to_ksf'));
    }

    public function testSupportsProtocol(): void
    {
        $restAdapter = SystemAdapter::getInstance(SystemAdapter::SUITECRM);
        $this->assertTrue($restAdapter->supportsProtocol('rest'));
        $this->assertFalse($restAdapter->supportsProtocol('xmlrpc'));

        $xmlrpcAdapter = SystemAdapter::getInstance(SystemAdapter::ODOO);
        $this->assertTrue($xmlrpcAdapter->supportsProtocol('xmlrpc'));
        $this->assertFalse($xmlrpcAdapter->supportsProtocol('rest'));
    }

    public function testGetSystemInfo(): void
    {
        $info = $this->adapter->getSystemInfo(SystemAdapter::SUITECRM);

        $this->assertArrayHasKey('system', $info);
        $this->assertArrayHasKey('version', $info);
        $this->assertArrayHasKey('protocol', $info);
        $this->assertArrayHasKey('modules', $info);
        $this->assertEquals('suitecrm', $info['system']);
    }

    public function testDetectSystemFromOrangeHRM(): void
    {
        $requestData = [
            'application' => 'OrangeHRM',
            'emp_firstname' => 'John',
        ];

        $detected = $this->adapter->detectSystem($requestData);
        $this->assertEquals(SystemAdapter::ORANGEHRM, $detected);
    }

    public function testDetectSystemFromSource(): void
    {
        $requestData = [
            '__source' => 'odoo',
            'key' => 'test_key',
        ];

        $detected = $this->adapter->detectSystem($requestData);
        $this->assertEquals(SystemAdapter::ODOO, $detected);
    }

    public function testDetectSystemDefaultsToSuiteCRM(): void
    {
        $requestData = [
            'method' => 'get_entry',
            'session' => 'test123',
        ];

        $detected = $this->adapter->detectSystem($requestData);
        $this->assertEquals(SystemAdapter::SUITECRM, $detected);
    }
}