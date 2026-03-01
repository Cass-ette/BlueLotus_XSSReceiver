<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Build DI container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    'settings' => require __DIR__ . '/../config/settings.php',
    PDO::class => function ($c) {
        $settings = $c->get('settings');
        $dbPath = $settings['database_path'];
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA foreign_keys=ON');

        // Auto-initialize schema
        $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
        $pdo->exec($schema);

        // Seed default admin user if no users exist
        $count = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count == 0) {
            $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
            $stmt->execute([
                $settings['default_username'],
                password_hash($settings['default_password'], PASSWORD_BCRYPT),
            ]);
        }

        return $pdo;
    },
]);
$container = $containerBuilder->build();

// Create Slim app
$app = AppFactory::createFromContainer($container);

// Parse JSON body
$app->addBodyParsingMiddleware();

// Add CORS middleware
$app->add(new App\Middleware\CorsMiddleware());

// Add routing middleware
$app->addRoutingMiddleware();

// Error handling
$app->addErrorMiddleware(true, true, true);

// Register routes
(require __DIR__ . '/../src/routes.php')($app);

$app->run();
