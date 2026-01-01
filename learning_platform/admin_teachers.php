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
        if ($stmt === false) return [];
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (is_mysqli($conn)) {
        $res = $conn->query($sql);
        if ($res === false) return [];
        return $res->fetch_all(MYSQLI_ASSOC);
    } else {
        return [];
    }
}


function update_teacher_status($conn, $teacher_id, $status) {
    $teacher_id = (int)$teacher_id;
    if (is_pdo($conn)) {
        $sql = "UPDATE users SET status = :status WHERE id = :id AND role = 'teacher'";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([':status' => $status, ':id' => $teacher_id]);
    } elseif (is_mysqli($conn)) {
        $sql = "UPDATE users SET status = ? WHERE id = ? AND role = 'teacher'";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) return false;
        $stmt->bind_param('si', $status, $teacher_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    } else {
        return false;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['teacher_id'])) {
        $teacher_id = (int)$_POST['teacher_id'];

        if (isset($_POST['approve'])) {
            update_teacher_status($conn, $teacher_id, 'approved');
        } elseif (isset($_POST['reject'])) {
            update_teacher_status($conn, $teacher_id, 'rejected');
        }

        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}


$pending_teachers = fetch_all_assoc($conn, "SELECT id, name, email, profile_image, created_at FROM users WHERE role = 'teacher' AND status = 'pending' ORDER BY created_at DESC");
$approved_teachers = fetch_all_assoc($conn, "SELECT id, name, email, profile_image, created_at FROM users WHERE role = 'teacher' AND status = 'approved' ORDER BY name ASC");
$rejected_teachers = fetch_all_assoc($conn, "SELECT id, name, email, profile_image, created_at FROM users WHERE role = 'teacher' AND status = 'rejected' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المعلمين | لوحة التحكم</title>
    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; }
        .sidebar .nav-link { color: rgba(255, 255, 255, 0.8); margin-bottom: 5px; }
        .sidebar .nav-link:hover { color: white; }
        .sidebar .nav-link.active { background-color: #007bff; color: white; }
        .teacher-card { transition: transform 0.3s; }
        .teacher-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .profile-image { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 3px solid #007bff; }
        .badge-pending { background-color: #ffc107; color: #212529; }
        .badge-approved { background-color: #28a745; color: #fff; }
        .badge-rejected { background-color: #dc3545; color: #fff; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
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
                            <a class="nav-link active" href="admin_teachers.php">
                                <i class="fas fa-chalkboard-teacher me-2"></i> إدارة المعلمين
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_students.php">
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

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">إدارة المعلمين</h1>
                </div>

                <!-- Pending Teachers Section -->
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>
                            طلبات الانضمام المعلقة
                            <span class="badge bg-dark ms-2"><?php echo count($pending_teachers); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($pending_teachers) > 0): ?>
                            <div class="row">
                                <?php foreach ($pending_teachers as $teacher): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card teacher-card h-100">
                                            <div class="card-body text-center">
                                                <?php
                                                    $img = !empty($teacher['profile_image']) ? 'uploads/profiles/' . $teacher['profile_image'] : 'assets/img/default-profile.png';
                                                    $img = htmlspecialchars($img);
                                                ?>
                                                <img src="<?php echo $img; ?>" alt="صورة المعلم" class="profile-image mb-3">
                                                <h5 class="card-title"><?php echo htmlspecialchars($teacher['name']); ?></h5>
                                                <p class="card-text text-muted"><?php echo htmlspecialchars($teacher['email']); ?></p>
                                                <span class="badge badge-pending">قيد المراجعة</span>
                                                <p class="small text-muted mt-2">
                                                    تاريخ التسجيل: <?php echo htmlspecialchars(date('Y-m-d', strtotime($teacher['created_at']))); ?>
                                                </p>
                                                <div class="d-flex justify-content-between mt-3">
                                                    <form method="post">
                                                        <input type="hidden" name="teacher_id" value="<?php echo (int)$teacher['id']; ?>">
                                                        <button type="submit" name="approve" class="btn btn-success">
                                                            <i class="fas fa-check me-1"></i> قبول
                                                        </button>
                                                    </form>
                                                    <form method="post">
                                                        <input type="hidden" name="teacher_id" value="<?php echo (int)$teacher['id']; ?>">
                                                        <button type="submit" name="reject" class="btn btn-danger">
                                                            <i class="fas fa-times me-1"></i> رفض
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                لا توجد طلبات معلقة حالياً.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Approved Teachers Section -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            المعلمون المعتمدون
                            <span class="badge bg-light text-dark ms-2"><?php echo count($approved_teachers); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($approved_teachers) > 0): ?>
                            <div class="row">
                                <?php foreach ($approved_teachers as $teacher): ?>
                                    <div class="col-md-6 col-lg-3 mb-4">
                                        <div class="card teacher-card h-100">
                                            <div class="card-body text-center">
                                                <?php
                                                    $img = !empty($teacher['profile_image']) ? 'uploads/profiles/' . $teacher['profile_image'] : 'assets/img/default-profile.png';
                                                    $img = htmlspecialchars($img);
                                                ?>
                                                <img src="<?php echo $img; ?>" alt="صورة المعلم" class="profile-image mb-3">
                                                <h5 class="card-title"><?php echo htmlspecialchars($teacher['name']); ?></h5>
                                                <p class="card-text text-muted"><?php echo htmlspecialchars($teacher['email']); ?></p>
                                                <span class="badge badge-approved">معتمد</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                لا يوجد معلمون معتمدون حالياً.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Rejected Teachers Section -->
                <div class="card mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-ban me-2"></i>
                            الطلبات المرفوضة
                            <span class="badge bg-light text-dark ms-2"><?php echo count($rejected_teachers); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($rejected_teachers) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>الاسم</th>
                                            <th>البريد الإلكتروني</th>
                                            <th>تاريخ التسجيل</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rejected_teachers as $teacher): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                                                <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                                <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($teacher['created_at']))); ?></td>
                                                <td>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="teacher_id" value="<?php echo (int)$teacher['id']; ?>">
                                                        <button type="submit" name="approve" class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-check me-1"></i> قبول
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                لا توجد طلبات مرفوضة حالياً.
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
