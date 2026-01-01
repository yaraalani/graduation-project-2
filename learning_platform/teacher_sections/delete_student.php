<?php
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $conn = new mysqli("localhost", "root", "", "learning_platform");
    if ($conn->connect_error) {
        die("فشل الاتصال: " . $conn->connect_error);
    }

    // حذف الطالب
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $conn->close();
}

header("Location: ../teacher_dashboard.php?section=students");
exit();
