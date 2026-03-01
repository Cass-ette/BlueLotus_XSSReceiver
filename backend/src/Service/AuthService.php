<?php

declare(strict_types=1);

namespace App\Service;

use Firebase\JWT\JWT;
use PDO;

class AuthService
{
    public function __construct(
        private PDO $pdo,
        private array $settings,
    ) {}

    public function login(string $username, string $password, string $ip): array|false
    {
        // Check IP ban
        if ($this->isIpBanned($ip)) {
            return false;
        }

        $stmt = $this->pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->recordFailedAttempt($ip);
            return false;
        }

        // Clear failed attempts on success
        $this->clearFailedAttempts($ip);

        $token = $this->generateToken($user['id'], $user['username']);

        return [
            'token' => $token,
            'username' => $user['username'],
        ];
    }

    public function changePassword(int $userId, string $oldPassword, string $newPassword): bool
    {
        $stmt = $this->pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
            return false;
        }

        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        return $stmt->execute([password_hash($newPassword, PASSWORD_BCRYPT), $userId]);
    }

    private function generateToken(int $userId, string $username): string
    {
        $payload = [
            'iss' => 'bluelotus-xss',
            'sub' => $userId,
            'username' => $username,
            'iat' => time(),
            'exp' => time() + $this->settings['jwt_expiry'],
        ];

        return JWT::encode($payload, $this->settings['jwt_secret'], 'HS256');
    }

    private function isIpBanned(string $ip): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT attempts, banned_at FROM banned_ips WHERE ip = ?'
        );
        $stmt->execute([$ip]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        if ($row['attempts'] >= $this->settings['max_login_attempts']) {
            $bannedAt = strtotime($row['banned_at']);
            if (time() - $bannedAt < $this->settings['ban_duration']) {
                return true;
            }
            // Ban expired, clear it
            $this->clearFailedAttempts($ip);
        }

        return false;
    }

    private function recordFailedAttempt(string $ip): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO banned_ips (ip, attempts, banned_at) VALUES (?, 1, CURRENT_TIMESTAMP)
             ON CONFLICT(ip) DO UPDATE SET attempts = attempts + 1, banned_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$ip]);
    }

    private function clearFailedAttempts(string $ip): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM banned_ips WHERE ip = ?');
        $stmt->execute([$ip]);
    }
}
