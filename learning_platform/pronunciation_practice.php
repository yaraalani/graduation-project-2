<?php
session_start();
$student_id = $_SESSION['user_id'] ?? null;

// التحقق من تسجيل الدخول
if (!$student_id) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "learning_platform");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// جلب بيانات الطالب
$user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$user_stmt->bind_param("i", $student_id);
$user_stmt->execute();
$user_stmt->bind_result($name, $email);
$user_stmt->fetch();
$user_stmt->close();

// التحقق من وجود جدول pronunciation_sentences
$check_table = $conn->query("SHOW TABLES LIKE 'pronunciation_sentences'");
if ($check_table->num_rows == 0) {
    // إنشاء الجدول إذا لم يكن موجوداً
    $conn->query("
        CREATE TABLE pronunciation_sentences (
            id INT AUTO_INCREMENT PRIMARY KEY,
            text TEXT NOT NULL,
            difficulty_level INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // إضافة بعض الجمل الافتراضية
    $default_sentences = [
        ['text' => 'Hello, how are you today?', 'difficulty_level' => 1],
        ['text' => 'I would like to learn English.', 'difficulty_level' => 1],
        ['text' => 'The weather is beautiful today.', 'difficulty_level' => 1],
        ['text' => 'Could you please tell me the way to the nearest library?', 'difficulty_level' => 2],
        ['text' => 'I am studying English to improve my communication skills.', 'difficulty_level' => 2],
        ['text' => 'The quick brown fox jumps over the lazy dog.', 'difficulty_level' => 3]
    ];
    
    foreach ($default_sentences as $sentence) {
        $stmt = $conn->prepare("INSERT INTO pronunciation_sentences (text, difficulty_level) VALUES (?, ?)");
        $stmt->bind_param("si", $sentence['text'], $sentence['difficulty_level']);
        $stmt->execute();
        $stmt->close();
    }
}

// جلب جمل التدريب على النطق
$sentences_query = $conn->query("
    SELECT * FROM pronunciation_sentences 
    ORDER BY difficulty_level ASC, id ASC
");

$sentences = [];
if ($sentences_query) {
    while ($row = $sentences_query->fetch_assoc()) {
        $sentences[] = $row;
    }
}

// تسجيل نتيجة تمرين النطق
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_result'])) {
    $sentence_id = $_POST['sentence_id'] ?? 0;
    $accuracy = $_POST['accuracy'] ?? 0;
    
    // التحقق من وجود جدول نتائج النطق
    $check_table = $conn->query("SHOW TABLES LIKE 'pronunciation_results'");
    if ($check_table->num_rows == 0) {
        // إنشاء الجدول إذا لم يكن موجوداً
        $conn->query("
            CREATE TABLE pronunciation_results (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                sentence_id INT NOT NULL,
                accuracy FLOAT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }
    
    // حفظ النتيجة
    $save_stmt = $conn->prepare("INSERT INTO pronunciation_results (student_id, sentence_id, accuracy) VALUES (?, ?, ?)");
    $save_stmt->bind_param("iid", $student_id, $sentence_id, $accuracy);
    $save_stmt->execute();
    $save_stmt->close();
    
    // إعادة توجيه لتجنب إعادة الإرسال
    header("Location: pronunciation_practice.php?saved=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تدريب النطق</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .pronunciation-card {
            border: 1px solid #4cc9f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #f8fcff;
            transition: 0.3s;
        }
        .pronunciation-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(76, 201, 240, 0.2);
        }
        .sentence-text {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #333;
        }
        .controls {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .result-feedback {
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }
        .result-feedback.correct {
            background-color: #d4edda;
            color: #155724;
        }
        .result-feedback.incorrect {
            background-color: #f8d7da;
            color: #721c24;
        }
        .difficulty-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-bottom: 10px;
        }
        .difficulty-1 {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .difficulty-2 {
            background-color: #fff3cd;
            color: #664d03;
        }
        .difficulty-3 {
            background-color: #f8d7da;
            color: #842029;
        }
        .transcript {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            min-height: 50px;
        }
        #recordButton {
            background-color: #4cc9f0;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
        }
        #recordButton.recording {
            background-color: #f94144;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h1 class="text-center mb-4">تدريب النطق</h1>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <a href="student_dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-right ml-1"></i> العودة إلى لوحة التحكم
                    </a>
                    <div>
                        <span class="fw-bold">مرحباً، <?php echo htmlspecialchars($name); ?></span>
                    </div>
                </div>
                
                <?php if (isset($_GET['saved']) && $_GET['saved'] == 1): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    تم حفظ نتيجة التمرين بنجاح!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    قم بالضغط على زر التسجيل ثم انطق الجملة المعروضة باللغة الإنجليزية. سيتم تقييم نطقك وإظهار النتيجة.
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <?php foreach ($sentences as $index => $sentence): ?>
                <div class="pronunciation-card" id="sentence-<?php echo $index; ?>">
                    <div class="difficulty-badge difficulty-<?php echo $sentence['difficulty_level'] ?? 1; ?>">
                        المستوى: <?php echo $sentence['difficulty_level'] ?? 1; ?>
                    </div>
                    <div class="sentence-text" id="sentence-text-<?php echo $index; ?>" data-sentence="<?php echo htmlspecialchars($sentence['text'] ?? $sentence['text']); ?>">
                        <?php echo htmlspecialchars($sentence['text'] ?? $sentence['text']); ?>
                    </div>
                    <div class="controls">
                        <button id="recordButton-<?php echo $index; ?>" class="btn" onclick="toggleRecording(<?php echo $index; ?>)">
                            <i class="fas fa-microphone"></i> ابدأ التسجيل
                        </button>
                        <button id="playButton-<?php echo $index; ?>" class="btn btn-outline-secondary" onclick="speakSentence(<?php echo $index; ?>)">
                            <i class="fas fa-volume-up"></i> استمع للجملة
                        </button>
                    </div>
                    <div class="transcript" id="transcript-<?php echo $index; ?>">
                        <em>ما تم التعرف عليه سيظهر هنا...</em>
                    </div>
                    <div class="result-feedback" id="feedback-<?php echo $index; ?>"></div>
                    
                    <form id="result-form-<?php echo $index; ?>" method="post" action="" style="display: none;">
                        <input type="hidden" name="save_result" value="1">
                        <input type="hidden" name="sentence_id" value="<?php echo $sentence['id'] ?? $index; ?>">
                        <input type="hidden" name="accuracy" id="accuracy-<?php echo $index; ?>" value="0">
                        <button type="submit" class="btn btn-success mt-3">حفظ النتيجة</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // متغيرات عامة
        let recognition = null;
        let currentSentenceIndex = -1;
        let isRecording = false;
        
        // التحقق من دعم المتصفح للتعرف على الصوت
        function checkBrowserSupport() {
            if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                alert('عذراً، متصفحك لا يدعم ميزة التعرف على الصوت. يرجى استخدام متصفح Chrome أو Edge.');
                return false;
            }
            return true;
        }
        
        // تهيئة التعرف على الصوت
        function setupSpeechRecognition(sentenceIndex) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();
            recognition.lang = 'en-US';
            recognition.continuous = false;
            recognition.interimResults = false;
            
            recognition.onresult = function(event) {
                const transcript = event.results[0][0].transcript;
                const confidence = event.results[0][0].confidence;
                
                document.getElementById(`transcript-${sentenceIndex}`).textContent = transcript;
                
                // مقارنة النص المنطوق بالجملة الأصلية
                const originalSentence = document.getElementById(`sentence-text-${sentenceIndex}`).dataset.sentence.toLowerCase().trim();
                const spokenText = transcript.toLowerCase().trim();
                
                // حساب دقة التطابق باستخدام مسافة ليفنشتاين
                const accuracy = calculateSimilarity(originalSentence, spokenText);
                const accuracyPercentage = Math.round(accuracy * 100);
                
                // عرض النتيجة
                const feedbackElement = document.getElementById(`feedback-${sentenceIndex}`);
                feedbackElement.style.display = 'block';
                
                if (accuracyPercentage >= 75) {
                    feedbackElement.className = 'result-feedback correct';
                    feedbackElement.innerHTML = `<i class="fas fa-check-circle"></i> أحسنت! نطقك صحيح بنسبة ${accuracyPercentage}%`;
                } else {
                    feedbackElement.className = 'result-feedback incorrect';
                    feedbackElement.innerHTML = `<i class="fas fa-times-circle"></i> حاول مرة أخرى. دقة النطق: ${accuracyPercentage}%`;
                }
                
                // تحديث قيمة الدقة في النموذج
                document.getElementById(`accuracy-${sentenceIndex}`).value = accuracy;
                document.getElementById(`result-form-${sentenceIndex}`).style.display = 'block';
            };
            
            recognition.onerror = function(event) {
                console.error('Speech recognition error', event.error);
                stopRecording(sentenceIndex);
                alert('حدث خطأ في التعرف على الصوت: ' + event.error);
            };
            
            recognition.onend = function() {
                stopRecording(sentenceIndex);
            };
        }
        
        // بدء أو إيقاف التسجيل
        function toggleRecording(sentenceIndex) {
            if (!checkBrowserSupport()) return;
            
            if (isRecording && currentSentenceIndex === sentenceIndex) {
                // إيقاف التسجيل الحالي
                recognition.stop();
            } else {
                // إيقاف أي تسجيل سابق
                if (isRecording) {
                    recognition.stop();
                }
                
                // بدء تسجيل جديد
                setupSpeechRecognition(sentenceIndex);
                currentSentenceIndex = sentenceIndex;
                isRecording = true;
                
                const button = document.getElementById(`recordButton-${sentenceIndex}`);
                button.innerHTML = '<i class="fas fa-stop"></i> إيقاف التسجيل';
                button.classList.add('recording');
                
                recognition.start();
            }
        }
        
        // إيقاف التسجيل
        function stopRecording(sentenceIndex) {
            isRecording = false;
            const button = document.getElementById(`recordButton-${sentenceIndex}`);
            button.innerHTML = '<i class="fas fa-microphone"></i> ابدأ التسجيل';
            button.classList.remove('recording');
        }
        
        // نطق الجملة باستخدام Web Speech API
        function speakSentence(sentenceIndex) {
            if (!('speechSynthesis' in window)) {
                alert('عذراً، متصفحك لا يدعم ميزة تحويل النص إلى كلام.');
                return;
            }
            
            const sentence = document.getElementById(`sentence-text-${sentenceIndex}`).dataset.sentence;
            const utterance = new SpeechSynthesisUtterance(sentence);
            utterance.lang = 'en-US';
            utterance.rate = 0.9; // سرعة النطق (أبطأ قليلاً من المعتاد)
            
            speechSynthesis.speak(utterance);
        }
        
        // حساب التشابه بين نصين باستخدام مسافة ليفنشتاين
        function calculateSimilarity(str1, str2) {
            const track = Array(str2.length + 1).fill(null).map(() => 
                Array(str1.length + 1).fill(null));
            
            for (let i = 0; i <= str1.length; i += 1) {
                track[0][i] = i;
            }
            
            for (let j = 0; j <= str2.length; j += 1) {
                track[j][0] = j;
            }
            
            for (let j = 1; j <= str2.length; j += 1) {
                for (let i = 1; i <= str1.length; i += 1) {
                    const indicator = str1[i - 1] === str2[j - 1] ? 0 : 1;
                    track[j][i] = Math.min(
                        track[j][i - 1] + 1, // حذف
                        track[j - 1][i] + 1, // إضافة
                        track[j - 1][i - 1] + indicator, // استبدال
                    );
                }
            }
            
            const distance = track[str2.length][str1.length];
            const maxLength = Math.max(str1.length, str2.length);
            
            // حساب نسبة التشابه (1 = تطابق تام، 0 = لا تطابق)
            return maxLength > 0 ? 1 - distance / maxLength : 1;
        }
    </script>
</body>
</html>