<?php

require __DIR__ . '/../vendor/autoload.php';

$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile) && is_readable($envFile)) {
    if (class_exists(\Dotenv\Dotenv::class)) {
        \Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
    } else {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name); $value = trim($value, " \t\n\r\0\x0B\"'");
            if ($name !== '') { $_ENV[$name] = $value; putenv("$name=$value"); }
        }
    }
}

try {
    (new \App\Core\EnvironmentValidator())->validateOrFail();
} catch (\RuntimeException $e) {
    if (($_ENV['APP_ENV'] ?? 'production') !== 'production') die($e->getMessage());
    http_response_code(500);
    die('Application configuration error. Please contact support.');
}

\App\Middleware\SecureHeaders::send();

use App\Core\Config;
use App\Core\Container;
use App\Core\EventDispatcher;
use App\Core\Queue;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Storage;

Config::load(dirname(__DIR__) . '/config');

$isProduction = Config::get('app.env') === 'production';

ini_set('display_errors', $isProduction ? '0' : '1');
ini_set('display_startup_errors', $isProduction ? '0' : '1');
error_reporting($isProduction ? 0 : E_ALL);

if ($isProduction) {
    ini_set('log_errors', '1');
    ini_set('error_log', dirname(__DIR__) . '/storage/logs/php_errors.log');
}

if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','domain'=>'','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $secure ? '1' : '0');
    ini_set('session.use_only_cookies', '1');
    session_name('app_session');
    session_start();
    if (!isset($_SESSION['initiated'])) { session_regenerate_id(true); $_SESSION['initiated'] = true; }
}

set_exception_handler(function (\Throwable $e) use ($isProduction) {
    $isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');
    $code  = ($e->getCode() >= 400 && $e->getCode() < 600) ? (int)$e->getCode() : 500;
    http_response_code($code);
    error_log('[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if ($isProduction) {
        header('Content-Type: application/json');
        echo $isApi ? json_encode(['success'=>false,'message'=>'An unexpected error occurred.']) : 'Something went wrong.';
    } else {
        if ($isApi) { header('Content-Type: application/json'); echo json_encode(['error'=>$e->getMessage(),'trace'=>$e->getTrace()]); }
        else echo '<pre>' . htmlspecialchars((string)$e, ENT_QUOTES) . '</pre>';
    }
});

$container = new Container();

$container->singleton(\PDO::class, function () {
    return new \PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ';dbname=' . ($_ENV['DB_NAME'] ?? 'agh_validations') . ';charset=utf8mb4',
        $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASS'] ?? '',
        [\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION,\PDO::ATTR_DEFAULT_FETCH_MODE=>\PDO::FETCH_ASSOC,\PDO::ATTR_EMULATE_PREPARES=>false]
    );
});

$container->singleton(\App\Core\Cache::class, fn() => new \App\Core\Cache());
$container->singleton(Request::class,         fn() => new Request());
$container->singleton(Response::class,        fn() => new Response());
$container->singleton(Storage::class,         fn() => new Storage(__DIR__ . '/../storage/files'));
$container->singleton(Queue::class,           fn() => new Queue(__DIR__ . '/../storage/jobs'));
$container->singleton(EventDispatcher::class, fn($c) => new EventDispatcher($c->resolve(Queue::class)));

$router = new Router($container);
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

$request  = $container->resolve(Request::class);
$response = $container->resolve(Response::class);
$response = $router->dispatch($request, $response);
if (!headers_sent()) $response->send();
