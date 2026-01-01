<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$correct_answers = ['q1' => 'b', 'q2' => 'c', 'q3' => 'a', 'q4' => 'b', 'q5' => 'a'];
$score = 0;

foreach ($correct_answers as $question => $correct) {
    if (isset($_POST[$question]) && $_POST[$question] === $correct) {
        $score++;
    }
}

if ($score <= 2) {
    $level = 'beginner';
    $level_text = 'مبتدئ';
} elseif ($score == 3 || $score == 4) {
    $level = 'intermediate';
    $level_text = 'متوسط';
} else {
    $level = 'advanced';
    $level_text = 'متقدم';
}

// تحديث المستوى بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "learning_platform");
$student_id = $_SESSION['user_id'];
$stmt = $conn->prepare("UPDATE users SET level = ? WHERE id = ?");
$stmt->bind_param("si", $level, $student_id);
$stmt->execute();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نتيجة الاختبار</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <meta http-equiv="refresh" content="6;url=student_dashboard.php">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Cairo', sans-serif;
        }
    </style>
</head>
<body>

<!-- Modal -->
<div class="modal fade show" id="resultModal" tabindex="-1" style="display: block;" aria-labelledby="modalLabel" aria-modal="true" role="dialog">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title w-100" id="modalLabel">🎉 تهانينا!</h5>
      </div>
      <div class="modal-body">
        <p class="fs-4">لقد تم تقييم مستواك على أنه:</p>
        <h3 class="fw-bold text-success"><?= $level_text ?></h3>
        <p class="mt-3">سيتم تحويلك إلى لوحة التحكم خلال ثوانٍ قليلة...</p>
      </div>
    </div>
  </div>
</div>

</body>
</html>
