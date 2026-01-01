<?php
session_start();
$conn = new mysqli("localhost", "root", "", "learning_platform");

$lesson_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($lesson_id === 0) {
    echo "<div class='alert alert-danger' role='alert'>الدرس غير موجود!</div>";
    exit;
}

$result = $conn->query("SELECT title, content, unit_id FROM lessons WHERE id = $lesson_id");
$lesson = $result->fetch_assoc();

if (!$lesson) {
    echo "<div class='alert alert-danger' role='alert'>الدرس غير موجود!</div>";
    exit;
}

// جلب معلومات الوحدة
$unit_result = $conn->query("SELECT title FROM units WHERE id = {$lesson['unit_id']}");
$unit = $unit_result->fetch_assoc();

// Get the next lesson ID
$next_lesson_query = $conn->query("SELECT id FROM lessons WHERE unit_id = {$lesson['unit_id']} AND id > $lesson_id ORDER BY id ASC LIMIT 1");
$next_lesson = $next_lesson_query->fetch_assoc();
$next_lesson_id = ($next_lesson) ? $next_lesson['id'] : 0;

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>عرض الدرس: <?= htmlspecialchars($lesson['title']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .lesson-container {
            background-color: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-top: 30px;
            transition: transform 0.3s ease;
        }
        .lesson-container:hover {
            transform: translateY(-5px);
        }
        .lesson-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }
        .unit-info {
            font-size: 16px;
            color: #7f8c8d;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }
        .unit-info i {
            margin-left: 8px;
            color: #3498db;
        }
        .lesson-content {
            font-size: 18px;
            line-height: 1.9;
            color: #34495e;
            margin-bottom: 25px;
            white-space: pre-line;
        }
        .back-button {
            margin-top: 30px;
        }
        .back-button i {
            margin-right: 5px;
        }
        /* أنماط إضافية للتفاعل */
        .interactive-section {
            background-color: #f0f0f0;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        .interactive-section h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        .highlight-box {
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .key-point {
            background-color: #e3f2fd;
            padding: 12px;
            border-radius: 8px;
            margin: 15px 0;
            position: relative;
            padding-left: 50px;
        }
        .key-point:before {
            content: "!";
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            width: 30px;
            height: 30px;
            background-color: #3498db;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .visual-separator {
            border: 0;
            height: 1px;
            background-image: linear-gradient(to right, rgba(0,0,0,0), rgba(52, 152, 219, 0.75), rgba(0,0,0,0));
            margin: 25px 0;
        }
        .quiz-question {
            font-size: 18px;
            margin-bottom: 10px;
            color: #3498db;
        }
        .quiz-options {
            list-style: none;
            padding: 0;
            margin-bottom: 15px;
        }
        .quiz-options li {
            background-color: #e0e0e0;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .quiz-options li:hover {
            background-color: #d0d0d0;
        }
        .quiz-answer {
            font-weight: bold;
            margin-top: 10px;
            padding: 10px;
            border-radius: 8px;
            display: none;
        }
        .correct-answer {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .wrong-answer {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .explanation {
            margin-top: 15px;
            padding: 10px;
            border-radius: 8px;
            background-color: #e2e3e4;
            color: #383d41;
            border: 1px solid #d3d6da;
            display: none;
        }
        .next-lesson-button {
            margin-top: 20px;
        }
        .rating-star {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .rating-star:hover {
            transform: scale(1.2);
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="progress mb-3" style="height: 8px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" 
             style="width: 75%;" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
    </div>

    <div class="lesson-container">
        <h2 class="lesson-title"><?= htmlspecialchars($lesson['title']) ?></h2>
        <div class="unit-info">
            <i class="fas fa-book"></i>
            <span>الوحدة: <?= htmlspecialchars($unit['title']) ?></span>
        </div>
        <div class="lesson-content">
            <?php
                // Remove HTML tags from the lesson content
                $lesson_content = strip_tags($lesson['content']);
                // Replace newlines with paragraph tags for better formatting
                $lesson_content = str_replace("\n", "<p style=\"margin-bottom: 15px; line-height: 1.8;\"></p>", $lesson_content);

                echo $lesson_content;
            ?>
        </div>

        <div class="visual-separator"></div>

        <div class="highlight-box">
            <h4><i class="fas fa-star me-2"></i>النقاط الرئيسية</h4>
            <ul class="key-points-list">
                <!-- سيتم ملؤها جافاسكريبت -->
            </ul>
        </div>

        <div class="interactive-section mt-4">
            <h3><i class="fas fa-lightbulb me-2"></i>جرب بنفسك</h3>
            <p>هذه مساحة لتجربة ما تعلمته في الدرس:</p>
            
            <div class="mb-3">
                <label for="practiceInput" class="form-label">اكتب ملخصًا لما فهمته:</label>
                <textarea class="form-control" id="practiceInput" rows="3"></textarea>
            </div>
            <button class="btn btn-sm btn-outline-primary" id="saveNote">حفظ الملاحظة</button>
            <div id="noteSaved" class="text-success mt-2" style="display:none;">
                <i class="fas fa-check-circle"></i> تم حفظ ملاحظتك بنجاح
            </div>
        </div>

        <?php if ($next_lesson_id > 0): ?>
            <a href="view_lesson.php?id=<?= $next_lesson_id ?>" class="btn btn-primary next-lesson-button">
                الدرس التالي <i class="fas fa-arrow-left"></i>
            </a>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="rating-section">
                <span class="me-2">كيف تقيم هذا الدرس؟</span>
                <i class="far fa-star rating-star" data-rating="1"></i>
                <i class="far fa-star rating-star" data-rating="2"></i>
                <i class="far fa-star rating-star" data-rating="3"></i>
                <i class="far fa-star rating-star" data-rating="4"></i>
                <i class="far fa-star rating-star" data-rating="5"></i>
            </div>
            <button class="btn btn-sm btn-outline-success" id="shareLesson">
                <i class="fas fa-share-alt"></i> مشاركة الدرس
            </button>
        </div>

        <a href="student_dashboard.php" class="btn btn-secondary back-button">
            <i class="fas fa-arrow-left"></i>
            الرجوع إلى لوحة التحكم
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // حفظ الملاحظات
    document.getElementById('saveNote').addEventListener('click', function() {
        const note = document.getElementById('practiceInput').value;
        if(note.trim() !== '') {
            localStorage.setItem('lessonNote_<?= $lesson_id ?>', note);
            document.getElementById('noteSaved').style.display = 'block';
            setTimeout(() => {
                document.getElementById('noteSaved').style.display = 'none';
            }, 3000);
        }
    });
    
    // تحميل الملاحظة المحفوظة إن وجدت
    window.addEventListener('DOMContentLoaded', () => {
        const savedNote = localStorage.getItem('lessonNote_<?= $lesson_id ?>');
        if(savedNote) {
            document.getElementById('practiceInput').value = savedNote;
        }
        
        // إنشاء نقاط رئيسية تلقائية
        const content = document.querySelector('.lesson-content').textContent;
        const sentences = content.split(/[.!؟]/).filter(s => s.trim().length > 30);
        const keyPointsList = document.querySelector('.key-points-list');
        
        if(sentences.length > 0) {
            const selectedSentences = sentences.slice(0, Math.min(3, sentences.length));
            selectedSentences.forEach(sentence => {
                const li = document.createElement('li');
                li.className = 'key-point mb-2';
                li.textContent = sentence.trim();
                keyPointsList.appendChild(li);
            });
        } else {
            // إذا لم يتم العثور على جمل مناسبة، إضافة رسالة افتراضية
            const li = document.createElement('li');
            li.className = 'text-muted';
            li.textContent = 'لا توجد نقاط رئيسية محددة لهذا الدرس';
            keyPointsList.appendChild(li);
        }
    });

    // نظام التقييم
    document.querySelectorAll('.rating-star').forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.getAttribute('data-rating');
            // هنا يمكنك إرسال التقييم إلى الخادم
            alert(`شكرًا لتقييمك! قيمت الدرس بـ ${rating} نجوم`);
            
            // تحديث النجوم
            document.querySelectorAll('.rating-star').forEach((s, index) => {
                if(index < rating) {
                    s.classList.remove('far');
                    s.classList.add('fas', 'text-warning');
                } else {
                    s.classList.remove('fas', 'text-warning');
                    s.classList.add('far');
                }
            });
        });
    });
    
    // زر المشاركة
    document.getElementById('shareLesson').addEventListener('click', function() {
        if(navigator.share) {
            navigator.share({
                title: '<?= htmlspecialchars($lesson['title']) ?>',
                text: 'اكتشف هذا الدرس الرائع:',
                url: window.location.href
            }).catch(err => {
                console.log('Error sharing:', err);
            });
        } else {
            // Fallback for browsers that don't support Web Share API
            prompt('انسخ الرابط لمشاركة الدرس:', window.location.href);
        }
    });
</script>
</body>
</html>