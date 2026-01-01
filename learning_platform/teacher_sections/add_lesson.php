<?php
session_start();

// التأكد من أنه المستخدم هو أستاذ
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];

// الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "learning_platform");
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// متغير لتخزين أي رسائل خطأ في حال وجودها
$error = '';

// إضافة الدرس
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $level = $_POST['level'];

    // التأكد من أن الحقول ليست فارغة
    if (empty($title) || empty($content) || empty($level)) {
        $error = "الرجاء ملء جميع الحقول!";
    } else {
        // إدخال البيانات في قاعدة البيانات
        $stmt = $conn->prepare("INSERT INTO lessons (title, content, level, teacher_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssi", $title, $content, $level, $teacher_id);

        if ($stmt->execute()) {
            echo "<script>alert('تم إضافة الدرس بنجاح!'); window.location.href =  '../teacher_dashboard.php?section=lessons';</script>";
            exit();
        } else {
            $error = "حدث خطأ أثناء إضافة الدرس.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إضافة درس</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', sans-serif; background-color: #f8f9fa; }
    </style>
</head>
<body>

<div class="container py-4">
    <h3 class="mb-4">📚 إضافة درس جديد</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">عنوان الدرس</label>
            <input type="text" name="title" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">المحتوى</label>
            <textarea name="content" class="form-control" rows="5" required></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">المستوى</label>
            <select name="level" class="form-select" required>
                <option value="beginner">مبتدئ</option>
                <option value="intermediate">متوسط</option>
                <option value="advanced">متقدم</option>
            </select>
        </div>

        <button type="submit" class="btn btn-success">إضافة الدرس</button>
        <a href="../teacher_dashboard.php?section=lessons" class="btn btn-secondary">إلغاء</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
