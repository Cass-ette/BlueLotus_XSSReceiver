<?php

declare(strict_types=1);

namespace App\Service;

use PDO;

class RecordService
{
    public function __construct(
        private PDO $pdo,
        private GeoIpService $geoIp,
    ) {}

    public function create(array $data): int
    {
        // Lookup GeoIP if not set
        if (empty($data['location']) && !empty($data['user_ip'])) {
            $data['location'] = $this->geoIp->lookup($data['user_ip']);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO records (user_ip, user_port, protocol, request_method, request_uri,
             request_time, location, headers_data, get_data, decoded_get_data, post_data,
             decoded_post_data, cookie_data, decoded_cookie_data, keepsession)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $data['user_ip'],
            $data['user_port'] ?? null,
            $data['protocol'] ?? null,
            $data['request_method'] ?? null,
            $data['request_uri'] ?? null,
            $data['request_time'],
            $data['location'] ?? null,
            $data['headers_data'] ?? null,
            $data['get_data'] ?? null,
            $data['decoded_get_data'] ?? null,
            $data['post_data'] ?? null,
            $data['decoded_post_data'] ?? null,
            $data['cookie_data'] ?? null,
            $data['decoded_cookie_data'] ?? null,
            $data['keepsession'] ?? 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function list(int $page = 1, int $limit = 25, string $search = ''): array
    {
        $offset = ($page - 1) * $limit;

        if ($search !== '') {
            $searchParam = '%' . $search . '%';
            $countStmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM records WHERE user_ip LIKE ? OR location LIKE ? OR get_data LIKE ? OR post_data LIKE ? OR cookie_data LIKE ?'
            );
            $countStmt->execute([$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
            $total = (int) $countStmt->fetchColumn();

            $stmt = $this->pdo->prepare(
                'SELECT * FROM records WHERE user_ip LIKE ? OR location LIKE ? OR get_data LIKE ? OR post_data LIKE ? OR cookie_data LIKE ?
                 ORDER BY request_time DESC LIMIT ? OFFSET ?'
            );
            $stmt->execute([$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $limit, $offset]);
        } else {
            $total = (int) $this->pdo->query('SELECT COUNT(*) FROM records')->fetchColumn();

            $stmt = $this->pdo->prepare('SELECT * FROM records ORDER BY request_time DESC LIMIT ? OFFSET ?');
            $stmt->execute([$limit, $offset]);
        }

        $records = $stmt->fetchAll();

        // Parse JSON fields
        foreach ($records as &$record) {
            $record = $this->parseJsonFields($record);
        }

        return [
            'data' => $records,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => (int) ceil($total / $limit),
        ];
    }

    public function get(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM records WHERE id = ?');
        $stmt->execute([$id]);
        $record = $stmt->fetch();

        if (!$record) {
            return false;
        }

        return $this->parseJsonFields($record);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM records WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function clear(): bool
    {
        $this->pdo->exec('DELETE FROM records');
        return true;
    }

    public function getLatestId(): int
    {
        $result = $this->pdo->query('SELECT MAX(id) FROM records')->fetchColumn();
        return (int) ($result ?? 0);
    }

    private function parseJsonFields(array $record): array
    {
        $jsonFields = ['headers_data', 'get_data', 'decoded_get_data', 'post_data', 'decoded_post_data', 'cookie_data', 'decoded_cookie_data'];
        foreach ($jsonFields as $field) {
            if (isset($record[$field]) && is_string($record[$field])) {
                $decoded = json_decode($record[$field], true);
                $record[$field] = $decoded !== null ? $decoded : $record[$field];
            }
        }
        return $record;
    }
}
