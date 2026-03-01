<?php

declare(strict_types=1);

namespace App\Service;

use PDO;

class ScriptService
{
    public function __construct(private PDO $pdo) {}

    public function list(string $type): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, description, type, created_at, updated_at FROM scripts WHERE type = ? ORDER BY updated_at DESC'
        );
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }

    public function get(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM scripts WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: false;
    }

    public function getByName(string $name, string $type): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM scripts WHERE name = ? AND type = ?');
        $stmt->execute([$name, $type]);
        return $stmt->fetch() ?: false;
    }

    public function create(string $name, string $description, string $content, string $type): int|false
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO scripts (name, description, content, type) VALUES (?, ?, ?, ?)'
        );

        try {
            $stmt->execute([$name, $description, $content, $type]);
            return (int) $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            // Unique constraint violation
            return false;
        }
    }

    public function update(int $id, string $name, string $description, string $content): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE scripts SET name = ?, description = ?, content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
        );

        try {
            $stmt->execute([$name, $description, $content, $id]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM scripts WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function clear(string $type): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM scripts WHERE type = ?');
        $stmt->execute([$type]);
        return true;
    }
}
