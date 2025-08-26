<?php
if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
    // 對於 GET 請求，直接返回 HTML 認證狀態頁面
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 驗證憑證
        require_once '../PHP/common.php';
        $passwordCheck = validatePassword($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
        
        if ($passwordCheck['success']) {
            // 認證成功，返回明確的成功頁面
            header('Content-Type: text/html; charset=UTF-8');
            echo "<!DOCTYPE html>";
            echo "<html><head><title>Authentication Successful</title></head>";
            echo "<body>";
            echo "<h1>Authentication Successful</h1>";
            echo "<p><strong>Status:</strong> success authenticated</p>";
            echo "<p><strong>User:</strong> " . htmlspecialchars($_SERVER['PHP_AUTH_USER']) . "</p>";
            echo "<p><strong>Permission:</strong> student</p>";
            echo "<p><strong>Login Method:</strong> Basic Authentication</p>";
            echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
            echo "</body></html>";
        } else {
            // 認證失敗
            header('Content-Type: text/html; charset=UTF-8');
            http_response_code(401);
            echo "<!DOCTYPE html>";
            echo "<html><head><title>Authentication Failed</title></head>";
            echo "<body>";
            echo "<h1>Authentication Failed</h1>";
            echo "<p>Invalid credentials</p>";
            echo "</body></html>";
        }
        exit;
    }
    
    // 對於 POST 請求，轉換為表單參數
    if (empty($_POST)) {
        $_POST['student_id'] = $_SERVER['PHP_AUTH_USER'];
        $_POST['password'] = $_SERVER['PHP_AUTH_PW'];
        $_POST['action'] = 'login';
    }
}
session_start();

// 使用共用函式庫
require_once '../PHP/common.php';

// 取得動作類型
$action = $_POST['action'] ?? 'get_data';

// 統一的登入函式
function performLogin($student_id, $password) {
    // 先清理可能存在的舊 Session
    session_unset();
    
    // 驗證密碼
    $passwordCheck = validatePassword($student_id, $password);
    if (!$passwordCheck['success']) {
        Logger::logLogin($student_id, false, $passwordCheck['error']);
        // 登入失敗時確保 Session 被清理
        session_destroy();
        errorResponse($passwordCheck['error']);
    }
    
    // 取得用戶權限
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT permission FROM users WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (!$row) {
        // 用戶不存在
        session_destroy();
        Logger::logLogin($student_id, false, '用戶不存在');
        errorResponse('用戶不存在');
    }
    
    // 修正 typo（若有）→ student
    $perm = strtolower($row['permission']) === 'sutdent' ? 'student' : $row['permission'];

    
    // 登入成功，設定 Session
    $_SESSION['student_id'] = $student_id;
    $_SESSION['permission'] = $perm;
    $_SESSION['last_activity'] = time();
    
    // 記錄登入成功
    Logger::logLogin($student_id, true, null, $perm);
    
    $stmt->close();
    $conn->close();
    
    return $perm;
}

try {
    switch ($action) {
        case 'logout':
            // 處理登出
            session_unset(); 
            session_destroy();
            successResponse();
            break;
            
        case 'test':
            // 處理系統測試 - 無需權限檢查
            $result = performSystemTest();
            successResponse(['test_result' => $result]);
            break;
            
        case 'login':
            // 處理明確的登入請求
            $sid = trim($_POST['student_id'] ?? '');
            $pwd = trim($_POST['password'] ?? '');
            
            if($sid === '' || $pwd === ''){
                errorResponse('請輸入學號和密碼');
            }
            
            $perm = performLogin($sid, $pwd);
            successResponse(['permission' => $perm, 'student_id' => $sid]);
            break;
            
        case 'get_student_scores':
            // 學生查詢成績（包含用戶資訊）- 整合自 get.php
            $sid = trim($_POST['student_id'] ?? '');
            $pwd = trim($_POST['password'] ?? '');
            
            if($sid === '' || $pwd === ''){
                errorResponse('請輸入學號和密碼');
            }
            
            // 驗證密碼
            $passwordCheck = validatePassword($sid, $pwd);
            if (!$passwordCheck['success']) {
                Logger::logLogin($sid, false, $passwordCheck['error']);
                errorResponse($passwordCheck['error']);
            }
            
            // 查詢成績和用戶資訊
            getStudentScoresWithUserInfo($sid);
            break;
            
        case 'get_data':
        default:
            // 檢查是否有新的登入請求（提供了帳號密碼）
            $sid = trim($_POST['student_id'] ?? '');
            $pwd = trim($_POST['password'] ?? '');
            
            if($sid !== '' && $pwd !== '') {
                // 有提供帳密，執行新的登入
                performLogin($sid, $pwd);
            } 
            // 檢查現有 Session 狀態
            elseif (!isset($_SESSION['student_id']) || time() - ($_SESSION['last_activity'] ?? 0) > SESSION_TIMEOUT) {
                // Session 不存在或過期，且沒有提供登入資訊
                errorResponse('請先登入');
            }
            
            // 更新活動時間
            $_SESSION['last_activity'] = time();
            
            // 根據 permission 分流
            $perm = $_SESSION['permission'];
            if($perm === 'teacher' || $perm === 'ta'){
                // 查詢教師/助教姓名
                $conn = getDbConnection();
                $userStmt = $conn->prepare("SELECT name FROM users WHERE student_id = ?");
                $userStmt->bind_param("s", $_SESSION['student_id']);
                $userStmt->execute();
                $userResult = $userStmt->get_result();
                $userName = null;
                if ($userRow = $userResult->fetch_assoc()) {
                    $userName = $userRow['name'];
                }
                $userStmt->close();
                $conn->close();
                
                successResponse([
                    'permission' => $perm,
                    'name' => $userName,
                    'student_id' => $_SESSION['student_id']
                ]);
            }
            elseif($perm === 'student' || $perm === 'nurse' || $perm === 'snurse'){
                // student, nurse, snurse 查詢自己的成績和個人資訊
                $sortMode = intval($_POST['sortMode'] ?? 0);
                getStudentScores($_SESSION['student_id'], $sortMode);
            }
            else {
                // 其他權限沒有查詢權限
                errorResponse('您沒有查詢權限。當前權限: ' . ($perm ?? 'null'));
            }
            break;
    }
} catch (Exception $e) {
    errorResponse($e->getMessage());
}

// 學生查詢成績的函式（僅成績資料）
function getStudentScores($studentId, $sortMode = 0) {
    try {
        $conn = getDbConnection();
        
        // 先查詢學生姓名
        $userStmt = $conn->prepare("SELECT name FROM users WHERE student_id = ?");
        $userStmt->bind_param("s", $studentId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $userName = null;
        if ($userRow = $userResult->fetch_assoc()) {
            $userName = $userRow['name'];
        }
        $userStmt->close();
        
        // 使用共用的排序函數
        $orderBy = getSortOrder($sortMode);
        
        $stmt = $conn->prepare(
            "SELECT student_id,score,incorrect_questions,answer_time,answer_date
             FROM score
             WHERE student_id=?
             ORDER BY " . str_replace('s.', '', $orderBy)
        );
        
        if(!$stmt){
            throw new Exception('查詢成績準備失敗: ' . $conn->error);
        }
        
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while($r = $result->fetch_assoc()){
            $data[] = $r;
        }
        
        $stmt->close();
        $conn->close();
        
        successResponse([
            'permission' => $_SESSION['permission'] ?? 'student',
            'name' => $userName,
            'student_id' => $studentId,
            'data' => $data,
            'cookie' => $_COOKIE['PHPSESSID'] ?? null
        ]);
        
    } catch (Exception $e) {
        errorResponse($e->getMessage());
    }
}

// 學生查詢成績的函式（包含用戶資訊）- 整合自 get.php
function getStudentScoresWithUserInfo($studentId) {
    try {
        $conn = getDbConnection();
        
        // 查詢成績和用戶資訊
        $sql = "SELECT u.student_id, u.name, u.gender, s.score, s.incorrect_questions, s.answer_time, s.answer_date
                FROM users u
                JOIN score s ON u.student_id = s.student_id
                WHERE u.student_id = ?
                ORDER BY s.answer_date ASC";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('查詢準備失敗: ' . $conn->error);
        }
        
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = array();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['name'] = decodeName($row['name']);
                $data[] = $row;
            }
            jsonResponse(array('data' => $data));
        } else {
            errorResponse('無此學號的數據');
        }
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        errorResponse($e->getMessage());
    }
}
