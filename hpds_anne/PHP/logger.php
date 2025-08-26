<?php
/**
 * 日誌記錄系統
 * 記錄系統各種操作和安全事件
 */

require_once __DIR__ . '/env_loader.php';

class Logger {
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_SUCCESS = 'SUCCESS';
    const LEVEL_INFO = 'INFO';
    
    private static $instance = null;
    private $logPath;
    private $enabled;
    private $maxSize;
    private $maxFiles;
    
    private function __construct() {
        EnvLoader::load();
        $this->logPath = EnvLoader::get('LOG_PATH', 'C:/xampp/logs');
        $this->enabled = EnvLoader::get('LOG_ENABLED', 'true') === 'true';
        $this->maxSize = intval(EnvLoader::get('LOG_MAX_SIZE', 10485760)); // 10MB
        $this->maxFiles = intval(EnvLoader::get('LOG_MAX_FILES', 30));
        
        // 確保日誌目錄存在
        if (!is_dir($this->logPath)) {
            @mkdir($this->logPath, 0755, true);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    //記錄用戶匯出事件
    public static function logUserExport($studentId, $permission, $searchConditions = '全部') {
        $logger = self::getInstance();
        $message = sprintf(
            "用戶匯出 - 帳號: %s, 權限: %s, 搜尋條件: %s",
            $studentId,
            $permission,
            $searchConditions
        );
        $logger->writeLog(self::LEVEL_SUCCESS, 'USER_EXPORT', $message, $studentId);
    }
    
    //記錄成績匯出事件
    public static function logScoreExport($studentId, $permission, $exportType, $searchConditions = '全部') {
        $logger = self::getInstance();
        $message = sprintf(
            "成績匯出 - 帳號: %s, 權限: %s, 類型: %s, 搜尋條件: %s",
            $studentId,
            $permission,
            $exportType,
            $searchConditions
        );
        $logger->writeLog(self::LEVEL_SUCCESS, 'SCORE_EXPORT', $message, $studentId);
    }

    //記錄登入狀態
    public static function logLogin($studentId, $success, $failReason = null, $permission = null) {
        $logger = self::getInstance();
        if ($success) {
            $message = sprintf(
                "登入成功 - 帳號: %s, 權限: %s",
                $studentId,
                $permission
            );
            $logger->writeLog(self::LEVEL_SUCCESS, 'LOGIN_SUCCESS', $message, $studentId);
        } else {
            $message = sprintf(
                "登入失敗 - 帳號: %s, 失敗原因: %s",
                $studentId,
                $failReason
            );
            $logger->writeLog(self::LEVEL_WARNING, 'LOGIN_FAILED', $message, $studentId);
        }
    }

    //記錄自動登出事件
    public static function logAutoLogout($studentId, $reason = 'Session逾時') {
        $logger = self::getInstance();
        $message = sprintf(
            "自動登出 - 帳號: %s, 原因: %s",
            $studentId,
            $reason
        );
        $logger->writeLog(self::LEVEL_INFO, 'AUTO_LOGOUT', $message, $studentId);
    }

    //記錄成績匯入事件
    public static function logScoreImport($operatorId, $targetStudentId, $score, $source = 'MANUAL') {
        $logger = self::getInstance();
        $message = sprintf(
            "成績匯入 - 操作者: %s, 匯入成績到: %s, 分數: %s, 來源: %s",
            $operatorId,
            $targetStudentId,
            $score,
            $source
        );
        $logger->writeLog(self::LEVEL_SUCCESS, 'SCORE_IMPORT', $message, $operatorId);
    }

    //記錄Unity成績匯入事件
    public static function logUnityScoreImport($targetStudentId, $score, $sourceInfo = '') {
        $logger = self::getInstance();
        $message = sprintf(
            "Unity成績匯入 - 匯入到學號: %s, 分數: %s, 來源資訊: %s",
            $targetStudentId,
            $score,
            $sourceInfo
        );
        $logger->writeLog(self::LEVEL_SUCCESS, 'UNITY_SCORE_IMPORT', $message, 'UNITY_SYSTEM');
    }

    //記錄成績刪除事件
    public static function logScoreDelete($operatorId, $targetStudentId, $scoreInfo, $details = '') {
        $logger = self::getInstance();
        $message = sprintf(
            "成績刪除 - 操作者: %s, 目標學號: %s, 成績資訊: %s, 詳情: %s",
            $operatorId,
            $targetStudentId,
            $scoreInfo,
            $details
        );
        $logger->writeLog(self::LEVEL_WARNING, 'SCORE_DELETE', $message, $operatorId);
    }

    //記錄用戶管理操作
    public static function logUserManagement($operatorId, $action, $targetStudentId, $details = '') {
        $logger = self::getInstance();
        $message = sprintf(
            "用戶管理 - 操作者: %s, 動作: %s, 目標用戶: %s, 詳情: %s",
            $operatorId,
            $action,
            $targetStudentId,
            $details
        );
        $level = ($action === 'DELETE') ? self::LEVEL_WARNING : self::LEVEL_SUCCESS;
        $logger->writeLog($level, 'USER_MANAGEMENT', $message, $operatorId);
    }

    //記錄系統錯誤
    public static function logError($message, $context = '', $userId = 'SYSTEM') {
        $logger = self::getInstance();
        $fullMessage = sprintf(
            "系統錯誤 - %s, 內容: %s",
            $message,
            $context
        );
        $logger->writeLog(self::LEVEL_ERROR, 'SYSTEM_ERROR', $fullMessage, $userId);
    }
    
    //記錄安全事件
    public static function logSecurity($event, $message, $userId = 'UNKNOWN') {
        $logger = self::getInstance();
        $fullMessage = sprintf(
            "安全事件 - %s: %s",
            $event,
            $message
        );
        $logger->writeLog(self::LEVEL_WARNING, 'SECURITY', $fullMessage, $userId);
    }
    
    //寫入日誌文件
    private function writeLog($level, $category, $message, $userId) {
        if (!$this->enabled) {
            return;
        }
        
        try {
            $timestamp = date('Y-m-d H:i:s');
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            // 格式化日誌條目
            $logEntry = sprintf(
                "[%s] [%s] [%s] [%s] [%s] %s | UA: %s\n",
                $timestamp,
                $level,
                $category,
                $userId,
                $clientIp,
                $message,
                $userAgent
            );
            
            // 取得日誌檔案路徑
            $logFile = $this->getLogFilePath();
            
            // 檢查檔案大小並輪轉
            $this->rotateLogIfNeeded($logFile);
            
            // 寫入日誌
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
            
        } catch (Exception $e) {
            // 日誌系統本身出錯時，記錄到 PHP 錯誤日誌
            error_log("Logger error: " . $e->getMessage());
        }
    }
    
    //取得當前日誌檔案路徑
    private function getLogFilePath() {
        $date = date('Y-m-d');
        return $this->logPath . "/hpds_{$date}.log";
    }
    
    //檢查並輪轉日誌檔案
    private function rotateLogIfNeeded($logFile) {
        if (!file_exists($logFile)) {
            return;
        }
        
        if (filesize($logFile) > $this->maxSize) {
            $timestamp = date('His');
            $newName = str_replace('.log', "_{$timestamp}.log", $logFile);
            rename($logFile, $newName);
            
            // 清理舊檔案
            $this->cleanOldLogs();
        }
    }

    //清理舊的日誌檔案
    private function cleanOldLogs() {
        $files = glob($this->logPath . "/hpds_*.log");
        if (count($files) > $this->maxFiles) {
            // 按修改時間排序
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // 刪除最舊的檔案
            $filesToDelete = array_slice($files, 0, count($files) - $this->maxFiles);
            foreach ($filesToDelete as $file) {
                @unlink($file);
            }
        }
    }

    //取得日誌檔案列表
    public static function getLogFiles() {
        $logger = self::getInstance();
        $files = glob($logger->logPath . "/hpds_*.log");
        
        // 按修改時間排序（最新的在前）
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        return array_map(function($file) {
            return [
                'name' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'modified' => filemtime($file)
            ];
        }, $files);
    }

    //讀取日誌內容
    public static function readLogFile($filename, $lines = 100) {
        $logger = self::getInstance();
        $filepath = $logger->logPath . '/' . basename($filename);
        
        if (!file_exists($filepath)) {
            return [];
        }
        
        $content = file($filepath);
        if ($content === false) {
            return [];
        }
        
        // 只取最後幾行
        $content = array_slice($content, -$lines);
        
        // 解析日誌條目
        $logs = [];
        foreach ($content as $line) {
            if (empty(trim($line))) {
                continue;
            }
            
            // 解析日誌格式: [timestamp] [level] [category] [userId] [ip] message | UA: userAgent
            if (preg_match('/^\[([^\]]+)\] \[([^\]]+)\] \[([^\]]+)\] \[([^\]]+)\] \[([^\]]+)\] (.*?) \| UA: (.*)$/', $line, $matches)) {
                $logs[] = [
                    'timestamp' => $matches[1],
                    'level' => $matches[2],
                    'category' => $matches[3],
                    'user_id' => $matches[4],
                    'ip' => $matches[5],
                    'message' => $matches[6],
                    'user_agent' => $matches[7]
                ];
            }
        }
        
        return array_reverse($logs); // 最新的在前
    }
}