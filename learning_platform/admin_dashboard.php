<?php
session_start();
require_once 'db_connection.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}


$hasStatusColumnStmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'users' 
      AND COLUMN_NAME = 'status'
");
$hasStatusColumnStmt->execute();
$hasStatusColumn = $hasStatusColumnStmt->fetchColumn() > 0;


$qrHasStudentIdStmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'quiz_results' 
      AND COLUMN_NAME = 'student_id'
");
$qrHasStudentIdStmt->execute();
$qrHasStudentId = $qrHasStudentIdStmt->fetchColumn() > 0;

$qrHasUserIdStmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'quiz_results' 
      AND COLUMN_NAME = 'user_id'
");
$qrHasUserIdStmt->execute();
$qrHasUserId = $qrHasUserIdStmt->fetchColumn() > 0;


$timestampCandidates = ['created_at', 'submitted_at', 'attempted_at', 'date'];
$qrTimestampColumn = null;
foreach ($timestampCandidates as $col) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'quiz_results' 
          AND COLUMN_NAME = :col
    ");
    $stmt->execute([':col' => $col]);
    if ($stmt->fetchColumn() > 0) {
        $qrTimestampColumn = $col;
        break;
    }
}


$pending_teachers = [];
if ($hasStatusColumn) {
    $pending_teachers_stmt = $conn->query("SELECT id, name, email, created_at FROM users WHERE role = 'teacher' AND status = 'pending'");
    $pending_teachers = $pending_teachers_stmt ? $pending_teachers_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}
$pending_teachers_count = count($pending_teachers);


$recent_results = [];
try {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'student_final_results'");
    $hasFinalTable = $tableCheck && $tableCheck->rowCount() > 0;
    
    if ($hasFinalTable) {
        
        $recent_results_query = "
            SELECT 
                sfr.student_id,
                u.name AS student_name,
                sfr.percentage AS final_percentage,
                sfr.updated_at AS created_at
            FROM student_final_results sfr
            JOIN users u ON u.id = sfr.student_id
            WHERE u.role = 'student'
            ORDER BY sfr.updated_at DESC
            LIMIT 10
        ";
        $recent_results_stmt = $conn->query($recent_results_query);
        $recent_results = $recent_results_stmt ? $recent_results_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        
        
        if (empty($recent_results)) {
            $recent_results_query = "
                SELECT 
                    sfr.student_id,
                    u.name AS student_name,
                    sfr.percentage AS final_percentage,
                    sfr.updated_at AS created_at
                FROM student_final_results sfr
                JOIN users u ON u.id = sfr.student_id
                ORDER BY sfr.updated_at DESC
                LIMIT 10
            ";
            $recent_results_stmt = $conn->query($recent_results_query);
            $recent_results = $recent_results_stmt ? $recent_results_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }
    }
} catch (Exception $e) {
    error_log("Error fetching recent results: " . $e->getMessage());
    $recent_results = [];
}


$students_count = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$teachers_count_query = "SELECT COUNT(*) FROM users WHERE role = 'teacher'" . ($hasStatusColumn ? " AND status = 'approved'" : "");
$teachers_count = (int)$conn->query($teachers_count_query)->fetchColumn();
$quizzes_count = (int)$conn->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['teacher_id'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    $action = $_POST['action'];

    if ($hasStatusColumn) {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE id = ? AND role = 'teacher'");
            $stmt->execute([$teacher_id]);
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE users SET status = 'rejected' WHERE id = ? AND role = 'teacher'");
            $stmt->execute([$teacher_id]);
        }
    }

    header("Location: admin_dashboard.php");
    exit();
}


$notifications_stmt = $conn->query("SELECT * FROM admin_notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT 5");
$notifications = $notifications_stmt ? $notifications_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
$notifications_count = count($notifications);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المدير - إنجليش ماستر</title>
    <!-- Bootstrap RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f7fa;
        }
        
        .admin-sidebar {
            background-color: var(--dark-color);
            color: white;
            min-height: 100vh;
            padding-top: 2rem;
        }
        
        .admin-sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.8rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0.25rem;
        }
        
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .admin-sidebar .nav-link i {
            margin-left: 0.5rem;
            width: 20px;
            text-align: center;
        }
        
        .dashboard-card {
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
            transition: transform 0.3s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-card {
            border-right: 4px solid var(--primary-color);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.7rem;
        }
        
        .teacher-request-card {
            transition: all 0.3s;
        }
        
        .teacher-request-card:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            
            <div class="col-md-3 col-lg-2 admin-sidebar p-0">
                <div class="d-flex flex-column p-3">
                    <a href="admin_dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                        <i class="fas fa-language fs-4 me-2"></i>
                        <span class="fs-4">إنجليش ماستر</span>
                    </a>
                    <hr>
                    <ul class="nav nav-pills flex_column mb-auto">
                        <li class="nav-item">
                            <a href="admin_dashboard.php" class="nav-link active">
                                <i class="fas fa-tachometer-alt"></i>
                                لوحة التحكم
                            </a>
                        </li>
                        <li>
                            <a href="admin_teachers.php" class="nav-link">
                                <i class="fas fa-chalkboard-teacher"></i>
                                إدارة المعلمين
                                <?php if ($pending_teachers_count > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?php echo $pending_teachers_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li>
                            <a href="admin_students.php" class="nav-link">
                                <i class="fas fa-user_graduate"></i>
                                إدارة الطلاب
                            </a>
                        </li>
                        <li>
                            <a href="admin_reports.php" class="nav-link">
                                <i class="fas fa-chart-bar"></i>
                                تقارير النتائج
                            </a>
                        </li>
                        <li>
                            <a href="admin_notifications.php" class="nav-link">
                                <i class="fas fa-bell"></i>
                                الإشعارات
                                <?php if ($notifications_count > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?php echo $notifications_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                    <hr>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="images/6300787.png" alt="Admin" width="32" height="32" class="rounded-circle me-2">
                            <strong><?php echo htmlspecialchars($_SESSION['name'] ?? 'المدير'); ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end text-small shadow" aria-labelledby="dropdownUser1">
                            <li><a class="dropdown-item" href="admin_profile.php">الملف الشخصي</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">تسجيل الخروج</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
          
            <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">لوحة تحكم المدير</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="position-relative me-2">
                            <a href="admin_notifications.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-bell"></i>
                                <?php if ($notifications_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                                    <?php echo $notifications_count; ?>
                                </span>
                                <?php endif; ?>
                            </a>
                        </div>
                        <a href="logout.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-sign-out-alt"></i>
                            تسجيل الخروج
                        </a>
                    </div>
                </div>
                
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card dashboard-card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-0">إجمالي الطلاب</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $students_count; ?></h2>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-user-graduate text_primary fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-0">إجمالي المعلمين</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $teachers_count; ?></h2>
                                    </div>
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-chalkboard-teacher text-success fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-0">إجمالي الاختبارات</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $quizzes_count; ?></h2>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-clipboard-list text-warning fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card dashboard-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">طلبات المعلمين المعلقة</h5>
                                <a href="admin_teachers.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                            </div>
                            <div class="card-body">
                                <?php if ($pending_teachers_count > 0): ?>
                                    <?php foreach ($pending_teachers as $teacher): ?>
                                    <div class="card mb-2 teacher-request-card">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($teacher['name']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($teacher['email']); ?></small>
                                                </div>
                                                <div class="action-buttons">
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="btn btn-success btn-sm" <?php echo !$hasStatusColumn ? 'disabled' : ''; ?>>
                                                            <i class="fas fa-check"></i> قبول
                                                        </button>
                                                    </form>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit" class="btn btn-danger btn-sm" <?php echo !$hasStatusColumn ? 'disabled' : ''; ?>>
                                                            <i class="fas fa-times"></i> رفض
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                                        <p class="mb-0">لا توجد طلبات معلقة حالياً</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="col-md-6">
                        <div class="card dashboard-card h-100">
                            <div class="card-header d-flex justify-content_between align-items-center">
                                <h5 class="mb-0">آخر نتائج الطلاب</h5>
                                <a href="admin_reports.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>الطالب</th>
                                                <th>الاختبار</th>
                                                <th>الدرجة</th>
                                                <th>التاريخ</th>
                                            </tr>
                                        </thead>
                                       <tbody>
<?php if (!empty($recent_results)): ?>
    <?php foreach ($recent_results as $result): ?>
        <?php
            $percentage = (float)$result['final_percentage'];
            if ($percentage >= 85) {
                $status = 'ممتاز';
                $badge = 'success';
            } elseif ($percentage >= 60) {
                $status = 'جيد';
                $badge = 'warning';
            } else {
                $status = 'ضعيف';
                $badge = 'danger';
            }
        ?>
        <tr>
            <td><?= htmlspecialchars($result['student_name']) ?></td>
            <td>النتيجة النهائية</td>
            <td>
                <span class="badge bg-<?= $badge ?>">
                    <?= $percentage ?>% - <?= $status ?>
                </span>
            </td>
            <td><?= date('Y-m-d', strtotime($result['created_at'])) ?></td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="4" class="text-center">لا توجد نتائج نهائية مسجلة بعد</td>
    </tr>
<?php endif; ?>
</tbody>

                                    </table>
                                    <?php if (!$qrTimestampColumn): ?>
                                        <div class="alert alert-warning mt-3" role="alert">
                                            لم يتم العثور على عمود تاريخ في جدول النتائج (created_at / submitted_at / attempted_at / date). يتم ترتيب النتائج حسب رقم المعرف.
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!$qrHasStudentId && !$qrHasUserId): ?>
                                        <div class="alert alert-warning mt-3" role="alert">
                                            لا توجد أعمدة ربط للمستخدم في جدول النتائج (student_id أو user_id)، لذلك لا يمكن عرض أسماء الطلاب.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        
        document.addEventListener('DOMContentLoaded', function() {
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
        });
    </script>
</body>
</html>