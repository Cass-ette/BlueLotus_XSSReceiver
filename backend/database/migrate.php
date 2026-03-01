<?php

/**
 * 旧数据迁移脚本
 * 将 /data/*.php, /template/*.js, /myjs/*.js 迁移到 SQLite
 *
 * Usage: php migrate.php
 */

declare(strict_types=1);

$rootDir = realpath(__DIR__ . '/../../');

// Load old config for encryption settings
define('IN_XSS_PLATFORM', true);

$oldConfigFile = $rootDir . '/config.php';
if (!file_exists($oldConfigFile)) {
    echo "Error: Old config.php not found at $oldConfigFile\n";
    exit(1);
}

require_once $oldConfigFile;
require_once $rootDir . '/functions.php';

// Initialize SQLite
$settings = require __DIR__ . '/../config/settings.php';
$dbPath = $settings['database_path'];
$dbDir = dirname($dbPath);
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA journal_mode=WAL');

// Initialize schema
$schema = file_get_contents(__DIR__ . '/schema.sql');
$pdo->exec($schema);

echo "=== BlueLotus XSS Receiver Data Migration ===\n\n";

// --- Migrate XSS Records ---
$dataDir = $rootDir . '/data';
$recordCount = 0;
$errorCount = 0;

if (is_dir($dataDir)) {
    $files = glob($dataDir . '/*.php');
    $total = count($files);
    echo "Found $total record files in /data/\n";

    $stmt = $pdo->prepare(
        'INSERT OR IGNORE INTO records (user_ip, user_port, protocol, request_method, request_uri,
         request_time, location, headers_data, get_data, decoded_get_data, post_data,
         decoded_post_data, cookie_data, decoded_cookie_data, keepsession)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $pdo->beginTransaction();

    foreach ($files as $file) {
        $filename = basename($file, '.php');

        // Skip non-timestamp files (e.g., forbiddenIPList)
        if (!preg_match('/^[0-9]{10}$/', $filename)) {
            continue;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            $errorCount++;
            continue;
        }

        // Strip PHP exit guard
        if (strncmp($content, '<?php exit();?>', 15) === 0) {
            $content = substr($content, 15);
        }

        // Decrypt
        $content = decrypt($content);

        // Parse JSON
        $info = @json_decode($content, true);
        if (!$info || !isset($info['request_time'])) {
            $errorCount++;
            echo "  Warning: Failed to parse $filename\n";
            continue;
        }

        $jsonEncode = function ($data) {
            return isset($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : null;
        };

        try {
            $stmt->execute([
                $info['user_IP'] ?? $info['user_ip'] ?? 'unknown',
                $info['user_port'] ?? null,
                $info['protocol'] ?? null,
                $info['request_method'] ?? null,
                $info['request_URI'] ?? $info['request_uri'] ?? null,
                (int) $info['request_time'],
                $info['location'] ?? null,
                $jsonEncode($info['headers_data'] ?? null),
                $jsonEncode($info['get_data'] ?? null),
                $jsonEncode($info['decoded_get_data'] ?? null),
                $jsonEncode($info['post_data'] ?? null),
                $jsonEncode($info['decoded_post_data'] ?? null),
                $jsonEncode($info['cookie_data'] ?? null),
                $jsonEncode($info['decoded_cookie_data'] ?? null),
                ($info['keepsession'] ?? false) ? 1 : 0,
            ]);
            $recordCount++;
        } catch (PDOException $e) {
            $errorCount++;
            echo "  Error migrating $filename: " . $e->getMessage() . "\n";
        }
    }

    $pdo->commit();
    echo "  Migrated: $recordCount records ($errorCount errors)\n\n";
} else {
    echo "No /data/ directory found, skipping records.\n\n";
}

// --- Migrate JS Scripts ---
function migrateScripts(PDO $pdo, string $dir, string $type): int
{
    $count = 0;
    if (!is_dir($dir)) {
        echo "No $type directory found, skipping.\n";
        return 0;
    }

    $files = glob($dir . '/*.js');
    echo "Found " . count($files) . " $type scripts\n";

    $stmt = $pdo->prepare(
        'INSERT OR IGNORE INTO scripts (name, description, content, type) VALUES (?, ?, ?, ?)'
    );

    foreach ($files as $file) {
        $filename = preg_replace('/^.+[\\\\\\/]/', '', $file);
        $name = substr($filename, 0, strlen($filename) - 3);

        $content = @file_get_contents($file) ?: '';

        // Load description
        $descFile = $dir . '/' . $name . '.desc';
        $desc = '';
        if (file_exists($descFile)) {
            $descContent = @file_get_contents($descFile);
            if ($descContent !== false) {
                $desc = decrypt($descContent);
                if (json_encode($desc) === false) {
                    $desc = '';
                }
            }
        }

        try {
            $stmt->execute([$name, $desc, $content, $type]);
            $count++;
        } catch (PDOException $e) {
            echo "  Error migrating $type '$name': " . $e->getMessage() . "\n";
        }
    }

    echo "  Migrated: $count $type scripts\n\n";
    return $count;
}

migrateScripts($pdo, $rootDir . '/template', 'template');
migrateScripts($pdo, $rootDir . '/myjs', 'myjs');

// --- Seed default admin user ---
$count = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($count == 0) {
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
    $stmt->execute([
        $settings['default_username'],
        password_hash($settings['default_password'], PASSWORD_BCRYPT),
    ]);
    echo "Created default admin user: {$settings['default_username']}\n\n";
}

echo "=== Migration Complete ===\n";
echo "Database: $dbPath\n";
