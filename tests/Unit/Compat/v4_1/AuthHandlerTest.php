<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\Compat\v4_1;

use Ksfraser\Compat\v4_1\AuthHandler;
use PHPUnit\Framework\TestCase;

class AuthHandlerTest extends TestCase
{
    private AuthHandler $auth;

    protected function setUp(): void
    {
        $this->auth = new AuthHandler();
    }

    public function testLoginWithValidCredentials(): void
    {
        $result = $this->auth->login([
            'user_auth' => [
                'user_name' => 'admin',
                'password' => '',
                'pass_clear' => 'admin123',
            ],
            'application_name' => 'Test App',
        ]);

        $this->assertArrayHasKey('id', $result);
        $this->assertNotEmpty($result['id']);
        $this->assertEquals('Users', $result['module_name']);
        $this->assertArrayHasKey('name_value_list', $result);
    }

    public function testLoginWithHermesCredentials(): void
    {
        $result = $this->auth->login([
            'user_auth' => [
                'user_name' => 'ksfii_hermes',
                'password' => '',
                'pass_clear' => 'hermes_agent',
            ],
            'application_name' => 'Hermes Agent',
        ]);

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('Users', $result['module_name']);
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->expectException(\Exception::class);
        
        $this->auth->login([
            'user_auth' => [
                'user_name' => 'admin',
                'password' => '',
                'pass_clear' => 'wrong_password',
            ],
        ]);
    }

    public function testLoginWithEmptyUsername(): void
    {
        $this->expectException(\Exception::class);
        
        $this->auth->login([
            'user_auth' => [
                'user_name' => '',
                'pass_clear' => 'password',
            ],
        ]);
    }

    public function testValidateSession(): void
    {
        $login = $this->auth->login([
            'user_auth' => [
                'user_name' => 'admin',
                'pass_clear' => 'admin123',
            ],
        ]);

        $sessionId = $login['id'];
        $user = $this->auth->validateSession($sessionId);

        $this->assertNotNull($user);
        $this->assertEquals('admin', $user['username']);
    }

    public function testValidateInvalidSession(): void
    {
        $user = $this->auth->validateSession('invalid_session_id');
        $this->assertNull($user);
    }

    public function testLogout(): void
    {
        $login = $this->auth->login([
            'user_auth' => [
                'user_name' => 'admin',
                'pass_clear' => 'admin123',
            ],
        ]);

        $sessionId = $login['id'];
        $result = $this->auth->logout($sessionId);

        $this->assertTrue($result);
        $this->assertNull($this->auth->validateSession($sessionId));
    }

    public function testTokenGeneration(): void
    {
        $user = ['id' => 1, 'username' => 'testuser'];
        $token = $this->auth->generateToken($user);

        $this->assertNotEmpty($token);
        $this->assertCount(3, explode('.', $token));
    }

    public function testTokenValidation(): void
    {
        $user = ['id' => 1, 'username' => 'testuser'];
        $token = $this->auth->generateToken($user);

        $validated = $this->auth->validateToken($token);

        $this->assertNotNull($validated);
        $this->assertEquals(1, $validated['id']);
        $this->assertEquals('testuser', $validated['username']);
    }

    public function testInvalidTokenValidation(): void
    {
        $result = $this->auth->validateToken('invalid.token.here');
        $this->assertNull($result);
    }
}