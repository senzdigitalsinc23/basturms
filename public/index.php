<?php


require __DIR__ . '/../vendor/autoload.php';

/* echo password_hash('tem22ple12345?', PASSWORD_BCRYPT);
exit; */

// Load .env before any config (use Dotenv if installed, else parse .env manually)
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile) && is_readable($envFile)) {
    if (class_exists(\Dotenv\Dotenv::class)) {
        $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->safeLoad();
    } else {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                if ($name !== '') {
                    $_ENV[$name] = $value;
                    putenv("$name=$value");
                }
            }
        }
    }
}

// Validate environment configuration
try {
    $validator = new \App\Core\EnvironmentValidator();
    $validator->validateOrFail();
} catch (\RuntimeException $e) {
    // In production, show generic error
    if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
        http_response_code(500);
        die('Application configuration error. Please contact support.');
    }
    // In development, show detailed error
    die($e->getMessage());
}

// Send secure HTTP headers
\App\Middleware\SecureHeaders::send();

use App\Core\Cache;
use App\Core\Config;
use App\Core\Container;
use App\Core\EventDispatcher;
use App\Core\Queue;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Storage;

Config::load(dirname(__DIR__) . '/config');

// Basic PHP error display based on env/config
if (Config::get('app.env') === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
} elseif (!empty(Config::get('app.display_errors')) && Config::get('app.display_errors') === 'true') {
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

// Bind PDO (database connection)
$container->singleton(\PDO::class, function($container) {
    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $dbname = $_ENV['DB_NAME'] ?? 'basturms_db';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? '';
    
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    
    return new \PDO($dsn, $user, $pass, [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES => false
    ]);
});

// Bind Cache
$container->singleton(\App\Core\Cache::class, fn($container) => new \App\Core\Cache());

// Bind core services
$container->singleton(Request::class, fn($container) => new Request());
$container->singleton(Response::class, fn($container) => new Response());

// Bind repositories
$container->singleton(\App\Repositories\StudentRepository::class, fn($container) => 
    new \App\Repositories\StudentRepository(
        $container->resolve(\PDO::class),
        $container->resolve(\App\Core\Cache::class)
    )
);
$container->singleton(\App\Repositories\UserRepository::class, fn($container) => 
    new \App\Repositories\UserRepository(
        $container->resolve(\PDO::class),
        $container->resolve(\App\Core\Cache::class)
    )
);
$container->singleton(\App\Repositories\AcademicSetupRepository::class, fn($container) => 
    new \App\Repositories\AcademicSetupRepository()
);
$container->singleton(\App\Repositories\AcademicYearRepository::class, fn($container) => 
    new \App\Repositories\AcademicYearRepository(
        $container->resolve(\PDO::class),
        $container->resolve(\App\Core\Cache::class)
    )
);
$container->singleton(\App\Repositories\SubjectRepository::class, fn($container) => 
    new \App\Repositories\SubjectRepository(
        $container->resolve(\PDO::class),
        $container->resolve(\App\Core\Cache::class)
    )
);
$container->singleton(\App\Repositories\ClassRepository::class, fn($container) => 
    new \App\Repositories\ClassRepository(
        $container->resolve(\PDO::class),
        $container->resolve(\App\Core\Cache::class)
    )
);

// Bind services
$container->singleton(\App\Services\ValidationService::class, fn($container) => new \App\Services\ValidationService());
$container->singleton(\App\Services\StudentService::class, fn($container) => 
    new \App\Services\StudentService(
        $container->resolve(\App\Repositories\StudentRepository::class)
    )
);
$container->singleton(\App\Services\AuthValidationService::class, fn($container) => new \App\Services\AuthValidationService());
$container->singleton(\App\Services\AcademicSetupService::class, fn($container) => 
    new \App\Services\AcademicSetupService(
        $container->resolve(\App\Repositories\AcademicSetupRepository::class),
        $container->resolve(\App\Repositories\AcademicYearRepository::class),
        $container->resolve(\App\Services\ValidationService::class)
    )
);
$container->singleton(\App\Services\AuthService::class, fn($container) => 
    new \App\Services\AuthService(
        $container->resolve(\App\Repositories\UserRepository::class),
        $container->resolve(\App\Services\AcademicSetupService::class)
    )
);
$container->singleton(\App\Services\AdminService::class, fn($container) => 
    new \App\Services\AdminService(
        $container->resolve(\App\Repositories\UserRepository::class)
    )
);
$container->singleton(\App\Services\SubjectService::class, fn($container) => 
    new \App\Services\SubjectService(
        $container->resolve(\App\Repositories\SubjectRepository::class)
    )
);
$container->singleton(\App\Services\ClassService::class, fn($container) => 
    new \App\Services\ClassService(
        $container->resolve(\App\Repositories\ClassRepository::class)
    )
);

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

