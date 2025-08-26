<?php
//共用函式庫 - 統一常用功能
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logger.php';


// 確保 session 已開始（如果還沒開始的話）
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

//統一的資料庫連線函數
function getDbConnection() {
    global $db_config;
    
    try {
        $conn = new mysqli(
            $db_config['host'], 
            $db_config['username'], 
            $db_config['password'], 
            $db_config['database'], 
            $db_config['port']
        );
        
        if ($conn->connect_error) {
            throw new Exception('DB 連線失敗: ' . $conn->connect_error);
        }
        
        return $conn;
    } catch (Exception $e) {
        throw $e;
    }
}

// 通用參數提取函式（處理成績提交相關參數）
function extractScoreParams() {
    return [
        'student_id' => trim($_POST['student_id'] ?? ''),
        'password' => trim($_POST['password'] ?? ''),
        'score' => trim($_POST['score'] ?? ''),
        'incorrect_questions' => trim($_POST['incorrect_questions'] ?? ''),
        'answer_time' => trim($_POST['answer_time'] ?? '')
    ];
}

// 通用參數驗證函式
function validateRequiredParams($params, $required = []) {
    foreach ($required as $param) {
        if (empty($params[$param])) {
            throw new Exception("缺少必要參數: {$param}");
        }
    }
}

//統一的權限檢查函數
function checkSessionAndPermission($allowedRoles = []) {
    
    // 處理登出
    if (isset($_POST['action']) && $_POST['action'] === 'logout') {
        if (isset($_SESSION['student_id'])) {
            Logger::logAutoLogout($_SESSION['student_id'], '手動登出');
        }
        session_unset();
        session_destroy();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => '已登出']);
        exit;
    }
    
    // 檢查 Session 和閒置逾時
    if (!isset($_SESSION['student_id']) 
        || time() - ($_SESSION['last_activity'] ?? 0) > SESSION_TIMEOUT) {

        if (isset($_SESSION['student_id'])) {
            Logger::logAutoLogout($_SESSION['student_id'], 'Session逾時');
        }
        session_unset();
        session_destroy();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => '您已自動登出', 'auto_logout' => true]);
        exit;
    }
    
    // 更新最後活動時間
    $_SESSION['last_activity'] = time();
    
    // 檢查權限
    if (!empty($allowedRoles) && !in_array($_SESSION['permission'], $allowedRoles, true)) {
        // 取得當前存取的頁面/API
        $currentPage = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        
        $accessInfo = sprintf(
            "嘗試存取需要權限: %s, 當前權限: %s, 存取頁面: %s, 來源頁面: %s",
            implode(',', $allowedRoles),
            $_SESSION['permission'],
            $currentPage,
            $referer
        );
        
        Logger::logSecurity('PERMISSION_DENIED', $accessInfo, $_SESSION['student_id']);
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => '您沒有此權限']);
        exit;
    }
    
    return $_SESSION;
}

//統一的密碼驗證函數
function validatePassword($student_id, $password) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT password FROM users WHERE student_id = ?");
    if (!$stmt) {
        $conn->close();
        throw new Exception('查詢準備失敗: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();

        return ['success' => false, 'error' => '無此學號'];
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    if ($row['password'] !== $password) {

        return ['success' => false, 'error' => '密碼錯誤'];
    }
    

    return ['success' => true];
}

//統一的排序邏輯
function getSortOrder($mode) {
    switch (intval($mode)) {
        case 1: 
            return 's.answer_time ASC';
        case 2: 
            return 's.answer_date DESC';  // 改為降序，新的在上面
        default: 
            return 's.score DESC';
    }
}

//Base64 姓名編碼
function encodeName($name) {
    return base64_encode($name);
}

//Base64 姓名解碼
function decodeName($encodedName) {
    return base64_decode($encodedName);
}

//統一的 CSV 匯出函數
function exportToCsv($data, $headers, $filename) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // UTF-8 BOM
    echo "\xEF\xBB\xBF";
    
    // 輸出標頭
    $escapedHeaders = array_map(function($header) {
        return '"' . str_replace('"', '""', $header) . '"';
    }, $headers);
    echo implode(',', $escapedHeaders) . "\n";
    
    // 輸出資料
    foreach ($data as $row) {
        $escapedRow = array_map(function($value) {
            return '"' . str_replace('"', '""', $value) . '"';
        }, $row);
        echo implode(',', $escapedRow) . "\n";
    }
}

//成績資料 CSV 匯出 (包含 q1-q16)
function exportScoresToCsv($sqlQuery, $params, $filename, $sortMode = 0) {
    $conn = getDbConnection();
    
    // 準備查詢
    $stmt = $conn->prepare($sqlQuery);
    if (!$stmt) {
        $conn->close();
        throw new Exception('查詢準備失敗: ' . $conn->error);
    }
    
    // 綁定參數
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        echo "沒有找到符合條件的資料";
        exit;
    }
    
    // 準備 CSV 標頭
    $headers = array_merge(
        ['student_id', 'name', 'score', 'incorrect_questions', 'answer_time'],
        array_map(fn($i) => "q{$i}", range(1, 16)),
        ['answer_date']
    );
    
    // 開始輸出 CSV
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";
    
    // 輸出標頭
    echo implode(',', $headers) . "\n";
    
    // 輸出資料
    while ($row = $result->fetch_assoc()) {
        // 解碼姓名
        $row['name'] = decodeName($row['name']);
        
        // 組織資料行
        $csvRow = [];
        foreach ($headers as $header) {
            $value = $row[$header] ?? '';
            $csvRow[] = '"' . str_replace('"', '""', $value) . '"';
        }
        echo implode(',', $csvRow) . "\n";
    }
    
    $stmt->close();
    $conn->close();
}

//回應 JSON 格式
function jsonResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit;
}

//錯誤回應
function errorResponse($message, $httpCode = 400) {
    jsonResponse(['success' => false, 'error' => $message], $httpCode);
}

//成功回應
function successResponse($data = [], $message = null) {
    $response = ['success' => true];
    if ($message) {
        $response['message'] = $message;
    }
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    jsonResponse($response);
}

//取得成績資料的基礎 SQL (包含 q1-q16)
function getScoreSelectSql($orderBy = 's.score DESC') {
    return "SELECT
        s.student_id,
        u.name,
        s.score,
        s.incorrect_questions,
        s.answer_time,
        s.answer_date," . 
        implode(',', array_map(fn($i) => "s.q{$i}", range(1, 16))) .
        " FROM score s
        JOIN users u ON s.student_id = u.student_id
        ORDER BY {$orderBy}";
}

//取得成績資料的 SQL (包含職稱篩選)
function getScoreSelectSqlWithPermission($orderBy = 's.score DESC', $permission = '') {
    $sql = "SELECT
        s.student_id,
        u.name,
        u.permission,
        s.score,
        s.incorrect_questions,
        s.answer_time,
        s.answer_date," . 
        implode(',', array_map(fn($i) => "s.q{$i}", range(1, 16))) .
        " FROM score s
        JOIN users u ON s.student_id = u.student_id";
    
    if (!empty($permission)) {
        $sql .= " WHERE u.permission = '" . mysqli_real_escape_string(getDbConnection(), $permission) . "'";
    }
    
    $sql .= " ORDER BY {$orderBy}";
    return $sql;
}

// 共用的用戶欄位定義
function getUserColumns() {
    return [
        'name','password','permission','gender','birth_date','graduate_year','education_level','marital_status',
        'hire_date','department','department_other','facility_type',
        'other_hospital_experience','other_hospital_years','critical_care_experience','critical_care_years',
        'bls_training_date','acls_experience','acls_training_date','rescue_count','last_rescue_date',
        'rescue_course_count','case_record_date','group_assignment'
    ];
}

//統一的用戶查詢 SQL
function getUserSelectSql($excludeSystem = true, $orderBy = 'id DESC') {
    $cols = getUserColumns();
    $sql = "SELECT student_id," . implode(',', $cols) . " FROM users";
    
    if ($excludeSystem) {
        $systemUserId = EnvLoader::get('SPECIAL_ADMIN_ID');
        $sql .= " WHERE student_id != '{$systemUserId}'";
    }
    
    $sql .= " ORDER BY {$orderBy}";
    return $sql;
}

//統一的用戶搜尋 SQL
function getUserSearchSql($conditions = [], $orderBy = 'id DESC') {
    $cols = getUserColumns();
    $sql = "SELECT student_id," . implode(',', $cols) . " FROM users";
    
    // 預設排除系統用戶
    $systemUserId = EnvLoader::get('SPECIAL_ADMIN_ID');
    $defaultCondition = "student_id != '{$systemUserId}'";
    
    if (!empty($conditions)) {
        $sql .= " WHERE {$defaultCondition} AND " . implode(' AND ', $conditions);
    } else {
        $sql .= " WHERE {$defaultCondition}";
    }
    
    $sql .= " ORDER BY {$orderBy}";
    return $sql;
}

//統一的系統測試功能
function performSystemTest() {
    $conn = getDbConnection();
    
    $result = [];
    $result['connection'] = 'OK';
    
    // 檢查表
    $tables = $conn->query("SHOW TABLES");
    $result['tables'] = [];
    while($row = $tables->fetch_array()){
        $result['tables'][] = $row[0];
    }
    
    // 檢查用戶數量
    $userCount = $conn->query("SELECT COUNT(*) as count FROM users");
    $result['user_count'] = $userCount->fetch_assoc()['count'];
    
    $conn->close();
    return $result;
}

//檢查系統權限
function isSystemUser($studentId) {
    return $studentId === EnvLoader::get('SPECIAL_ADMIN_ID');
}

//檢查系統管理權限
function checkSystemPermission() {
    $session = checkSessionAndPermission(['teacher', 'ta']);
    
    // if (!isSystemUser($session['student_id'])) {
    //     $currentPage = $_SERVER['REQUEST_URI'] ?? 'unknown';
    //     $referer = $_SERVER['HTTP_REFERER'] ?? '';
        
    //     $accessInfo = sprintf(
    //         "非管理員嘗試存取, 當前權限: %s, 存取頁面: %s, 來源頁面: %s",
    //         $session['permission'],
    //         $currentPage,
    //         $referer
    //     );
        
    //     Logger::logSecurity('SYSTEM_ACCESS_DENIED', $accessInfo, $session['student_id']);
    //     http_response_code(403);
    //     echo json_encode(['success' => false, 'error' => '您沒有權限']);
    //     exit;
    // }
    
    return $session;
}

// 正規化答題時間（秒數轉 mm:ss 格式）
function normalizeAnswerTime($answer_time) {
    if (strpos($answer_time, ':') === false) {
        $sec = intval($answer_time);
        $m = floor($sec / 60);
        $s = $sec % 60;
        return sprintf('%d:%02d', $m, $s);
    }
    return $answer_time;
}

// 處理錯誤題目陣列，轉換為 q1~q16 的布林值
function processIncorrectQuestions($incorrect_questions) {
    $incorrectArr = array_filter(array_map('trim', explode(',', $incorrect_questions)), 'strlen');
    $qValues = [];
    for ($i = 1; $i <= 16; $i++) {
        // 在 incorrectArr 裡面的題號 = 答錯(0)，否則答對(1)
        $qValues[$i] = in_array((string)$i, $incorrectArr, true) ? 0 : 1;
    }
    return $qValues;
}

// 統一的成績資料插入函數
function insertScoreRecord($student_id, $score, $answer_time, $incorrect_questions, $qValues = null) {
    if ($qValues === null) {
        $qValues = processIncorrectQuestions($incorrect_questions);
    }
    
    $conn = getDbConnection();
    
    // 準備 INSERT 語句（動態綁定 q1~q16）
    $fields = ['student_id','score','answer_time','incorrect_questions','answer_date'];
    $placeholders = ['?','?','?','?','?'];
    $types = 'sisss';  // s=string, i=int
    $values = [
        $student_id,
        (int)$score,
        $answer_time,
        $incorrect_questions,
        date('Y-m-d H:i:s')
    ];
    
    for ($i = 1; $i <= 16; $i++) {
        $fields[] = "q{$i}";
        $placeholders[] = '?';
        $types .= 'i';
        $values[] = $qValues[$i];
    }
    
    $sql = "INSERT INTO score (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Prepare 失敗: ' . $conn->error);
    }
    
    // bind_param with unpacked $values
    $stmt->bind_param($types, ...$values);
    
    // 執行並回傳結果
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return ['success' => true, 'message' => '資料已儲存'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        throw new Exception('執行失敗: ' . $error);
    }
}
