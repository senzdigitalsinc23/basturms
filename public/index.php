<?php

//require __DIR__ . '/../vendor/autoload.php';

use App\Core\Cache;
use App\Core\Config;
use App\Core\Container;
use App\Core\EventDispatcher;
use App\Core\Queue;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Storage;

//echo password_hash('123456', PASSWORD_BCRYPT);exit;
//echo json_encode(['success' => true, 'message' => $_SERVER['REQUEST_URI'] ]);exit;

Config::load(dirname(__DIR__) . '/config');

// Basic PHP error display based on config
if (!empty(Config::get('app.display_errors')) && Config::get('app.display_errors') === 'true') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

if (session_status() === PHP_SESSION_NONE) {
    // Secure cookie & strict session settings
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? null) == 443;

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax', // consider 'Strict' for non-3rd-party flows
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $secure ? '1' : '0');
    ini_set('session.use_only_cookies', '1');
    session_name('app_session');

    session_start();
    // Rotate session ID after login or privilege changes
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
}


// public/index.php (top-level front controller)
set_exception_handler(function (\Throwable $e) {
$isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');
$code  = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;

    http_response_code((int) $code);

    //show(Config::get('app.debug'));

    if (!empty(Config::get('app.debug')) && Config::get('app.debug') === 'true') {
        if ($isApi) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage(), 'trace' => $e->getTrace()]);
        } else {
            echo "<pre>" . htmlspecialchars((string)$e, ENT_QUOTES) . "</pre>";
        }
    } else {
        if ($isApi) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Server error']);
        } else {
            echo "Something went wrong.";
        }
    }
});

// Boot container
$container = new Container();

$app_name = '';

// Bind core services
$container->singleton(Request::class, fn($container) => new Request());
$container->singleton(Response::class, fn($container) => new Response());

// Bind repositories
$container->singleton(\App\Repositories\StudentRepository::class, fn($container) => new \App\Repositories\StudentRepository());
$container->singleton(\App\Repositories\UserRepository::class, fn($container) => new \App\Repositories\UserRepository());

// Bind services
$container->singleton(\App\Services\ValidationService::class, fn($container) => new \App\Services\ValidationService());
$container->singleton(\App\Services\StudentService::class, fn($container) => new \App\Services\StudentService($container->resolve(\App\Repositories\StudentRepository::class)));
$container->singleton(\App\Services\AuthValidationService::class, fn($container) => new \App\Services\AuthValidationService());
$container->singleton(\App\Services\AuthService::class, fn($container) => new \App\Services\AuthService($container->resolve(\App\Repositories\UserRepository::class)));
$container->singleton(\App\Services\AdminService::class, fn($container) => new \App\Services\AdminService($container->resolve(\App\Repositories\UserRepository::class)));

// Register Cache (singleton)
$container->singleton(Cache::class, function ($container) {
    return new Cache(__DIR__ . '/../storage/cache');
});

// Register Storage (singleton)
$container->singleton(Storage::class, function ($container) {
    return new Storage(__DIR__ . '/../storage/files');
});

$container->singleton(Queue::class, function ($container) {
    return new Queue(__DIR__ . '/../storage/jobs');
});




/* $container->singleton(EmailService::class, function () {
    return new EmailService('noreply@myapp.com');
});

$container->singleton(SMSService::class, function () {
    return new SMSService();
}); */

$container->singleton(EventDispatcher::class, function ($container) {
    $queue = $container->resolve(Queue::class);
    return new EventDispatcher($queue);
});

// Init router
$router = new Router($container);

// Load routes
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

// Dispatch request
$request = $container->resolve(Request::class);
$response = $container->resolve(Response::class);

/* $sms = $container->resolve(SMSService::class); */

$response = $router->dispatch($request, $response);

// Avoid double-send when controllers already called json() which sends output
if (!headers_sent()) {
    $response->send();
}

