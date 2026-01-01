<?php
session_start();
$conn = new mysqli("localhost", "root", "", "learning_platform");

$lesson_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($lesson_id === 0) {
    echo "<div class='alert alert-danger' role='alert'>الدرس غير موجود!</div>";
    exit;
}

$result = $conn->query("SELECT * FROM interactive_lessons WHERE id = $lesson_id");
$interactive_lesson = $result->fetch_assoc();

if (!$interactive_lesson) {
    echo "<div class='alert alert-danger' role='alert'>الدرس غير موجود!</div>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>عرض الدرس التفاعلي: <?= htmlspecialchars($interactive_lesson['title']) ?></title>
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
        }
        .lesson-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }
        .back-button {
            margin-top: 30px;
        }
        .back-button i {
            margin-right: 5px;
        }
    </style>
</head>
<body class="bg-light">

    <div class="container py-4">
        <div class="lesson-container">
            <h2 class="lesson-title"><?= htmlspecialchars($interactive_lesson['title']) ?></h2>
            <p><strong>المستوى:</strong> <?= htmlspecialchars($interactive_lesson['level']) ?></p>
            <p><strong>النوع:</strong> <?= htmlspecialchars($interactive_lesson['type']) ?></p>
            <div class="lesson-content">
                <?php
                // Output the lesson content, handling HTML entities
                echo htmlspecialchars_decode($interactive_lesson['content']);
                ?>
            </div>

            <?php if (!empty($interactive_lesson['choices'])): ?>
                <div class="interactive-section">
                    <h3>خيارات</h3>
                    <ul class="quiz-options">
                        <?php
                        $choices = json_decode($interactive_lesson['choices'], true);
                        if (is_array($choices)) {
                            foreach ($choices as $key => $choice) {
                                echo '<li data-option="' . $key . '">' . htmlspecialchars($choice) . '</li>';
                            }
                        }
                        ?>
                    </ul>
                    <div class="quiz-answer" id="quiz-answer"></div>
                    <div class="explanation" id="explanation"></div>
                </div>
            <?php endif; ?>

            <a href="student_dashboard.php" class="btn btn-secondary back-button">
                <i class="fas fa-arrow-left"></i>
                الرجوع إلى لوحة التحكم
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const quizOptions = document.querySelectorAll('.quiz-options li');
        const quizAnswer = document.getElementById('quiz-answer');
        const explanation = document.getElementById('explanation');
        const correctAnswer = "<?= htmlspecialchars($interactive_lesson['correct_answer']) ?>"; // Get correct answer from DB

        quizOptions.forEach(option => {
            option.addEventListener('click', function() {
                const selectedOption = this.dataset.option;
                if (selectedOption === correctAnswer) {
                    quizAnswer.className = 'quiz-answer correct-answer';
                    quizAnswer.textContent = 'إجابة صحيحة!';
                    explanation.style.display = 'block';
                } else {
                    quizAnswer.className = 'quiz-answer wrong-answer';
                    quizAnswer.textContent = 'إجابة خاطئة، حاول مرة أخرى.';
                    explanation.style.display = 'none';
                }
                quizAnswer.style.display = 'block';
            });
        });
    </script>
</body>
</html>
