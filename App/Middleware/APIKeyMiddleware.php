<?php
// app/Middleware/ApiKeyMiddleware.php
namespace App\Middleware;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;

class APIKeyMiddleware
{
    private array $validKeys;

    public function __construct()
    {
        // Load config PHP files from project config directory
        Config::load(dirname(__DIR__, 2) . '/config');

        // Load keys from config/env (comma-separated allowed)
        $keys = Config::get('api.api_key') ?? '';
        $this->validKeys = array_values(array_filter(array_map('trim', explode(',', (string)$keys))));
    }

    public function handle(Request $request, Response $response, callable $next): Response
    {
        // Debug: log incoming X-API-KEY and Authorization header
        $logPath = dirname(__DIR__, 2) . '/storage/logs/api_debug.log';
        $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $headerKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        file_put_contents($logPath, date('c') . " [APIKeyMiddleware] From IP $ip X-API-KEY: $headerKey | Authorization: $authorization\n", FILE_APPEND);

        // Accept API key from header or query
        $providedKey = '';

        if ($headerKey !== '') {
            $providedKey = $headerKey;
        } elseif ($authorization !== '') {
            // Support formats: "ApiKey <key>" or "Bearer <key>" if desired later
            if (stripos($authorization, 'ApiKey ') === 0) {
                $providedKey = trim(substr($authorization, 7));
            }
        } elseif ($_GET['api_key'] !== '') {
            $providedKey = $_GET['api_key'] ?? '';
        }

        if ($providedKey === '' || (!empty($this->validKeys) && !in_array($providedKey, $this->validKeys, true))) {
            $response->setStatusCode(401);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => 'Invalid or missing API key']));
            return $response;
        }

        return $next($request, $response);
    }

    
}

/* <?php
// app/Middleware/ApiKeyMiddleware.php
namespace App\Middleware;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use Repositories\ApiKeyRepo;
use Support\ApiPath;

class ApiKeyMiddleware
{
    protected ApiKeyRepo $repo;

    public function __construct() {
        // Load config PHP files
        Config::load(dirname(__DIR__) . '/config');
        $this->repo = new ApiKeyRepo();
    }

    public function handle(Request $request, Response $response, callable $next): Response
    {
        //show(ApiPath::isApi());
        if (!ApiPath::isApi() || ApiPath::isWhitelisted()) {
            return $next($request, $response);//echo json_encode(['success' => false, 'message' => 'Missing API key'], 401);exit; // only protect /api/* and not whitelisted paths
        }

        //show(Config::get('api.key'));
        $key = Config::get('api.key') ?? ($_GET['api_key'] ?? '');
        
        if (!$key) {
            return $response()->json(['success' => false, 'message' => 'Missing API key'], 401);
            //$this->deny('Missing API key'); // 401
        }

        $record = $this->repo->find($key);

        //show($this->repo->isValid($record));

        //show($this->repo->isValid($record));
        if (!$record || !$this->repo->isValid($record)) {
            return $response()->json(['success' => false, 'message' => 'Invalid or inactive API key'], 401);
            //$this->deny('Invalid or inactive API key'); // 401
        }

        // Attach key metadata for downstream use (logging, scopes, owner)
        $_SERVER['API_KEY_OWNER'] = $record['owner'] ?? null;
        $_SERVER['API_KEY_SCOPES'] = $record['scopes'] ?? [];

        return $next($request, $response);
    }

    /* private function deny(, string $msg): Response
    {
        http_response_code(401);
        header('Content-Type: application/json');
        header('WWW-Authenticate: ApiKey realm="API", format="X-API-KEY: <key>"');
        return $response()->json(['success' => false, 'message' => $msg]);
        exit;
    } */
