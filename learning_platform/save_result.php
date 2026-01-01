<?php
session_start();
header('Content-Type: application/json');

// ✅ التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'المستخدم غير مسجل الدخول']);
    exit;
}

$student_id = intval($_SESSION['user_id']);
$quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
$score = isset($_POST['score']) ? intval($_POST['score']) : 0;
$total_questions = isset($_POST['total_questions']) ? intval($_POST['total_questions']) : 1;
$percentage = isset($_POST['percentage']) ? floatval($_POST['percentage']) : 0;

// حساب النسبة إذا لم تُرسل
if ($percentage == 0 && $total_questions > 0) {
    $percentage = round(($score / $total_questions) * 100, 2);
}

$is_final = ($quiz_id == 0) ? 1 : 0; // quiz_id = 0 → نتيجة نهائية
$created_at = date('Y-m-d H:i:s');
$completed_at = $created_at;

$conn = new mysqli("localhost", "root", "", "learning_platform");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'فشل الاتصال بقاعدة البيانات']);
    exit;
}

// ✅ حفظ أو تحديث مع دعم الأعمدة الكاملة (بما فيها is_final و percentage)
$stmt = $conn->prepare("
    INSERT INTO quiz_results (student_id, quiz_id, score, total_questions, percentage, is_final, completed_at, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        score = VALUES(score),
        total_questions = VALUES(total_questions),
        percentage = VALUES(percentage),
        is_final = VALUES(is_final),
        completed_at = VALUES(completed_at)
");
$stmt->bind_param("iiidisis", $student_id, $quiz_id, $score, $total_questions, $percentage, $is_final, $completed_at, $created_at);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'تم الحفظ بنجاح']);
} else {
    echo json_encode(['success' => false, 'message' => 'خطأ: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
