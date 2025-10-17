<?php

use App\Core\Container;
use App\Core\EventDispatcher;
use Dotenv\Dotenv;

function db(): PDO
{
    static $pdo;

    if (!$pdo) {
        $config = Dotenv::createImmutable(__DIR__ . '/../../');
        $config->load();

        $config = [
            "host" => $_ENV['DB_HOST'],
            "db"   => $_ENV['DB_NAME'],
            "user" => $_ENV['DB_USER'],
            "pass" => $_ENV['DB_PASS'],
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
    function session(string $key = null, mixed $default = null): mixed
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


