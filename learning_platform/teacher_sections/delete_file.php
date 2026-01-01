<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$challenge_id = intval($_GET['challenge_id']);

$conn = new mysqli("localhost", "root", "", "learning_platform");

// التحقق من ملكية التحدي
$challenge = $conn->query("SELECT id FROM challenges WHERE id = $challenge_id AND user_id = $teacher_id")->fetch_assoc();
if (!$challenge) {
    die("لا تملك صلاحية حذف هذا الملف");
}

// جلب معلومات الملف وحذفه
$file = $conn->query("SELECT * FROM challenge_files WHERE challenge_id = $challenge_id")->fetch_assoc();
if ($file) {
    if (file_exists($file['file_path'])) {
        unlink($file['file_path']);
    }
    $conn->query("DELETE FROM challenge_files WHERE challenge_id = $challenge_id");
}

header("Location: teacher_dashboard.php?section=challenges&edit=$challenge_id");
exit;