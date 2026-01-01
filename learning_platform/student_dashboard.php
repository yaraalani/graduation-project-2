<?php
session_start();
$student_id = $_SESSION['user_id'] ?? null;
$conn = new mysqli("localhost", "root", "", "learning_platform");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$user_stmt->bind_param("i", $student_id);
$user_stmt->execute();
$user_stmt->bind_result($name, $email);
$user_stmt->fetch();
$user_stmt->close();

$units_query = $conn->query("
    SELECT u.id AS unit_id, u.title AS unit_title, u.description, u.order_num,
           l.id AS lesson_id, l.title AS lesson_title, l.content AS lesson_content
    FROM units u
    LEFT JOIN lessons l ON u.id = l.unit_id
    ORDER BY u.order_num ASC, l.created_at ASC
");
$units = [];
if ($units_query && $units_query->num_rows > 0) {
    while ($row = $units_query->fetch_assoc()) {
        $unit_id = $row['unit_id'];
        if (!isset($units[$unit_id])) {
            $units[$unit_id] = [
                'unit_id' => $unit_id,
                'unit_title' => $row['unit_title'],
                'unit_description' => $row['description'],
                'unit_order' => $row['order_num'],
                'lessons' => [],
            ];
        }
        if (!empty($row['lesson_id'])) {
            $units[$unit_id]['lessons'][] = [
                'lesson_id' => $row['lesson_id'],
                'lesson_title' => $row['lesson_title'],
                'lesson_content' => $row['lesson_content'],
            ];
        }
    }
}

$quizzes_query = $conn->query("
    SELECT q.*, u.title AS unit_title, r.score AS student_score, r.completed_at
    FROM quizzes q
    LEFT JOIN units u ON q.unit_id = u.id
    LEFT JOIN quiz_results r 
        ON q.id = r.quiz_id AND r.student_id = $student_id
    ORDER BY q.created_at DESC
");

$challenges_query = $conn->query("
    SELECT c.*, cf.file_path, cf.file_name 
    FROM challenges c 
    LEFT JOIN challenge_files cf ON c.id = cf.challenge_id 
    ORDER BY c.start_date DESC
");

$final_result = null;
$final_stmt = $conn->prepare("SELECT score, total_questions, (score/total_questions)*100 AS percentage 
                              FROM quiz_results 
                              WHERE student_id = ? AND quiz_id = 0
                              ORDER BY id DESC LIMIT 1");
$final_stmt->bind_param("i", $student_id);
$final_stmt->execute();
$final_stmt->bind_result($final_score, $final_total, $final_percentage);
if ($final_stmt->fetch()) {
    $final_result = [
        'score' => $final_score,
        'total' => $final_total,
        'percentage' => round($final_percentage)
    ];
}
$final_stmt->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة تحكم الطالب</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        
        .interactive-lesson-card {
            border: 1px solid #4cc9f0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            background-color: #f8fcff;
            transition: 0.3s;
        }
        .interactive-lesson-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(76, 201, 240, 0.2);
        }
        .interactive-choices {
            margin-top: 15px;
        }
        .interactive-choice {
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.2s;
        }
        .interactive-choice:hover {
            background-color: #e6f7ff;
            border-color: #4cc9f0;
        }
        .interactive-choice.selected {
            background-color: #4cc9f0;
            color: white;
            border-color: #4cc9f0;
        }
        .interactive-feedback {
            margin-top: 15px;
            padding: 10px;
            border-radius: 8px;
            display: none;
        }
        .interactive-feedback.correct {
            background-color: #d4edda;
            color: #155724;
        }
        .interactive-feedback.incorrect {
            background-color: #f8d7da;
            color: #721c24;
        }
        .tab-content > .tab-pane:not(.active) {
            display: none;
        }
        .unit-card {
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            transition: 0.3s;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        .unit-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        .lesson-card {
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 10px;
            transition: 0.3s;
            background-color: #f8f9fa;
        }
        .lesson-card:hover {
            background-color: #e9ecef;
            transform: translateX(4px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }
        .unit-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #1a5290;
        }
        .lesson-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #007bff;
        }
        .progress-bar {
            background-color: #28a745;
            border-radius: 10px;
            height: 10px;
            margin-bottom: 15px;
        }
        .nav-link {
            border-radius: 10px;
            color: #0c4a6e;
        }
        .nav-link:hover {
            background-color: #e0f2fe;
            color: #1a5290;
        }
        .nav-link.active {
            background-color: #0c4a6e !important;
            color: white !important;
        }
        .card-header {
            background-color: #f0f4f8;
            border-bottom: 1px solid #ddd;
            padding: 12px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .card-body {
            padding: 16px;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 14px;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004080;
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 14px;
        }
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .alert-info {
            background-color: #d1ecf1;
            border-color: #b7e1e8;
            color: #0c4a6e;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .quiz-card { 
            transition: all 0.3s ease; 
            border-left:4px solid #6c757d; 
        }
        .stars .star { 
            font-size: 26px; 
            color: #ddd; 
            margin: 0 4px; 
        }
        .stars .star.lit { 
            color: #ffc107; 
        }
        .quiz-options {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .quiz-result {
            border-left: 4px solid #28a745;
        }
        .form-check-input:checked {
            background-color: #0c4a6e;
            border-color: #0c4a6e;
        }
        .quiz-feedback {
            transition: all 0.3s ease;
        }
        .correct-answer-box {
            background-color: #f8fff8;
            border-left-color: #28a745 !important;
        }
        .performance-report {
            background-color: #f8f9fa;
            border-left: 4px solid #6c757d;
        }
        .progress {
            background-color: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }
        .progress-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        .text-success {
            color: #28a745 !important;
        }
        .text-warning {
            color: #ffc107 !important;
            font-weight: bold;
        }
        .badge.bg-success {
            background-color: #28a745 !important;
        }
        .badge.bg-danger {
            background-color: #dc3545 !important;
        }
        .badge.bg-warning {
            background-color: #ffc107 !important;
            color: #000 !important;
        }
        .form-check-label.text-success {
            color: #28a745 !important;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <?php
            $profile_pic = "uploads/profiles/" . $student_id . ".jpg";
            $default_pic = "assets/images/default-avatar.jpg";
            ?>
            <div class="me-3">
                <?php if(file_exists($profile_pic)): ?>
                    <img src="<?= $profile_pic ?>?<?= time() ?>" 
                         class="rounded-circle" 
                         width="60" 
                         height="60"
                         style="object-fit: cover;"
                         onerror="this.src='<?= $default_pic ?>'">
                <?php else: ?>
                    <img src="<?= $default_pic ?>" 
                         class="rounded-circle" 
                         width="60" 
                         height="60"
                         style="object-fit: cover;">
                <?php endif; ?>
            </div>
            <div>
                <h2 class="mb-0"> welcome، <?= htmlspecialchars($name) ?></h2>
                <small class="text-muted"><?= htmlspecialchars($email) ?></small>
            </div>
        </div>
        <a href="logout.php" class="btn btn-outline-danger">
            <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
        </a>
    </div>
    
    <ul class="nav nav-tabs mb-3" id="studentTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="units-tab" data-bs-toggle="tab" data-bs-target="#units" 
                    type="button" role="tab" aria-controls="units" aria-selected="true">
                الوحدات الدراسية
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="interactive-tab" data-bs-toggle="tab" data-bs-target="#interactive" 
                    type="button" role="tab" aria-controls="interactive" aria-selected="false">
                تفاعل معنا
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="quizzes-tab" data-bs-toggle="tab" data-bs-target="#quizzes" 
                    type="button" role="tab" aria-controls="quizzes" aria-selected="false">
                الاختبارات
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="challenges-tab" data-bs-toggle="tab" data-bs-target="#challenges" 
                    type="button" role="tab" aria-controls="challenges" aria-selected="false">
                التحديات
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="account-tab" data-bs-toggle="tab" data-bs-target="#account" 
                    type="button" role="tab" aria-controls="account" aria-selected="false">
                الحساب
            </button>
        </li>
    </ul>
    <div class="tab-content" id="studentTabContent">
        <div class="tab-pane fade show active" id="units" role="tabpanel" aria-labelledby="units-tab">
            <?php foreach ($units as $unit): ?>
                <div class="unit-card">
                    <h3 class="unit-title"><?= htmlspecialchars($unit['unit_title']) ?></h3>
                    <p class="unit-description"><?= htmlspecialchars($unit['unit_description']) ?></p>
                    <div class="progress">
                         <?php
                            $total_lessons = count($unit['lessons']);
                            $completed_lessons = 0;
                            if($total_lessons > 0){
                                $progress = ($completed_lessons / $total_lessons) * 100;
                            } else {
                                $progress = 0;
                            }
                            $progress = min($progress, 100);
                         ?>
                        <div class="progress-bar" role="progressbar" style="width: <?= $progress ?>%" 
                             aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <?php if (count($unit['lessons']) > 0): ?>
                        <h4 class="mt-3">الدروس:</h4>
                        <?php foreach ($unit['lessons'] as $lesson): ?>
                            <div class="lesson-card">
                                <h5 class="lesson-title"><?= htmlspecialchars($lesson['lesson_title']) ?></h5>
                                <p><?= mb_strimwidth(strip_tags($lesson['lesson_content']), 0, 100, '...') ?></p>
                                <a href="view_lesson.php?id=<?= $lesson['lesson_id'] ?>" class="btn btn-primary btn-sm">عرض الدرس</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            لا يوجد دروس متاحة لهذه الوحدة حالياً.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="tab-pane fade" id="interactive" role="tabpanel" aria-labelledby="interactive-tab">
            <h5 class="mb-4">💡 تفاعل معنا</h5>
            <p class="text-muted mb-4">اختر أحد الدروس التفاعلية التالية لتختبر فهمك:</p>
            <?php
            
            $interactive_lessons = $conn->query("SELECT * FROM interactive_lessons ORDER BY created_at DESC");
            if ($interactive_lessons && $interactive_lessons->num_rows > 0): ?>
                <div class="row">
                    <?php while ($lesson = $interactive_lessons->fetch_assoc()): ?>
                        <div class="col-md-6 mb-4">
                            <div class="interactive-lesson-card" id="interactive-lesson-<?= $lesson['id'] ?>">
                                <h5><?= htmlspecialchars($lesson['title']) ?></h5>
                                <p><?= htmlspecialchars($lesson['content']) ?></p>
                                <div class="interactive-choices">
                                    <?php 
                                    $choices = json_decode($lesson['choices'], true);
                                    if (is_array($choices)) {
                                        foreach ($choices as $index => $choice): ?>
                                            <div class="interactive-choice" 
                                                 onclick="selectChoice(this, <?= $lesson['id'] ?>, '<?= addslashes($choice) ?>', '<?= addslashes($lesson['correct_answer']) ?>')">
                                                <?= htmlspecialchars($choice) ?>
                                            </div>
                                        <?php endforeach; 
                                    }?>
                                </div>
                                <div class="interactive-feedback" id="feedback-<?= $lesson['id'] ?>"></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    لا توجد دروس تفاعلية متاحة حالياً.
                </div>
            <?php endif; ?>
        </div>
        <div class="tab-pane fade" id="quizzes" role="tabpanel" aria-labelledby="quizzes-tab">
            <h5 class="mb-3">📑 قائمة الاختبارات</h5>
            <?php 
            if ($quizzes_query && $quizzes_query->num_rows > 0): 
                $quizzes_query->data_seek(0);
                while ($quiz = $quizzes_query->fetch_assoc()): 
                    $options = json_decode($quiz['options'] ?? '[]', true);
                    if (!is_array($options)) $options = [];
            ?>
                <div class="quiz-card mb-4 p-3 border rounded bg-white" 
                     data-quiz-id="<?= $quiz['id'] ?>" id="quiz-card-<?= $quiz['id'] ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5><?= htmlspecialchars($quiz['question'] ?? '') ?></h5>
                            <p class="text-muted">الوحدة: <?= htmlspecialchars($quiz['unit_title'] ?? '') ?></p>
                        </div>
                    </div>
                    <div class="quiz-options mt-3">
                        <?php if (!empty($options)): ?>
                            <?php foreach($options as $key => $option): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" 
                                           name="quiz_<?= $quiz['id'] ?>" 
                                           id="quiz_<?= $quiz['id'] ?>_option_<?= $key ?>"
                                           value="<?= htmlspecialchars($option) ?>">
                                    <label class="form-check-label" for="quiz_<?= $quiz['id'] ?>_option_<?= $key ?>">
                                        <?= htmlspecialchars($option) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            
                            <button class="btn btn-primary btn-sm mt-2 submit-quiz" 
                                    data-quiz-id="<?= $quiz['id'] ?>"
                                    data-correct='<?= json_encode($quiz['correct_answer'] ?? '') ?>'>
                                تقديم الإجابة
                            </button>
                        <?php else: ?>
                            <div class="alert alert-warning">لا توجد خيارات متاحة لهذا الاختبار</div>
                        <?php endif; ?>
                        <div class="quiz-feedback mt-2" id="quiz-feedback-<?= $quiz['id'] ?>" style="display:none;"></div>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <div class="alert alert-info">لا توجد اختبارات متاحة حالياً.</div>
            <?php endif; ?>
        </div>
        <div class="tab-pane fade" id="challenges" role="tabpanel" aria-labelledby="challenges-tab">
            <h5 class="mb-3">🎯 التحديات الحالية</h5>
            <div class="mb-3">
                <a href="pronunciation_practice.php" class="btn btn-primary mb-3">
                    <i class="fas fa-microphone"></i> تدريب النطق
                </a>
                <p class="text-muted">تدرب على نطق الجمل باللغة الإنجليزية وتحقق من صحة نطقك باستخدام تقنية التعرف على الصوت.</p>
            </div>
            <?php 
            if ($challenges_query && $challenges_query->num_rows > 0): 
                $challenges_query->data_seek(0);
                while ($challenge = $challenges_query->fetch_assoc()): 
            ?>
                <div class="mb-3 p-3 border rounded bg-white">
                    <h6><?= htmlspecialchars($challenge['title'] ?? '') ?></h6>
                    <p><?= htmlspecialchars($challenge['description'] ?? '') ?></p>
                    <p><strong>الهدف:</strong> <?= htmlspecialchars($challenge['goal'] ?? '') ?> | 
                       <strong>النوع:</strong> <?= htmlspecialchars($challenge['type'] ?? '') ?></p>
                    <p>
                        <strong>الفترة:</strong>
                        <?php
                        $start_display = (!empty($challenge['start_date']) && $challenge['start_date'] !== '0000-00-00 00:00:00')
                            ? date('Y-m-d', strtotime($challenge['start_date'])) : 'غير محدد';
                        $end_display = (!empty($challenge['end_date']) && $challenge['end_date'] !== '0000-00-00 00:00:00')
                            ? date('Y-m-d', strtotime($challenge['end_date'])) : 'غير محدد';
                        echo $start_display . ' → ' . $end_display;
                        ?>
                    </p>
                    <?php
                    if (isset($challenge['id'])) {
                        $challenge_id = $challenge['id'];
                        $file_result = $conn->query("SELECT * FROM challenge_files WHERE challenge_id = $challenge_id LIMIT 1");
                        if ($file_result && $file_result->num_rows > 0):
                            $file = $file_result->fetch_assoc();
                    ?>
                            <p>
                                📄 <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    تحميل ملف التحدي (<?= htmlspecialchars($file['file_name']) ?>)
                                </a>
                            </p>
                    <?php 
                        endif;
                    }
                    ?>
                </div>
            <?php 
                endwhile; 
            else: 
            ?>
                <div class="alert alert-info">
                    لا توجد تحديات متاحة حالياً.
                </div>
            <?php endif; ?>
        </div>
        <div class="tab-pane fade" id="account" role="tabpanel" aria-labelledby="account-tab">
            <h5 class="mb-3">🧑 تعديل الحساب</h5>
            <?php if(isset($_SESSION['profile_update_message'])): ?>
                <div class="alert alert-<?= $_SESSION['profile_update_status'] ? 'success' : 'danger' ?> alert-dismissible fade show">
                    <?= $_SESSION['profile_update_message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['profile_update_message'], $_SESSION['profile_update_status']); ?>
            <?php endif; ?>
            <form action="update_profile.php" method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">الاسم الكامل</label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?= htmlspecialchars($name) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($email) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">كلمة المرور الجديدة (اتركه فارغاً إذا لم ترغب بالتغيير)</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                            <input type="password" name="confirm_password" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3 text-center">
                            <label class="form-label">الصورة الشخصية</label>
                            <div class="profile-picture mb-2">
                                <?php
                                $profile_pic = "uploads/profiles/$student_id.jpg";
                                if(file_exists($profile_pic)) {
                                    echo '<img src="'.$profile_pic.'?'.time().'" class="rounded-circle" width="150" height="150">';
                                } else {
                                    echo '<i class="fas fa-user-circle" style="font-size: 150px; color: #ccc;"></i>';
                                }
                                ?>
                            </div>
                            <input type="file" name="profile_picture" class="form-control" accept="image/jpeg,image/png">
                            <small class="text-muted">الصيغ المسموحة: JPG, PNG (الحجم الأقصى 2MB)</small>
                        </div>
                    </div>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ التعديلات
                    </button>
                    <button type="reset" class="btn btn-outline-secondary">
                        <i class="fas fa-undo"></i> إعادة تعيين
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

document.addEventListener('DOMContentLoaded', function() {
    
    var tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabEls.forEach(function(tabEl) {
        tabEl.addEventListener('click', function (e) {
            e.preventDefault();
            var tab = new bootstrap.Tab(tabEl);
            tab.show();
        });
    });
    
    if (window.location.hash) {
        var hash = window.location.hash;
        var triggerEl = document.querySelector(`[data-bs-target="${hash}"]`);
        if (triggerEl) {
            var tab = new bootstrap.Tab(triggerEl);
            tab.show();
        }
    }
    
    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(function(tab) {
        tab.addEventListener('shown.bs.tab', function (e) {
            var target = e.target.getAttribute('data-bs-target');
            if (target && target.startsWith('#')) {
                window.location.hash = target;
            }
        });
    });
});

function selectChoice(element, lessonId, selectedChoice, correctAnswer) {
    
    element.parentElement.querySelectorAll('.interactive-choice').forEach(choice => {
        choice.classList.remove('selected');
    });
    
    element.classList.add('selected');
    
    const feedbackDiv = document.getElementById(`feedback-${lessonId}`);
    feedbackDiv.style.display = 'block';
    if (selectedChoice === correctAnswer) {
        feedbackDiv.className = 'interactive-feedback correct';
        feedbackDiv.innerHTML = '<i class="fas fa-check-circle"></i> إجابة صحيحة! أحسنت 👏';
    } else {
        feedbackDiv.className = 'interactive-feedback incorrect';
        feedbackDiv.innerHTML = `<i class="fas fa-times-circle"></i> إجابة خاطئة 😢<br>الإجابة الصحيحة هي: <b>${correctAnswer}</b>`;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const studentId = <?= json_encode($student_id) ?>;
    let correctAnswers = 0;
    const totalQuestions = document.querySelectorAll('.submit-quiz').length;
    let wrongQuestions = []; // قائمة الأسئلة الخاطئة فقط
    let correctQuestionIds = []; // تتبع الأسئلة الصحيحة
    let isRetryMode = false; // حالة إعادة المحاولة
    let originalCorrectAnswers = 0; // عدد الإجابات الصحيحة الأصلية

    
    function saveQuizResult(quizId, score, totalQuestions, percentage) {
        fetch('save_result.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `quiz_id=${quizId}&score=${score}&total_questions=${totalQuestions}&percentage=${percentage}`
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                console.warn('⚠️ لم يتم حفظ إجابة السؤال:', data.message);
            }
        })
        .catch(err => console.error('❌ خطأ أثناء حفظ إجابة السؤال:', err));
    }

    
    function saveFinalResult(score, total, percentage) {
        fetch('save_final_result.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `score=${score}&total_questions=${total}&percentage=${percentage}`
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                console.warn('⚠️ لم يتم حفظ النتيجة النهائية:', data.message);
            }
        })
        .catch(err => console.error('❌ خطأ أثناء حفظ النتيجة النهائية:', err));
    }

    function setupQuizListeners() {
        document.querySelectorAll('.submit-quiz').forEach(button => {
            if (button.dataset.bound) return;
            button.dataset.bound = true;
            button.addEventListener('click', function () {
                const quizId = this.dataset.quizId; // بدون parseInt لتجنب NaN
                let correctAnswer = '';
                try {
                    correctAnswer = JSON.parse(this.dataset.correct);
                } catch (e) {
                    correctAnswer = this.dataset.correct || '';
                }
                correctAnswer = (typeof correctAnswer === 'string') ? correctAnswer.trim() : '';

                const selected = document.querySelector(`input[name="quiz_${quizId}"]:checked`);
                const feedbackDiv = document.getElementById(`quiz-feedback-${quizId}`);
                const quizCard = this.closest('.quiz-card');

                if (!selected) {
                    alert('الرجاء اختيار إجابة قبل التقديم');
                    return;
                }

                const chosen = selected.value.trim();
                const isCorrect = chosen === correctAnswer;
                const score = isCorrect ? 1 : 0;
                const percentage = isCorrect ? 100 : 0;

               
                saveQuizResult(quizId, score, 1, percentage);

                feedbackDiv.style.display = 'block';
                feedbackDiv.innerHTML = isCorrect
                    ? `<div class="alert alert-success"><i class="fas fa-check-circle"></i> إجابة صحيحة! أحسنت 👏</div>`
                    : `<div class="alert alert-danger"><i class="fas fa-times-circle"></i> إجابة خاطئة 😢<br>الإجابة الصحيحة هي: <b>${correctAnswer}</b></div>`;

                
                quizCard.querySelectorAll('input[type="radio"]').forEach(i => i.disabled = true);
                this.disabled = true;

                // تحديث الإحصاءات
                if (isCorrect) {
                    correctAnswers++;
                    if (!correctQuestionIds.includes(quizId)) {
                        correctQuestionIds.push(quizId);
                    }
                    wrongQuestions = wrongQuestions.filter(q => q.quizId !== quizId);
                } else {
                    const exists = wrongQuestions.some(q => q.quizId === quizId);
                    if (!exists) {
                        wrongQuestions.push({ quizId: quizId, correctAnswer: correctAnswer, quizCard: quizCard });
                    }
                    correctQuestionIds = correctQuestionIds.filter(id => id !== quizId);
                }

                // التحقق من إتمام جميع الأسئلة
                if (isRetryMode) {
                    const retryQuestions = document.querySelectorAll('.quiz-card.retry-mode:not(.hidden)');
                    const answeredRetry = Array.from(retryQuestions).filter(card => {
                        const submitBtn = card.querySelector('.submit-quiz');
                        return submitBtn && submitBtn.disabled;
                    }).length;

                    if (answeredRetry === retryQuestions.length && retryQuestions.length > 0) {
                        const updatedCorrect = Math.min(totalQuestions, originalCorrectAnswers + correctAnswers);
                        const updatedPercentage = Math.min(100, Math.max(0, Math.round((updatedCorrect / totalQuestions) * 100)));
                        const updatedStars = '⭐'.repeat(Math.min(4, Math.round(updatedPercentage / 25)));
                        showUpdatedResult(updatedCorrect, updatedPercentage, updatedStars);
                    }
                } else {
                    const answered = document.querySelectorAll('.submit-quiz:disabled').length;
                    if (answered === totalQuestions) {
                        originalCorrectAnswers = correctAnswers;
                        const percentage = Math.min(100, Math.max(0, Math.round((correctAnswers / totalQuestions) * 100)));
                        const stars = '⭐'.repeat(Math.min(4, Math.round(percentage / 25)));
                        showFinalResult(percentage, stars);
                    }
                }
            });
        });
    }

    function showFinalResult(percentage, stars) {
        const resultDiv = document.createElement('div');
        resultDiv.id = 'final-result';
        resultDiv.className = 'alert alert-info mt-4 text-center';
        let evaluation = '';
        if (percentage >= 80) evaluation = 'ممتاز 🏆';
        else if (percentage >= 60) evaluation = 'جيد 👍';
        else if (percentage >= 40) evaluation = 'مقبول 😐';
        else evaluation = 'ضعيف 😞';

        let retryButton = '';
        if (wrongQuestions.length > 0) {
            retryButton = `
                <div class="mt-3">
                    <button class="btn btn-warning btn-lg" id="retry-wrong-btn" onclick="retryWrongQuestions()">
                        <i class="fas fa-redo"></i> إعادة الأسئلة الخاطئة (${wrongQuestions.length})
                    </button>
                </div>
            `;
        }

        resultDiv.innerHTML = `
            <h4><i class="fas fa-trophy text-warning"></i> النتيجة النهائية</h4>
            <p>عدد الإجابات الصحيحة: <strong>${correctAnswers}</strong> من <strong>${totalQuestions}</strong></p>
            <p>النسبة المئوية: <strong>${percentage}%</strong></p>
            <p class="fs-4">${stars}</p>
            <p class="fw-bold">${evaluation}</p>
            ${retryButton}
        `;
        document.querySelector('#quizzes').appendChild(resultDiv);

       
        saveFinalResult(correctAnswers, totalQuestions, percentage);
    }

    window.retryWrongQuestions = function() {
        if (wrongQuestions.length === 0) {
            alert('لا توجد أسئلة خاطئة لإعادة المحاولة');
            return;
        }
        isRetryMode = true;
        correctAnswers = 0;

        const finalResultDiv = document.getElementById('final-result');
        if (finalResultDiv) {
            finalResultDiv.style.display = 'none';
        }

        const wrongQuestionIds = wrongQuestions.map(q => q.quizId);
        document.querySelectorAll('.quiz-card').forEach(card => {
            const quizId = card.dataset.quizId;
            if (wrongQuestionIds.includes(quizId)) {
                card.classList.add('retry-mode');
                card.classList.remove('hidden');
                card.style.display = 'block';
                card.querySelectorAll('input[type="radio"]').forEach(input => {
                    input.checked = false;
                    input.disabled = false;
                });
                const submitBtn = card.querySelector('.submit-quiz');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.dataset.bound = false;
                }
                const feedbackDiv = card.querySelector('.quiz-feedback');
                if (feedbackDiv) {
                    feedbackDiv.style.display = 'none';
                    feedbackDiv.innerHTML = '';
                }
            } else {
                card.classList.add('hidden');
                card.style.display = 'none';
                card.classList.remove('retry-mode');
            }
        });

        const retryHeader = document.createElement('div');
        retryHeader.id = 'retry-header';
        retryHeader.className = 'alert alert-warning mt-3 mb-3';
        retryHeader.innerHTML = `
            <h5><i class="fas fa-redo"></i> إعادة الأسئلة الخاطئة</h5>
            <p>يرجى الإجابة على الأسئلة التالية مرة أخرى (${wrongQuestions.length} سؤال):</p>
        `;
        const quizzesDiv = document.querySelector('#quizzes');
        const firstRetryQuiz = quizzesDiv.querySelector('.quiz-card.retry-mode:not(.hidden)');
        if (firstRetryQuiz) {
            quizzesDiv.insertBefore(retryHeader, firstRetryQuiz);
        } else {
            quizzesDiv.insertBefore(retryHeader, quizzesDiv.firstChild);
        }

        setupQuizListeners();
    };

    function showUpdatedResult(updatedCorrect, updatedPercentage, updatedStars) {
        const retryHeader = document.getElementById('retry-header');
        if (retryHeader) retryHeader.remove();
        const oldResult = document.getElementById('final-result');
        if (oldResult) oldResult.remove();

        wrongQuestions = wrongQuestions.filter(q => {
            const card = document.querySelector(`.quiz-card[data-quiz-id="${q.quizId}"]`);
            if (card) {
                const submitBtn = card.querySelector('.submit-quiz');
                const feedbackDiv = card.querySelector('.quiz-feedback');
                if (submitBtn && submitBtn.disabled && feedbackDiv && feedbackDiv.innerHTML.includes('alert-success')) {
                    return false;
                }
            }
            return true;
        });

        const resultDiv = document.createElement('div');
        resultDiv.id = 'final-result';
        resultDiv.className = 'alert alert-success mt-4 text-center';
        let evaluation = '';
        if (updatedPercentage >= 80) evaluation = 'ممتاز 🏆';
        else if (updatedPercentage >= 60) evaluation = 'جيد 👍';
        else if (updatedPercentage >= 40) evaluation = 'مقبول 😐';
        else evaluation = 'ضعيف 😞';

        let retryButton = '';
        if (wrongQuestions.length > 0) {
            retryButton = `
                <div class="mt-3">
                    <button class="btn btn-warning btn-lg" id="retry-wrong-btn" onclick="retryWrongQuestions()">
                        <i class="fas fa-redo"></i> إعادة الأسئلة الخاطئة (${wrongQuestions.length})
                    </button>
                </div>
            `;
        }

        resultDiv.innerHTML = `
            <h4><i class="fas fa-trophy text-warning"></i> النتيجة المحدثة</h4>
            <p>عدد الإجابات الصحيحة: <strong>${updatedCorrect}</strong> من <strong>${totalQuestions}</strong></p>
            <p>النسبة المئوية: <strong>${updatedPercentage}%</strong></p>
            <p class="fs-4">${updatedStars}</p>
            <p class="fw-bold">${evaluation}</p>
            <p class="text-muted mt-2"><small>تم تحديث النتيجة بعد إعادة المحاولة</small></p>
            ${retryButton}
        `;
        document.querySelector('#quizzes').appendChild(resultDiv);

        
        saveFinalResult(updatedCorrect, totalQuestions, updatedPercentage);

        document.querySelectorAll('.quiz-card').forEach(card => {
            card.style.display = 'block';
            card.classList.remove('hidden', 'retry-mode');
        });
    }

    setupQuizListeners();
});
</script>
</body>
</html>
