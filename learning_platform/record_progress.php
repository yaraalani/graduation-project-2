<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  exit;
}
$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$lesson_id = intval($data['lesson_id']);
$level     = $data['level'];

$conn = new mysqli("localhost","root","","learning_platform");
$stmt = $conn->prepare("
    INSERT IGNORE INTO user_progress (user_id, lesson_id, level)
    VALUES (?, ?, ?)
");
$stmt->bind_param("iis", $user_id, $lesson_id, $level);
$stmt->execute();
echo json_encode(['success' => $stmt->affected_rows > 0]);
