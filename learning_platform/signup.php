<?php
// الاتصال بقاعدة البيانات
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "learning_platform";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $profile_image = null;

    if ($password !== $confirm_password) {
        $error = "كلمتا المرور غير متطابقتين!";
    } else {
        // معالجة رفع الصورة الشخصية للمعلمين
        if ($role === 'teacher' && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/profiles/';
            
            // إنشاء المجلد إذا لم يكن موجوداً
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;
            
            // التحقق من نوع الملف
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array(strtolower($file_extension), $allowed_types)) {
                $error = "يُسمح فقط بملفات الصور (JPG, JPEG, PNG, GIF)";
            } else if ($_FILES['profile_image']['size'] > 5000000) { // 5MB
                $error = "حجم الملف كبير جداً. الحد الأقصى هو 5 ميجابايت";
            } else if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                $profile_image = $file_name;
            } else {
                $error = "حدث خطأ أثناء رفع الصورة";
            }
        } else if ($role === 'teacher' && (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK)) {
            $error = "يجب رفع صورة شخصية للمعلمين";
        }
        
        if (empty($error)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // تعيين حالة المعلم كمعلق للمراجعة
            $status = ($role === 'teacher') ? 'pending' : 'approved';
            
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status, profile_image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $email, $hashed_password, $role, $status, $profile_image);
            
            if ($stmt->execute()) {
                if ($role === 'teacher') {
                    $success = "تم إنشاء حسابك بنجاح. سيتم مراجعة طلبك من قبل الإدارة والرد عليك قريباً.";
                } else {
                    header("Location: login.php");
                    exit();
                }
            } else {
                $error = "حدث خطأ أثناء إنشاء الحساب: " . $stmt->error;
            }
            
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إنشاء حساب</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to left, #f0f8ff, #ffffff);
            font-family: 'Cairo', sans-serif;
        }
       
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-language"></i>
                إنجليش ماستر
            </a>
            <div>
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fas fa-home me-2"></i> العودة للرئيسية
                </a>
            </div>
        </div>
    </nav>
<div class="signup-form">
    <h3 class="text-center mb-4">إنشاء حساب جديد</h3>

    <?php if ($success): ?>
        <div class="alert alert-success text-center"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" novalidate>
        <div class="mb-3">
            <label class="form-label">الاسم الكامل</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">البريد الإلكتروني</label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">كلمة المرور</label>
            <input type="password" name="password" id="password" class="form-control" required>
            <div class="form-text mt-2" id="password-strength">
                <span class="badge bg-secondary">قوة كلمة المرور: غير محددة</span>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">تأكيد كلمة المرور</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            <div class="form-text mt-2" id="password-match">
                <span class="badge bg-secondary">لم يتم التحقق بعد</span>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">نوع الحساب</label>
            <select name="role" id="role-select" class="form-control" required>
                <option value="student">طالب</option>
                <option value="teacher">معلم</option>
            </select>
        </div>

        <div class="mb-3" id="profile-image-section" style="display: none;">
            <label class="form-label">الصورة الشخصية (مطلوبة للمعلمين)</label>
            <input type="file" name="profile_image" class="form-control" accept="image/*">
            <div class="form-text text-muted">يرجى رفع صورة شخصية واضحة. الحد الأقصى للحجم: 5 ميجابايت</div>
        </div>

        <button type="submit" class="btn btn-primary w-100">إنشاء الحساب</button>
        <p class="mt-3 text-center">لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a></p>
    </form>
</div>

<!-- Font Awesome -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const roleSelect = document.getElementById('role-select');
    const profileImageSection = document.getElementById('profile-image-section');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const strengthIndicator = document.getElementById('password-strength').querySelector('span');
    const matchIndicator = document.getElementById('password-match').querySelector('span');

    function toggleProfileSection() {
        profileImageSection.style.display = roleSelect.value === 'teacher' ? 'block' : 'none';
    }

    function evaluateStrength(value) {
        let score = 0;
        if (value.length >= 8) score++;
        if (/[A-Z]/.test(value)) score++;
        if (/[a-z]/.test(value)) score++;
        if (/\d/.test(value)) score++;
        if (/[\W_]/.test(value)) score++;
        return score;
    }

    function updateStrengthBadge() {
        const password = passwordInput.value;
        const score = evaluateStrength(password);
        const classes = ['bg-danger', 'bg-warning', 'bg-info', 'bg-primary', 'bg-success'];
        const messages = ['ضعيفة جداً', 'ضعيفة', 'متوسطة', 'جيدة', 'قوية'];

        if (!password) {
            strengthIndicator.className = 'badge bg-secondary';
            strengthIndicator.textContent = 'قوة كلمة المرور: غير محددة';
            return;
        }

        const index = Math.min(score, classes.length) - 1;
        strengthIndicator.className = 'badge ' + classes[Math.max(index, 0)];
        strengthIndicator.textContent = `قوة كلمة المرور: ${messages[Math.max(index, 0)]}`;
    }

    function updateMatchBadge() {
        const password = passwordInput.value;
        const confirm = confirmInput.value;

        if (!confirm) {
            matchIndicator.className = 'badge bg-secondary';
            matchIndicator.textContent = 'لم يتم التحقق بعد';
            return;
        }

        if (password === confirm) {
            matchIndicator.className = 'badge bg-success';
            matchIndicator.textContent = 'كلمتا المرور متطابقتان';
        } else {
            matchIndicator.className = 'badge bg-danger';
            matchIndicator.textContent = 'كلمتا المرور غير متطابقتين';
        }
    }

    roleSelect.addEventListener('change', toggleProfileSection);
    passwordInput.addEventListener('input', () => {
        updateStrengthBadge();
        updateMatchBadge();
    });
    confirmInput.addEventListener('input', updateMatchBadge);

    // تهيئة الحالة الأولى
    toggleProfileSection();
    updateStrengthBadge();
    updateMatchBadge();
</script>
</body>
</html>
