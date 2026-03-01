<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\GeoIpService;
use App\Service\RecordService;
use PDO;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RecordController
{
    private RecordService $records;

    public function __construct(ContainerInterface $container)
    {
        $settings = $container->get('settings');
        $pdo = $container->get(PDO::class);
        $geoIp = new GeoIpService($settings['qqwry_path']);
        $this->records = new RecordService($pdo, $geoIp);
    }

    public function list(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = min(100, max(1, (int) ($params['limit'] ?? 25)));
        $search = $params['search'] ?? '';

        $result = $this->records->list($page, $limit, $search);
        return $this->json($response, $result);
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $record = $this->records->get($id);

        if ($record === false) {
            return $this->json($response, ['error' => 'Record not found'], 404);
        }

        return $this->json($response, $record);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $success = $this->records->delete($id);

        if (!$success) {
            return $this->json($response, ['error' => 'Record not found'], 404);
        }

        return $this->json($response, ['message' => 'Deleted']);
    }

    public function clear(Request $request, Response $response): Response
    {
        $this->records->clear();
        return $this->json($response, ['message' => 'All records cleared']);
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
