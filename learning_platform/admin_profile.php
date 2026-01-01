<?php
session_start();
 
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}


require_once 'db_connection.php';


$admin_id = $_SESSION['user_id'];
$admin_query = "SELECT name, email, created_at FROM users WHERE id = :id AND role = 'admin'";
$stmt = $conn->prepare($admin_query);
$stmt->bindParam(':id', $admin_id, PDO::PARAM_INT);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];

    $update_query = "UPDATE users SET name = :name, email = :email WHERE id = :id AND role = 'admin'";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $update_stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $update_stmt->bindParam(':id', $admin_id, PDO::PARAM_INT);

    if ($update_stmt->execute()) {
        $_SESSION['name'] = $name; 
        $success_message = "تم تحديث البيانات بنجاح.";
    } else {
        $error_message = "حدث خطأ أثناء تحديث البيانات.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ملف التعريف | لوحة التحكم</title>
    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
        }
        .form-container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="text-center mb-4">ملف التعريف</h2>
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php elseif (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">الاسم</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">البريد الإلكتروني</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="created_at" class="form-label">تاريخ الإنشاء</label>
                    <input type="text" class="form-control" id="created_at" value="<?php echo htmlspecialchars($admin['created_at']); ?>" disabled>
                </div>
                <button type="submit" class="btn btn-primary w-100">تحديث البيانات</button>
            </form>
        </div>
    </div>
</body>
</html>