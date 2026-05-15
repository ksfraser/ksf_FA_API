<?php

declare(strict_types=1);

namespace Ksfraser\Compat\v4_1;

class RestHandler
{
    private CRMController $crm;
    private AuthHandler $auth;
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? [];
        $this->auth = new AuthHandler($this->config['encryption_key'] ?? '');
        $this->crm = new CRMController($this->auth);
    }

    public function handle(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        try {
            $params = $this->parseRequest();
            $response = $this->processRequest($params);

            if (isset($response['number']) && $response['number'] > 0) {
                http_response_code(400);
            }

            echo json_encode($response);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'name' => 'Server Error',
                'description' => $e->getMessage(),
                'number' => 1,
            ]);
        }
    }

    private function parseRequest(): array
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $rawInput = file_get_contents('php://input');

        if ($method === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
                $post = $_POST;
                $restData = json_decode($post['rest_data'] ?? '{}', true);
                unset($post['rest_data']);

                return array_merge($post, $restData);
            }

            if (strpos($contentType, 'application/json') !== false) {
                return json_decode($rawInput, true) ?? [];
            }

            parse_str($rawInput, $parsed);
            if (isset($parsed['rest_data'])) {
                $parsed['rest_data'] = json_decode($parsed['rest_data'], true);
            }
            return $parsed;
        }

        if ($method === 'GET') {
            return $_GET;
        }

        return [];
    }

    private function processRequest(array $params): array
    {
        $method = $params['method'] ?? '';

        if (empty($method)) {
            return [
                'name' => 'Invalid Request',
                'description' => 'Method parameter is required',
                'number' => 100,
            ];
        }

        try {
            return $this->crm->handleRequest($method, $params);
        } catch (\InvalidArgumentException $e) {
            return [
                'name' => 'Invalid Request',
                'description' => $e->getMessage(),
                'number' => 10,
            ];
        } catch (\BadMethodCallException $e) {
            return [
                'name' => 'Method Not Found',
                'description' => $e->getMessage(),
                'number' => 20,
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Error',
                'description' => $e->getMessage(),
                'number' => 99,
            ];
        }
    }

    public function getCrmController(): CRMController
    {
        return $this->crm;
    }

    public function getAuthHandler(): AuthHandler
    {
        return $this->auth;
    }
}