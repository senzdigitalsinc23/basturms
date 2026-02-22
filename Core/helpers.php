<?php

use App\Core\Container;
use App\Core\EventDispatcher;

/** Load .env into $_ENV if not already loaded (for CLI or when Dotenv package is missing). */
function loadEnvOnce(): void
{
    static $loaded = false;
    if ($loaded || !empty($_ENV['DB_HOST'])) {
        return;
    }
    $envFile = __DIR__ . '/../.env';
    if (!is_file($envFile) || !is_readable($envFile)) {
        return;
    }
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
    $loaded = true;
}

function db(): PDO
{
    static $pdo;

    if (!$pdo) {
        loadEnvOnce();
        $config = [
            "host" => $_ENV['DB_HOST'] ?? '',
            "db"   => $_ENV['DB_NAME'] ?? '',
            "user" => $_ENV['DB_USER'] ?? '',
            "pass" => $_ENV['DB_PASS'] ?? '',
        ];

       // var_dump($config);exit;
        $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}


use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Helpers\Auth;
use App\Models\Permission;

function request(): Request
{
    static $instance = null;

    if ($instance === null) {
        $instance = new Request();
    }

    return $instance;
}


function show($data){
    echo "<pre>";
    var_dump($data);
    echo "</pre>";

    exit;
}


if (!function_exists('response')) {
    function response()
    {
        return new class {
            public function json(array $data, int $status = 200)
            {
                http_response_code($status);

                $response = [
                    '200' => 'Ok'

                ];

                header('Content-Type: application/json');
                echo json_encode($data);
                exit;
            }
        };
    }
}


if (!function_exists('session')) {
    function session($key = null, $default = null)
    {
        if ($key === null) {
            return $_SESSION ?? [];
        }

        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('redirect')) {
    function redirect($url)
    {
        header("Location: $url");
        exit;
    }
}

if (!function_exists('back')) {
    function back()
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        redirect($referer);
    }
}

if (!function_exists('set_old_input')) {
    function set_old_input($data)
    {
        $_SESSION['__old_input'] = $data;
    }
}

if (!function_exists('flash')) {
    function flash($key, $value)
    {
        $_SESSION['__flash'][$key] = $value;
    }
}

if (!function_exists('get_flash')) {
    function get_flash($key)
    {
        if (isset($_SESSION['__flash'][$key])) {
            $val = $_SESSION['__flash'][$key];
            unset($_SESSION['__flash'][$key]);
            return $val;
        }
        return null;
    }
}

function layout($view) {

    if (is_array($view)) {
        foreach ($view as $vi) {
            $viewPath = __DIR__ . '/../app/views/layouts/partials/' . str_replace('.', '/', $vi) . '.view.php';
            //show($viewPath);

            if (!file_exists($viewPath)) {
                throw new Exception("View [$view] not found at $viewPath");
            }

            require_once $viewPath;
        }
    }else {
        $viewPath = __DIR__ . '/../app/views/layouts/partials/' . str_replace('.', '/', $view) . '.view.php';

        if (!file_exists($viewPath)) {
            throw new Exception("View [$view] not found at $viewPath");
        }
        require_once $viewPath;
    }
    
}

if (!function_exists('view')) {
    function view(string $view, array $data = [])
    {
        extract($data); // Make array keys available as variables

        $viewPath = __DIR__ . '/../app/views/' . str_replace('.', '/', $view) . '.view.php';

        if (!file_exists($viewPath)) {
            throw new Exception("View [$view] not found at $viewPath");
        }

        require_once $viewPath;
    }
}

function csrf_token()
{
    if (!isset($_SESSION['_token'])) {
        $_SESSION['_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_token'];
}

function response()
{
    return new class {
        public function json($data, int $code = 200)
        {
            http_response_code($code);
            header('Content-Type: application/json');
            echo json_encode($data);
            exit;
        }
    };
}

if (!function_exists('session')) {
    function session(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $_SESSION ?? [];
        }

        return Session::get($key, $default);
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $value): void
    {
        Session::flash($key, $value);
    }
}

if (!function_exists('get_flash')) {
    function get_flash(string $key): mixed
    {
        return Session::getFlash($key);
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        //show($key);
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
}

if (!function_exists('get_user')) {
    function get_user() {
        //show($_SESSION['user']);
        return $_SESSION['user'] ?? '';
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        if (get_user()) {
            return true;         
        }

        return false;
    }
}

if (!function_exists('icon')) {
    function icon($name = '', $color = '') {
        $filename = "./assets/images/bootstrap-icons/$name.svg";
        $content = '';
        

        if (file_exists($filename)) {
            $fp = fopen($filename, "r");

            if(filesize($filename) > 0){
                $content = fread($fp, filesize($filename));
                fclose($fp);
            }else $content = '';
        }

        

        return $content;
    }
}


function image($name, $width = '', $height = '', $color = '') {
    return "<img src=\"/assets/images/bootstrap-icons/{$name}\" alt=\"\" style=\"background-color: $color\">";
}


if (!function_exists('userCan')) {
    function userCan(string $permissionName): bool
    {
        $user = Session::get('user');
        if (!$user) {
            return false;
        }        

        return Auth::userCan($permissionName);
    }
}

if (!function_exists('hasRole')) {
    function hasRole(string $role): bool
    {
        return Session::get('user.role_name') === $role;
    }
}

if (!function_exists('remove')) {
    function remove($key) {
        return Session::remove($key);
    }
}

if (! function_exists('csrf_field')) {
    function csrf_field(): string {
    return '<input type="hidden" name="_token" value="' . \App\Core\Session::token() . '">';
}
}

if (!function_exists('event')) {
    /**
     * Fire an event
     *
     * @param string $eventName
     * @param mixed $payload
     */
    function event(string $eventName, $payload = null)
    {
        // Resolve EventDispatcher from container
        $container = new Container();
        $dispatcher = $container->resolve(EventDispatcher::class);

        $dispatcher->dispatch($eventName, $payload);
    }
}

function esc($string) {
    return View::e($string);
}

if (!function_exists('to_date')) {
    function to_date(string $date, string $format = 'Y-m-d'): string
    {
        $dt = new DateTime($date);
        return $dt->format($format);
    }
}

/**
 * Get logger instance
 *
 * @return \App\Core\Logger
 */
function logger(): \App\Core\Logger
{
    return \App\Core\LoggerFactory::getInstance();
}

/**
 * Log info message
 *
 * @param string $message
 * @param array $context
 * @return void
 */
function log_info(string $message, array $context = []): void
{
    logger()->info($message, $context);
}

/**
 * Log error message
 *
 * @param string $message
 * @param array $context
 * @return void
 */
function log_error(string $message, array $context = []): void
{
    logger()->error($message, $context);
}

/**
 * Log warning message
 *
 * @param string $message
 * @param array $context
 * @return void
 */
function log_warning(string $message, array $context = []): void
{
    logger()->warning($message, $context);
}

/**
 * Log debug message
 *
 * @param string $message
 * @param array $context
 * @return void
 */
function log_debug(string $message, array $context = []): void
{
    logger()->debug($message, $context);
}

/**
 * Log performance metrics
 *
 * @param string $operation
 * @param float $duration Duration in milliseconds
 * @param array $context
 * @return void
 */
function log_performance(string $operation, float $duration, array $context = []): void
{
    logger()->logPerformance($operation, $duration, $context);
}

/**
 * Log security event
 *
 * @param string $event
 * @param string $message
 * @param array $context
 * @return void
 */
function log_security(string $event, string $message, array $context = []): void
{
    logger()->logSecurity($event, $message, $context);
}