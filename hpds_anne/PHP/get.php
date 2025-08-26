<?php
header('Content-Type: application/json');

// Database credentials
require_once '../PHP/config.php';

// Create connection using config variables
$conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database'], $db_config['port']);

// Check connection
if ($conn->connect_error) {
    die(json_encode(array('error' => '資料庫連接失敗')));
}

// Get POST parameters
$student_id = $_POST['student_id'];
$password = $_POST['password']; // 新增的密碼參數

// Validate input
if (empty($student_id) || empty($password)) {
    echo json_encode(array('error' => '請輸入學號和密碼'));
    exit();
}

// Validate the password
$sql = "SELECT password FROM users WHERE student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$stmt->bind_result($stored_password);
$stmt->fetch();
$stmt->close();

// Compare the stored password with the input password
if ($stored_password !== $password) {
    echo json_encode(array('error' => '密碼錯誤'));
    $conn->close();
    exit();
}

// Fetch data for the specified student ID and order by answer_date ascending
$sql = "SELECT u.student_id, u.name, u.gender, s.score, s.incorrect_questions, s.answer_time, s.answer_date
        FROM users u
        JOIN score s ON u.student_id = s.student_id
        WHERE u.student_id = ?
        ORDER BY s.answer_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$data = array();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['name'] = base64_decode($row['name']);
        $data[] = $row;
    }
    echo json_encode(array('data' => $data));
} else {
    echo json_encode(array('error' => '無此學號的數據'));
}

$stmt->close();
$conn->close();
?>
