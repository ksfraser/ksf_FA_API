<?php

declare(strict_types=1);

namespace Ksfraser\Compat\v4_1;

use Exception;

class AuthHandler
{
    private array $sessions = [];
    private array $tokens = [];
    private string $sessionPrefix = 'sess_';
    private int $sessionLifetime = 3600;
    private string $encKey;

    public function __construct(string $encryptionKey = '')
    {
        $this->encKey = $encryptionKey ?: getenv('CRM_API_KEY') ?: 'default_encryption_key_please_change';
    }

    private function throwAuthException(string $message): void
    {
        throw new class($message) extends Exception {
            public function __construct(string $message) {
                parent::__construct($message, 401);
            }
        };
    }

    public function login(array $credentials): array
    {
        $userAuth = $credentials['user_auth'] ?? [];
        $applicationName = $credentials['application_name'] ?? 'CRM API';
        $nameValueList = $credentials['name_value_list'] ?? [];

        $username = $userAuth['user_name'] ?? '';
        $password = $this->decryptPassword($userAuth['password'] ?? '', $userAuth['pass_clear'] ?? '');
        $version = $userAuth['version'] ?? '1';

        if (empty($username)) {
            $this->throwAuthException('Username is required');
        }

        if (empty($password)) {
            $this->throwAuthException('Password is required');
        }

        $user = $this->validateCredentials($username, $password);

        if (!$user) {
            $this->throwAuthException('Invalid username or password');
        }

        $sessionId = $this->createSession($user);

        return [
            'id' => $sessionId,
            'module_name' => 'Users',
            'name_value_list' => [
                'user_id' => ['name' => 'user_id', 'value' => $user['id']],
                'user_name' => ['name' => 'user_name', 'value' => $user['username']],
                'user_language' => ['name' => 'user_language', 'value' => 'en_US'],
                'user_currency_id' => ['name' => 'user_currency_id', 'value' => '-99'],
                'user_currency_name' => ['name' => 'user_currency_name', 'value' => 'USD'],
            ],
        ];
    }

    public function validateSession(string $sessionId): ?array
    {
        $sessionKey = $this->sessionPrefix . $sessionId;

        if (!isset($this->sessions[$sessionKey])) {
            if ($this->isExternalSession($sessionId)) {
                return $this->validateExternalSession($sessionId);
            }
            return null;
        }

        $session = $this->sessions[$sessionKey];

        if ($session['expires'] < time()) {
            unset($this->sessions[$sessionKey]);
            return null;
        }

        $session['expires'] = time() + $this->sessionLifetime;
        $this->sessions[$sessionKey] = $session;

        return $session['user'];
    }

    public function logout(string $sessionId): bool
    {
        $sessionKey = $this->sessionPrefix . $sessionId;
        unset($this->sessions[$sessionKey]);
        unset($this->tokens[$sessionId]);
        return true;
    }

    public function getUserFromSession(string $sessionId): ?array
    {
        return $this->validateSession($sessionId);
    }

    public function generateToken(array $user): string
    {
        $payload = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'exp' => time() + 86400,
            'iat' => time(),
        ];

        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = base64_encode(json_encode($payload));
        $signature = $this->signData($header . '.' . $payload);

        return $header . '.' . $payload . '.' . $signature;
    }

    public function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        if ($this->signData($header . '.' . $payload) !== $signature) {
            return null;
        }

        $data = json_decode(base64_decode($payload), true);
        if (!$data || $data['exp'] < time()) {
            return null;
        }

        return [
            'id' => $data['user_id'],
            'username' => $data['username'],
        ];
    }

    public function refreshSession(string $sessionId): array
    {
        $user = $this->validateSession($sessionId);
        if (!$user) {
            $this->throwAuthException('Invalid or expired session');
        }

        $this->logout($sessionId);
        return $this->login([
            'user_auth' => [
                'user_name' => $user['username'],
                'password' => '',
                'pass_clear' => '',
            ],
        ]);
    }

    private function createSession(array $user): string
    {
        $sessionId = bin2hex(random_bytes(32));
        $sessionKey = $this->sessionPrefix . $sessionId;

        $this->sessions[$sessionKey] = [
            'user' => $user,
            'created' => time(),
            'expires' => time() + $this->sessionLifetime,
        ];

        return $sessionId;
    }

    private function validateCredentials(string $username, string $password): ?array
    {
        $faIncludesPath = __DIR__ . '/../../../../includes/db.inc';
        if (!function_exists('db_query') || !defined('TB_PREF') || !is_readable($faIncludesPath)) {
            return $this->mockValidateCredentials($username, $password);
        }

        global $db;
        include_once $faIncludesPath;

        $sql = "SELECT id, user_id, real_name, password, cryptmd5, is_admin 
                FROM " . TB_PREF . "users 
                WHERE user_id = " . db_escape($username) . " AND inactive = 0";

        $result = @db_query($sql);
        if (!$result) {
            return null;
        }

        $user = @db_fetch_assoc($result);
        if (!$user) {
            return null;
        }

        $storedHash = $user['cryptmd5'] ?? $user['password'] ?? '';
        $inputHash = md5($password);

        if ($storedHash === $inputHash || password_verify($password, $storedHash)) {
            return [
                'id' => $user['id'] ?? $user['user_id'],
                'username' => $user['user_id'],
                'realname' => $user['real_name'] ?? $username,
                'is_admin' => $user['is_admin'] ?? 0,
            ];
        }

        return null;
    }

    private function mockValidateCredentials(string $username, string $password): ?array
    {
        $validUsers = [
            'admin' => ['id' => 1, 'username' => 'admin', 'realname' => 'Administrator', 'is_admin' => 1],
            'ksfii_hermes' => ['id' => 2, 'username' => 'ksfii_hermes', 'realname' => 'Hermes Agent', 'is_admin' => 0],
        ];

        $mockPasswords = [
            'admin' => 'admin123',
            'ksfii_hermes' => 'hermes_agent',
        ];

        if (isset($validUsers[$username]) && ($mockPasswords[$username] ?? '') === $password) {
            return $validUsers[$username];
        }

        return null;
    }

    private function decryptPassword(string $encrypted, string $clear): string
    {
        if (!empty($clear)) {
            return $clear;
        }

        if (!empty($encrypted) && strlen($encrypted) === 32) {
            return $encrypted;
        }

        return '';
    }

    private function signData(string $data): string
    {
        return hash_hmac('sha256', $data, $this->encKey);
    }

    private function isExternalSession(string $sessionId): bool
    {
        return strlen($sessionId) > 64;
    }

    private function validateExternalSession(string $sessionId): ?array
    {
        return null;
    }

    public function setSessionLifetime(int $seconds): void
    {
        $this->sessionLifetime = $seconds;
    }

    public function cleanupExpiredSessions(): int
    {
        $now = time();
        $cleaned = 0;

        foreach ($this->sessions as $key => $session) {
            if ($session['expires'] < $now) {
                unset($this->sessions[$key]);
                $cleaned++;
            }
        }

        return $cleaned;
    }
}