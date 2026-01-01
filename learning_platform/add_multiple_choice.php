<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$conn = new mysqli("localhost", "root", "", "learning_platform");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$title = $_POST['title'];
$question = $_POST['question'];
$options = [
    $_POST['option1'],
    $_POST['option2'],
    $_POST['option3'],
    $_POST['option4']
];
$correct = $_POST['correct'];

$content = json_encode([
    "question" => $question,
    "options" => $options,
    "correct" => $correct
], JSON_UNESCAPED_UNICODE);

$stmt = $conn->prepare("INSERT INTO interactive_lessons (title, type, content, teacher_id) VALUES (?, 'multiple_choice', ?, ?)");
$stmt->bind_param("ssi", $title, $content, $teacher_id);

if ($stmt->execute()) {
    header("Location: teacher_dashboard.php?added=mcq_success");
    exit();
} else {
    echo "فشل في الإضافة: " . $stmt->error;
}
