<?php
session_start();
require_once 'db_connection.php'; // ملف يحتوي على اتصال قاعدة البيانات

if (!isset($_SESSION['user_id'])) {
    $_SESSION['profile_update_message'] = "يجب تسجيل الدخول أولاً";
    $_SESSION['profile_update_status'] = false;
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$conn = new mysqli("localhost", "root", "", "learning_platform");

if ($conn->connect_error) {
    $_SESSION['profile_update_message'] = "خطأ في الاتصال بقاعدة البيانات";
    $_SESSION['profile_update_status'] = false;
    header("Location: student_dashboard.php#account");
    exit;
}

// استقبال البيانات من النموذج
$name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
$email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// التحقق من البيانات الأساسية
if (empty($name) || empty($email)) {
    $_SESSION['profile_update_message'] = "الاسم والبريد الإلكتروني مطلوبان";
    $_SESSION['profile_update_status'] = false;
    header("Location: student_dashboard.php#account");
    exit;
}

// التحقق من صحة البريد الإلكتروني
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['profile_update_message'] = "البريد الإلكتروني غير صحيح";
    $_SESSION['profile_update_status'] = false;
    header("Location: student_dashboard.php#account");
    exit;
}

// التحقق من كلمة المرور إذا تم إدخالها
if (!empty($password)) {
    if ($password !== $confirm_password) {
        $_SESSION['profile_update_message'] = "كلمة المرور وتأكيدها غير متطابقين";
        $_SESSION['profile_update_status'] = false;
        header("Location: student_dashboard.php#account");
        exit;
    }
    
    if (strlen($password) < 8) {
        $_SESSION['profile_update_message'] = "كلمة المرور يجب أن تكون 8 أحرف على الأقل";
        $_SESSION['profile_update_status'] = false;
        header("Location: student_dashboard.php#account");
        exit;
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
}

// التحقق من البريد الإلكتروني غير مستخدم من قبل مستخدم آخر
$check_email = $conn->query("SELECT id FROM users WHERE email = '$email' AND id != $student_id");
if ($check_email->num_rows > 0) {
    $_SESSION['profile_update_message'] = "البريد الإلكتروني مستخدم من قبل حساب آخر";
    $_SESSION['profile_update_status'] = false;
    header("Location: student_dashboard.php#account");
    exit;
}

// بناء استعلام التحديث
$update_fields = "name = '$name', email = '$email'";
if (!empty($password)) {
    $update_fields .= ", password = '$hashed_password'";
}

// معالجة رفع الصورة الشخصية
// استبدل هذا الجزء من الكود
if (!empty($_FILES['profile_picture']['name'])) {
    $allowed_types = ['image/jpeg', 'image/png'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if (in_array($_FILES['profile_picture']['type'], $allowed_types) && 
        $_FILES['profile_picture']['size'] <= $max_size) {
        
        $upload_dir = 'uploads/profiles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $target_file = $upload_dir . $student_id . '.jpg';
        
        // التحقق من وجود دالة تحويل الصور
        if ($_FILES['profile_picture']['type'] == 'image/png' && function_exists('imagecreatefrompng')) {
            $image = imagecreatefrompng($_FILES['profile_picture']['tmp_name']);
            imagejpeg($image, $target_file, 90);
            imagedestroy($image);
        } else {
            // إذا كانت الصورة JPG أو لا يوجد دعم لتحويل PNG
            move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file);
        }
        
        chmod($target_file, 0644);
    } else {
        $_SESSION['profile_update_message'] = "صيغة الصورة غير مسموحة أو الحجم أكبر من 2MB";
        $_SESSION['profile_update_status'] = false;
        header("Location: student_dashboard.php#account");
        exit;
    }
}

// تنفيذ تحديث البيانات في قاعدة البيانات
$sql = "UPDATE users SET $update_fields WHERE id = $student_id";
if ($conn->query($sql)) {
    $_SESSION['name'] = $name;
    $_SESSION['profile_update_message'] = "تم تحديث بياناتك الشخصية بنجاح";
    $_SESSION['profile_update_status'] = true;
} else {
    $_SESSION['profile_update_message'] = "حدث خطأ أثناء تحديث البيانات: " . $conn->error;
    $_SESSION['profile_update_status'] = false;
}

$conn->close();
header("Location: student_dashboard.php#account");
exit;
?>