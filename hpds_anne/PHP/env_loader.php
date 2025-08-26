<?php
//環境變數載入器

class EnvLoader {
    private static $loaded = false;
    private static $env = [];

    public static function load($filePath = null) { //載入環境變數檔案
        if (self::$loaded) {
            return;
        }

        if ($filePath === null) {
            $filePath = 'C:\xampp\config.env';
        }

        if (!file_exists($filePath)) {
            throw new Exception("環境配置檔案不存在: {$filePath}");
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) { // 忽略註解
                continue;
            }
                    
            if (strpos($line, '=') !== false) { // 解析 key=value
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                $value = trim($value, '"\''); // 移除引號
                
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
                self::$env[$key] = $value;
            }
        }
        
        self::$loaded = true;
    }

    
    public static function get($key, $default = null) { //取得環境變數
        if (!self::$loaded) {
            self::load();
        }
        
        return $_ENV[$key] ?? $default;
    }

    public static function isDebug() { //除錯模式?
        return self::get('APP_DEBUG', 'false') === 'true';
    }
}
