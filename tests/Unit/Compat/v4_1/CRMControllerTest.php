<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\Compat\v4_1;

use Ksfraser\Compat\v4_1\AuthHandler;
use Ksfraser\Compat\v4_1\CRMController;
use PHPUnit\Framework\TestCase;

class CRMControllerTest extends TestCase
{
    private CRMController $crm;
    private AuthHandler $auth;

    protected function setUp(): void
    {
        $this->auth = new AuthHandler();
        $this->crm = new CRMController($this->auth);
    }

    public function testLogin(): void
    {
        $result = $this->crm->login([
            'user_auth' => [
                'user_name' => 'admin',
                'pass_clear' => 'admin123',
            ],
        ]);

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('Users', $result['module_name']);
    }

    public function testGetAvailableModules(): void
    {
        $login = $this->auth->login([
            'user_auth' => [
                'user_name' => 'admin',
                'pass_clear' => 'admin123',
            ],
        ]);

        $result = $this->crm->getAvailableModules([
            'session' => $login['id'],
        ]);

        $this->assertArrayHasKey('modules', $result);
        $this->assertNotEmpty($result['modules']);

        $moduleNames = array_column($result['modules'], 'module_key');
        $this->assertContains('Accounts', $moduleNames);
        $this->assertContains('Contacts', $moduleNames);
        $this->assertContains('Leads', $moduleNames);
    }

    public function testGetModuleFields(): void
    {
        $login = $this->auth->login([
            'user_auth' => [
                'user_name' => 'admin',
                'pass_clear' => 'admin123',
            ],
        ]);

        $result = $this->crm->getModuleFields([
            'session' => $login['id'],
            'module_name' => 'Accounts',
        ]);

        $this->assertArrayHasKey('module_name', $result);
        $this->assertEquals('Accounts', $result['module_name']);
        $this->assertArrayHasKey('module_fields', $result);
        $this->assertNotEmpty($result['module_fields']);
    }

    public function testGetEntryList(): void
    {
        $login = $this->auth->login([
            'user_auth' => [
                'user_name' => 'admin',
                'pass_clear' => 'admin123',
            ],
        ]);

        $result = $this->crm->getEntryList([
            'session' => $login['id'],
            'module_name' => 'Accounts',
            'max_results' => 10,
        ]);

        $this->assertArrayHasKey('result_count', $result);
        $this->assertArrayHasKey('entry_list', $result);
        $this->assertIsArray($result['entry_list']);
    }

    public function testGetEntryListWithNoResults(): void
    {
        $login = $this->auth->login([
            'user_auth' => [
                'user_name' => 'admin',
                'pass_clear' => 'admin123',
            ],
        ]);

        $result = $this->crm->getEntryList([
            'session' => $login['id'],
            'module_name' => 'Opportunities',
            'max_results' => 10,
        ]);

        $this->assertArrayHasKey('result_count', $result);
        $this->assertArrayHasKey('entry_list', $result);
    }

    public function testGetEntriesCount(): void
    {
        $login = $this->auth->login([
            'user_auth' => [
                'user_name' => 'admin',
                'pass_clear' => 'admin123',
            ],
        ]);

        $result = $this->crm->getEntriesCount([
            'session' => $login['id'],
            'module_name' => 'Accounts',
        ]);

        $this->assertArrayHasKey('result_count', $result);
    }

    public function testGetUserId(): void
    {
        $login = $this->auth->login([
            'user_auth' => [
                'user_name' => 'admin',
                'pass_clear' => 'admin123',
            ],
        ]);

        $result = $this->crm->getUserId([
            'session' => $login['id'],
        ]);

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals(1, $result['id']);
    }

    public function testLogout(): void
    {
        $login = $this->auth->login([
            'user_auth' => [
                'user_name' => 'admin',
                'pass_clear' => 'admin123',
            ],
        ]);

        $result = $this->crm->logout([
            'session' => $login['id'],
        ]);

        $this->assertArrayHasKey('id', $result);
    }

    public function testIsLoopbackAvailable(): void
    {
        $result = $this->crm->isLoopbackAvailable([]);

        $this->assertArrayHasKey('loopback_available', $result);
        $this->assertTrue($result['loopback_available']);
    }

    public function testGetUserTeamId(): void
    {
        $login = $this->auth->login([
            'user_auth' => [
                'user_name' => 'admin',
                'pass_clear' => 'admin123',
            ],
        ]);

        $result = $this->crm->getUserTeamId([
            'session' => $login['id'],
        ]);

        $this->assertArrayHasKey('team_id', $result);
        $this->assertEquals('1', $result['team_id']);
    }

    public function testSeamlessLogin(): void
    {
        $result = $this->crm->seamlessLogin([
            'user_auth' => [
                'user_name' => 'admin',
                'pass_clear' => 'admin123',
            ],
        ]);

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('Users', $result['module_name']);
    }

    public function testGetModuleFieldMd5(): void
    {
        $login = $this->auth->login([
            'user_auth' => [
                'user_name' => 'admin',
                'pass_clear' => 'admin123',
            ],
        ]);

        $result = $this->crm->getModuleFieldMd5([
            'session' => $login['id'],
            'module_name' => 'Accounts',
        ]);

        $this->assertArrayHasKey('md5', $result);
    }

    public function testSetRelationship(): void
    {
        $login = $this->auth->login([
            'user_auth' => [
                'user_name' => 'admin',
                'pass_clear' => 'admin123',
            ],
        ]);

        $result = $this->crm->setRelationship([
            'session' => $login['id'],
        ]);

        $this->assertArrayHasKey('created', $result);
        $this->assertArrayHasKey('deleted', $result);
    }

    public function testSetRelationships(): void
    {
        $login = $this->auth->login([
            'user_auth' => [
                'user_name' => 'admin',
                'pass_clear' => 'admin123',
            ],
        ]);

        $result = $this->crm->setRelationships([
            'session' => $login['id'],
            'module_ids' => ['1', '2', '3'],
        ]);

        $this->assertArrayHasKey('created', $result);
        $this->assertEquals(3, $result['created']);
    }
}