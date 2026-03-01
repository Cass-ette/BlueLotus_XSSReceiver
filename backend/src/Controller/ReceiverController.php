<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\GeoIpService;
use App\Service\MailService;
use App\Service\RecordService;
use PDO;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReceiverController
{
    private RecordService $records;
    private MailService $mail;

    public function __construct(ContainerInterface $container)
    {
        $settings = $container->get('settings');
        $pdo = $container->get(PDO::class);
        $geoIp = new GeoIpService($settings['qqwry_path']);
        $this->records = new RecordService($pdo, $geoIp);
        $this->mail = new MailService($settings['mail']);
    }

    public function receive(Request $request, Response $response): Response
    {
        $serverParams = $request->getServerParams();

        $userIp = $serverParams['REMOTE_ADDR'] ?? 'unknown';
        $userPort = $serverParams['REMOTE_PORT'] ?? 'unknown';
        $protocol = $serverParams['SERVER_PROTOCOL'] ?? 'unknown';
        $requestMethod = $serverParams['REQUEST_METHOD'] ?? 'unknown';
        $requestUri = $serverParams['REQUEST_URI'] ?? 'unknown';
        $requestTime = (int) ($serverParams['REQUEST_TIME'] ?? time());

        $getData = $request->getQueryParams();
        $postData = $request->getParsedBody() ?? [];
        if (!is_array($postData)) {
            $postData = [];
        }
        $cookieData = $request->getCookieParams();

        // Headers
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        // Try Base64 decode
        $decodedGetData = $this->tryBase64Decode($getData);
        $decodedPostData = $this->tryBase64Decode($postData);
        $decodedCookieData = $this->tryBase64Decode($cookieData);

        // XSS filter all input
        $sanitize = function ($val) {
            return is_string($val) ? addslashes(htmlspecialchars($val, ENT_QUOTES, 'UTF-8')) : $val;
        };
        $sanitizeArr = function (array $arr) use ($sanitize) {
            $result = [];
            foreach ($arr as $k => $v) {
                $result[$sanitize($k)] = $sanitize($v);
            }
            return $result;
        };

        // Determine keepsession
        $keepsession = 0;
        foreach ([$getData, $postData, $cookieData] as $source) {
            if (isset($source['keepsession']) && $source['keepsession'] === '1') {
                $keepsession = 1;
                break;
            }
        }

        $record = [
            'user_ip' => $sanitize($userIp),
            'user_port' => $sanitize((string) $userPort),
            'protocol' => $sanitize($protocol),
            'request_method' => $sanitize($requestMethod),
            'request_uri' => $sanitize($requestUri),
            'request_time' => $requestTime,
            'headers_data' => json_encode($sanitizeArr($headers), JSON_UNESCAPED_UNICODE),
            'get_data' => json_encode($sanitizeArr($getData), JSON_UNESCAPED_UNICODE),
            'decoded_get_data' => $decodedGetData ? json_encode($sanitizeArr($decodedGetData), JSON_UNESCAPED_UNICODE) : null,
            'post_data' => json_encode($sanitizeArr($postData), JSON_UNESCAPED_UNICODE),
            'decoded_post_data' => $decodedPostData ? json_encode($sanitizeArr($decodedPostData), JSON_UNESCAPED_UNICODE) : null,
            'cookie_data' => json_encode($sanitizeArr($cookieData), JSON_UNESCAPED_UNICODE),
            'decoded_cookie_data' => $decodedCookieData ? json_encode($sanitizeArr($decodedCookieData), JSON_UNESCAPED_UNICODE) : null,
            'keepsession' => $keepsession,
        ];

        $id = $this->records->create($record);

        // Send email notification
        if ($this->mail->isEnabled()) {
            $this->mail->sendNotification($record);
        }

        // Return a 1x1 transparent GIF
        $response->getBody()->write(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
        return $response
            ->withHeader('Content-Type', 'image/gif')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    private function tryBase64Decode(array $arr): array|false
    {
        if (empty($arr)) {
            return false;
        }

        $changed = false;
        $decoded = [];
        foreach ($arr as $k => $v) {
            if (!is_string($v)) {
                $decoded[$k] = '';
                continue;
            }
            if ($this->isBase64($v)) {
                $decoded[$k] = base64_decode($v);
                $changed = true;
            } else {
                $decoded[$k] = '';
            }
        }

        return $changed ? $decoded : false;
    }

    private function isBase64(string $str): bool
    {
        if (!preg_match('/^[A-Za-z0-9+\/=]+$/', $str)) {
            return false;
        }
        $decoded = base64_decode($str, true);
        if ($decoded === false) {
            return false;
        }
        return base64_encode($decoded) === $str;
    }
}
