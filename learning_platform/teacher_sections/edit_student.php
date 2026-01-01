<?php
$id = intval($_GET['id']);
$conn = new mysqli("localhost", "root", "", "learning_platform");
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// جلب بيانات الطالب
$stmt = $conn->prepare("SELECT name, email, level FROM users WHERE id = ? AND role = 'student'");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($name, $email, $level);
$stmt->fetch();
$stmt->close();

// تحديث البيانات إذا تم إرسال الفورم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['name']);
    $new_email = trim($_POST['email']);
    $new_level = trim($_POST['level']);

    $update = $conn->prepare("UPDATE users SET name = ?, email = ?, level = ? WHERE id = ?");
    $update->bind_param("sssi", $new_name, $new_email, $new_level, $id);
    $update->execute();
    $update->close();

    echo "<script>window.location.href = './teacher_dashboard.php?section=students';</script>";
exit();

}
?>

<div class="card p-4">
    <h5 class="mb-4">✏️ تعديل بيانات الطالب</h5>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">الاسم</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">البريد الإلكتروني</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">المستوى</label>
            <select name="level" class="form-select">
                <option value="beginner" <?= $level === 'beginner' ? 'selected' : '' ?>>مبتدئ</option>
                <option value="intermediate" <?= $level === 'intermediate' ? 'selected' : '' ?>>متوسط</option>
                <option value="advanced" <?= $level === 'advanced' ? 'selected' : '' ?>>متقدم</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">حفظ التعديلات</button>
        <a href="./teacher_dashboard.php?section=students" class="btn btn-secondary">إلغاء</a>
    </form>
</div>
