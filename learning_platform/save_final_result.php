<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح بالوصول']);
    exit;
}

$student_id = (int)$_SESSION['user_id'];
$percentage = isset($_POST['percentage']) ? (float)$_POST['percentage'] : 0;
$score = isset($_POST['score']) ? (int)$_POST['score'] : 0;
$total_questions = isset($_POST['total_questions']) ? (int)$_POST['total_questions'] : 1;


if ($percentage === 0 && $total_questions > 0) {
    $percentage = round(($score / $total_questions) * 100, 2);
}

$conn = new mysqli("localhost", "root", "", "learning_platform");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'خطأ في الاتصال']);
    exit;
}

$now = date('Y-m-d H:i:s');


$stmt1 = $conn->prepare("
    INSERT INTO student_final_results (student_id, percentage, updated_at)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE
        percentage = VALUES(percentage),
        updated_at = VALUES(updated_at)
");
$stmt1->bind_param("ids", $student_id, $percentage, $now);
$final_saved = $stmt1->execute();
$stmt1->close();


$stmt2 = $conn->prepare("
    INSERT INTO quiz_results (student_id, quiz_id, score, total_questions, percentage, is_final, completed_at)
    VALUES (?, 0, ?, ?, ?, 1, ?)
    ON DUPLICATE KEY UPDATE
        score = VALUES(score),
        total_questions = VALUES(total_questions),
        percentage = VALUES(percentage),
        completed_at = VALUES(completed_at)
");
$stmt2->bind_param("iiids", $student_id, $score, $total_questions, $percentage, $now);
$history_saved = $stmt2->execute();
$stmt2->close();

$conn->close();

if ($final_saved) {
    echo json_encode([
        'success' => true,
        'message' => 'تم حفظ النتيجة النهائية بنجاح',
        'percentage' => $percentage
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'فشل في حفظ النتيجة النهائية'
    ]);
}
?>