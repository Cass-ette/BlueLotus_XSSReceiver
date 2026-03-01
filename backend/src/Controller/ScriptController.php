<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ScriptService;
use PDO;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ScriptController
{
    private ScriptService $scripts;

    public function __construct(ContainerInterface $container)
    {
        $this->scripts = new ScriptService($container->get(PDO::class));
    }

    public function list(Request $request, Response $response): Response
    {
        $type = $request->getQueryParams()['type'] ?? 'myjs';
        if (!in_array($type, ['template', 'myjs'])) {
            return $this->json($response, ['error' => 'Invalid type'], 400);
        }

        $scripts = $this->scripts->list($type);
        return $this->json($response, $scripts);
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $script = $this->scripts->get($id);

        if ($script === false) {
            return $this->json($response, ['error' => 'Script not found'], 404);
        }

        return $this->json($response, $script);
    }

    public function create(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        $name = $body['name'] ?? '';
        $description = $body['description'] ?? '';
        $content = $body['content'] ?? '';
        $type = $body['type'] ?? 'myjs';

        if (empty($name)) {
            return $this->json($response, ['error' => 'Name is required'], 400);
        }
        if (!in_array($type, ['template', 'myjs'])) {
            return $this->json($response, ['error' => 'Invalid type'], 400);
        }

        $id = $this->scripts->create($name, $description, $content, $type);

        if ($id === false) {
            return $this->json($response, ['error' => 'Script with this name already exists'], 409);
        }

        return $this->json($response, ['id' => $id, 'message' => 'Created'], 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $body = $request->getParsedBody();
        $name = $body['name'] ?? '';
        $description = $body['description'] ?? '';
        $content = $body['content'] ?? '';

        if (empty($name)) {
            return $this->json($response, ['error' => 'Name is required'], 400);
        }

        $success = $this->scripts->update($id, $name, $description, $content);

        if (!$success) {
            return $this->json($response, ['error' => 'Script not found or name conflict'], 404);
        }

        return $this->json($response, ['message' => 'Updated']);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $success = $this->scripts->delete($id);

        if (!$success) {
            return $this->json($response, ['error' => 'Script not found'], 404);
        }

        return $this->json($response, ['message' => 'Deleted']);
    }

    public function clear(Request $request, Response $response): Response
    {
        $type = $request->getQueryParams()['type'] ?? '';
        if (!in_array($type, ['template', 'myjs'])) {
            return $this->json($response, ['error' => 'Type parameter required'], 400);
        }

        $this->scripts->clear($type);
        return $this->json($response, ['message' => 'Cleared']);
    }

    /**
     * Serve JS files publicly (for XSS payload delivery)
     */
    public function serveJs(Request $request, Response $response, array $args): Response
    {
        $name = $args['name'] ?? '';
        // Remove .js extension if present
        $name = preg_replace('/\.js$/', '', $name);

        // Try myjs first, then template
        $script = $this->scripts->getByName($name, 'myjs');
        if (!$script) {
            $script = $this->scripts->getByName($name, 'template');
        }

        if (!$script) {
            return $response->withStatus(404);
        }

        $response->getBody()->write($script['content'] ?? '');
        return $response
            ->withHeader('Content-Type', 'application/javascript; charset=utf-8')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Cache-Control', 'no-cache');
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
