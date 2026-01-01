<?php
session_start();
$section = $_GET['section'] ?? 'students'; 


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$name = $_SESSION['name'];
$teacher_id = $_SESSION['user_id']; 

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة تحكم الأستاذ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f4f6f9;
        }
        .sidebar {
            height: 100vh;
            background: #2f3e75;
            color: #fff;
            padding: 20px;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            display: block;
            margin-bottom: 15px;
            font-weight: 500;
        }
        .sidebar a:hover {
            color: #ffd166;
        }
        .header {
            background: #fff;
            padding: 15px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }
        .content {
            padding: 30px;
        }
        body { font-family: 'Tajawal', sans-serif; background: #f8f9fa; }
        .nav-tabs .nav-link.active { background-color: #4361ee; color: #fff; }
        .nav-tabs .nav-link { color: #4361ee; }
        .logout-button {
            position: absolute;
            top: 15px; 
            left: 15px; 
        }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>👨‍🏫 أهلاً أستاذ <?= htmlspecialchars($name) ?></h3>
        <a href="logout.php" class="btn btn-danger">تسجيل الخروج <i class="bi bi-box-arrow-right"></i></a>
    </div>


    <ul class="nav nav-tabs mb-3" id="teacherTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?= $section === 'students' ? 'active' : '' ?>" href="?section=students">👥 إدارة الطلاب</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $section === 'lessons' ? 'active' : '' ?>" href="?section=lessons">📚 إدارة الدروس</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $section === 'quizzes' ? 'active' : '' ?>" href="?section=quizzes">❓ إدارة الاختبارات</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $section === 'challenges' ? 'active' : '' ?>" href="?section=challenges">🏆 إدارة التحديات</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $section === 'results' ? 'active' : '' ?>" href="?section=results">📈 نتائج الطلاب</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $section === 'profile' ? 'active' : '' ?>" href="?section=profile">⚙️ الملف الشخصي</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $section === 'languages' ? 'active' : '' ?>"
           href="?section=languages">
            🌐 إدارة اللغات
        </a>
    </li>
</ul>


    <div class="tab-content mt-3">

    <?php if ($section === 'students'): ?>
        <div class="tab-pane fade show active" id="students" role="tabpanel">
            <?php include 'teacher_sections/students.php'; ?>
        </div>
    <?php elseif ($section === 'edit_student' && isset($_GET['id'])): ?>
        <div class="tab-pane fade show active" id="students" role="tabpanel">
            <?php include 'teacher_sections/edit_student.php'; ?>
        </div>
        <?php elseif ($section === 'languages'): ?>
        <div class="tab-pane fade show active" id="languages" role="tabpanel">
            <?php include 'teacher_sections/languages.php'; ?>
        </div>
    <?php elseif ($section === 'lessons'): ?>
        <div class="tab-pane fade show active" id="lessons" role="tabpanel">
            <?php include 'teacher_sections/lessons.php'; ?>
        </div>


    <?php elseif ($section === 'quizzes'): ?>
        <div class="tab-pane fade show active" id="quizzes" role="tabpanel">
            <?php include 'teacher_sections/quizzes.php'; ?>
        </div>
    <?php elseif ($section === 'challenges'): ?>
        <div class="tab-pane fade show active" id="challenges" role="tabpanel">
            <?php include 'teacher_sections/challenges.php'; ?>
        </div>
    <?php elseif ($section === 'results'): ?>
        <div class="tab-pane fade show active" id="results" role="tabpanel">
            <?php include 'teacher_sections/results.php'; ?>
        </div>
    <?php elseif ($section === 'profile'): ?>
        <div class="tab-pane fade show active" id="profile" role="tabpanel">
            <?php include 'teacher_sections/profile.php'; ?>
        </div>
    <?php endif; ?>


</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>