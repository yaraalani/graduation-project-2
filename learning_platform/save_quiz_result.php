<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

$conn = new mysqli("localhost", "root", "", "learning_platform");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'خطأ في الاتصال بقاعدة البيانات']);
    exit;
}

$student_id = $_SESSION['user_id'];
$quiz_id = intval($_POST['quiz_id'] ?? 0);
$score = intval($_POST['score'] ?? 0);
$total_questions = intval($_POST['total_questions'] ?? 1);

// حساب النسبة
$percentage = $total_questions > 0 ? round(($score / $total_questions) * 100, 2) : 0;
$is_final = 0; // نتيجة سؤال فردي
$created_at = date('Y-m-d H:i:s');
$completed_at = $created_at;

// التحقق من عدم وجود نتيجة سابقة لنفس السؤال
$check_stmt = $conn->prepare("SELECT id FROM quiz_results WHERE student_id = ? AND quiz_id = ? AND is_final = 0");
$check_stmt->bind_param("ii", $student_id, $quiz_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // تحديث النتيجة القديمة
    $update_stmt = $conn->prepare("
        UPDATE quiz_results 
        SET score = ?, total_questions = ?, percentage = ?, completed_at = ?
        WHERE student_id = ? AND quiz_id = ? AND is_final = 0
    ");
    $update_stmt->bind_param("iidsii", $score, $total_questions, $percentage, $completed_at, $student_id, $quiz_id);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم تحديث النتيجة']);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطأ في تحديث النتيجة']);
    }
    $update_stmt->close();
} else {
    // حفظ النتيجة الجديدة
    // التحقق من وجود الأعمدة في الجدول
    $check_columns = $conn->query("SHOW COLUMNS FROM quiz_results LIKE 'created_at'");
    $has_created_at = $check_columns->num_rows > 0;
    
    $check_columns2 = $conn->query("SHOW COLUMNS FROM quiz_results LIKE 'is_final'");
    $has_is_final = $check_columns2->num_rows > 0;
    
    $check_columns3 = $conn->query("SHOW COLUMNS FROM quiz_results LIKE 'percentage'");
    $has_percentage = $check_columns3->num_rows > 0;
    
    if ($has_created_at && $has_is_final && $has_percentage) {
        // الجدول محدث - استخدم جميع الأعمدة
        $stmt = $conn->prepare("
            INSERT INTO quiz_results (student_id, quiz_id, score, total_questions, percentage, is_final, created_at, completed_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiiidiss", $student_id, $quiz_id, $score, $total_questions, $percentage, $is_final, $created_at, $completed_at);
    } else {
        // الجدول قديم - استخدم الأعمدة الأساسية فقط
        $stmt = $conn->prepare("
            INSERT INTO quiz_results (student_id, quiz_id, score, total_questions, completed_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiiis", $student_id, $quiz_id, $score, $total_questions, $completed_at);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم حفظ النتيجة بنجاح']);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطأ في حفظ النتيجة: ' . $stmt->error]);
    }
    $stmt->close();
}

$check_stmt->close();
$conn->close();
?>