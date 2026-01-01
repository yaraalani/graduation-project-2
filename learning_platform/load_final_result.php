<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

$stmt = $conn->prepare("SELECT score, total_questions, percentage FROM quiz_results WHERE student_id = ? AND is_final = 1 LIMIT 1");
$stmt->execute([$student_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo json_encode(['success' => true, 'result' => $result]);
} else {
    echo json_encode(['success' => false]);
}
