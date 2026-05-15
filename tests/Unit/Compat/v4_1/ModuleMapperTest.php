<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\Compat\v4_1;

use Ksfraser\Compat\v4_1\ModuleMapper;
use PHPUnit\Framework\TestCase;

class ModuleMapperTest extends TestCase
{
    private ModuleMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = ModuleMapper::getInstance();
    }

    public function testGetMappingForAccounts(): void
    {
        $mapping = $this->mapper->getMapping('Accounts');

        $this->assertNotNull($mapping);
        $this->assertEquals('ksf_CRM', $mapping['ksf_module']);
        $this->assertEquals('Customer', $mapping['entity']);
        $this->assertArrayHasKey('name', $mapping['fields']);
    }

    public function testGetMappingForContacts(): void
    {
        $mapping = $this->mapper->getMapping('Contacts');

        $this->assertNotNull($mapping);
        $this->assertEquals('Contact', $mapping['entity']);
    }

    public function testIsKnownModule(): void
    {
        $this->assertTrue($this->mapper->isKnownModule('Accounts'));
        $this->assertTrue($this->mapper->isKnownModule('Contacts'));
        $this->assertTrue($this->mapper->isKnownModule('Leads'));
        $this->assertTrue($this->mapper->isKnownModule('Opportunities'));
        $this->assertTrue($this->mapper->isKnownModule('Cases'));
        $this->assertFalse($this->mapper->isKnownModule('UnknownModule'));
    }

    public function testGetSupportedModules(): void
    {
        $modules = $this->mapper->getSupportedModules();

        $this->assertContains('Accounts', $modules);
        $this->assertContains('Contacts', $modules);
        $this->assertContains('Leads', $modules);
        $this->assertContains('Opportunities', $modules);
        $this->assertContains('Cases', $modules);
        $this->assertGreaterThan(10, count($modules));
    }

    public function testToCrmFormat(): void
    {
        $ksfData = [
            'debtor_no' => '12345',
            'name' => 'Test Company',
            'phone' => '555-1234',
            'email' => 'test@example.com',
        ];

        $crmData = $this->mapper->toCrmFormat('Accounts', $ksfData);

        $this->assertEquals('Test Company', $crmData['name']);
        $this->assertEquals('555-1234', $crmData['phone_office']);
        $this->assertEquals('test@example.com', $crmData['email1']);
    }

    public function testFromCrmFormat(): void
    {
        $crmData = [
            'name' => 'Test Company',
            'phone_office' => '555-1234',
            'email1' => 'test@example.com',
        ];

        $ksfData = $this->mapper->fromCrmFormat('Accounts', $crmData);

        $this->assertEquals('Test Company', $ksfData['name']);
        $this->assertEquals('555-1234', $ksfData['phone']);
        $this->assertEquals('test@example.com', $ksfData['email']);
    }

    public function testGetKsfModule(): void
    {
        $this->assertEquals('ksf_CRM', $this->mapper->getKsfModule('Accounts'));
        $this->assertEquals('ksf_SupportTickets', $this->mapper->getKsfModule('Cases'));
        $this->assertEquals('ksf_HRM', $this->mapper->getKsfModule('Users'));
        $this->assertNull($this->mapper->getKsfModule('NonExistent'));
    }

    public function testGetEntity(): void
    {
        $this->assertEquals('Customer', $this->mapper->getEntity('Accounts'));
        $this->assertEquals('Contact', $this->mapper->getEntity('Contacts'));
        $this->assertEquals('Lead', $this->mapper->getEntity('Leads'));
        $this->assertNull($this->mapper->getEntity('NonExistent'));
    }
}