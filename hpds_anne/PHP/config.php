<?php
require_once __DIR__ . '/env_loader.php';

// 載入環境變數 (CORS 要用到白名單)
try {
    EnvLoader::load();
} catch (Exception $e) {
    error_log("警告: 無法載入環境配置檔案 - " . $e->getMessage());
}

$rawAllowed = EnvLoader::get('ALLOWED_ORIGINS', '');
$allowedOrigins = array_filter(array_map('trim', explode(',', $rawAllowed)));
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($requestOrigin && in_array($requestOrigin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 600');

// 處理預檢請求 (OPTIONS) 直接快速返回
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No Content 更語意化
    exit();
}

$db_config = [
    'host' => EnvLoader::get('DB_HOST'),
    'username' => EnvLoader::get('DB_USERNAME'),
    'password' => EnvLoader::get('DB_PASSWORD'),
    'database' => EnvLoader::get('DB_DATABASE'),
    'port' => intval(EnvLoader::get('DB_PORT'))
];

// 驗證必要的配置
if (empty($db_config['password'])) {
    if (EnvLoader::isDebug()) {
        die('錯誤: 未設定資料庫密碼，請檢查環境配置檔案');
    } else {
        die('配置錯誤');
    }
}

// Session 配置
define('SESSION_TIMEOUT', intval(EnvLoader::get('SESSION_TIMEOUT', 300)));
define('SESSION_NAME', EnvLoader::get('SESSION_NAME', 'HPDS_SESSION'));
define('CSRF_TOKEN_NAME', EnvLoader::get('CSRF_TOKEN_NAME', 'csrf_token'));

// 設定時區
date_default_timezone_set(EnvLoader::get('APP_TIMEZONE', 'Asia/Taipei'));

?>
