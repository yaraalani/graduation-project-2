<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'db_connection.php';

function is_pdo($conn) {
    return (class_exists('PDO') && $conn instanceof PDO);
}
function is_mysqli($conn) {
    return (class_exists('mysqli') && $conn instanceof mysqli);
}

function fetch_all_assoc($conn, $sql) {
    if (is_pdo($conn)) {
        $stmt = $conn->query($sql);
        if (!$stmt) return [];
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (is_mysqli($conn)) {
        $res = $conn->query($sql);
        if (!$res) return [];
        return $res->fetch_all(MYSQLI_ASSOC);
    } else {
        return [];
    }
}

$students = fetch_all_assoc($conn, "SELECT id, name, email, created_at FROM users WHERE role = 'student' ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الطلاب | لوحة التحكم</title>
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
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 5px;
        }
        .sidebar .nav-link:hover {
            color: white;
        }
        .sidebar .nav-link.active {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4>لوحة التحكم</h4>
                        <p class="text-muted">مرحباً، <?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?></p>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="admin_dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> الرئيسية
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_teachers.php">
                                <i class="fas fa-chalkboard-teacher me-2"></i> إدارة المعلمين
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="admin_students.php">
                                <i class="fas fa-user-graduate me-2"></i> إدارة الطلاب
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_reports.php">
                                <i class="fas fa-chart-bar me-2"></i> تقارير الأداء
                            </a>
                        </li>
                        <li class="nav-item mt-5">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> تسجيل الخروج
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">إدارة الطلاب</h1>
                </div>

                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user-graduate me-2"></i>
                            قائمة الطلاب
                            <span class="badge bg-light text-dark ms-2"><?php echo count($students); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($students) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>الاسم</th>
                                            <th>البريد الإلكتروني</th>
                                            <th>تاريخ التسجيل</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($student['created_at']))); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                لا يوجد طلاب مسجلون حالياً.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
