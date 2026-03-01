<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use PDO;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    private AuthService $auth;

    public function __construct(ContainerInterface $container)
    {
        $settings = $container->get('settings');
        $this->auth = new AuthService($container->get(PDO::class), $settings);
    }

    public function login(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        $username = $body['username'] ?? '';
        $password = $body['password'] ?? '';

        if (empty($username) || empty($password)) {
            return $this->json($response, ['error' => 'Username and password required'], 400);
        }

        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? '127.0.0.1';
        $result = $this->auth->login($username, $password, $ip);

        if ($result === false) {
            return $this->json($response, ['error' => 'Invalid credentials or IP banned'], 401);
        }

        return $this->json($response, $result);
    }

    public function logout(Request $request, Response $response): Response
    {
        // JWT is stateless, client just discards the token
        return $this->json($response, ['message' => 'Logged out']);
    }

    public function changePassword(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        $oldPassword = $body['old_password'] ?? '';
        $newPassword = $body['new_password'] ?? '';
        $userId = (int) $request->getAttribute('user_id');

        if (empty($oldPassword) || empty($newPassword)) {
            return $this->json($response, ['error' => 'Both old and new password required'], 400);
        }

        if (strlen($newPassword) < 6) {
            return $this->json($response, ['error' => 'New password must be at least 6 characters'], 400);
        }

        $success = $this->auth->changePassword($userId, $oldPassword, $newPassword);

        if (!$success) {
            return $this->json($response, ['error' => 'Old password is incorrect'], 401);
        }

        return $this->json($response, ['message' => 'Password changed']);
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
