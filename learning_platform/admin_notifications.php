<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'db_connection.php';

$notifications_query = "SELECT an.id, an.title, an.message, an.related_id, an.created_at, 
                               u.name as teacher_name, u.email as teacher_email, u.status
                       FROM admin_notifications an
                       LEFT JOIN users u ON an.related_id = u.id
                       WHERE an.notification_type = 'teacher_request' 
                       ORDER BY an.created_at DESC";
$notifications_result = $conn->query($notifications_query);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلبات الأساتذة الجديدة | لوحة التحكم</title>
    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .notification-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 5px solid;
        }
        .notification-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .notification-card.pending {
            border-left-color: #ffc107;
            background: linear-gradient(135deg, #fff3cd, #ffffff);
        }
        .notification-card.approved {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #d4edda, #ffffff);
        }
        .btn-back {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
        .teacher-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-left: 1rem;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    
    <div class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="mb-0"><i class="fas fa-bell me-2"></i> طلبات الأساتذة الجديدة</h3>
                </div>
                <div class="col-md-6 text-left">
                    <a href="admin_dashboard.php" class="btn btn-back">
                        <i class="fas fa-arrow-right me-2"></i> العودة للوحة التحكم
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="main-container">
            
            <?php
            
            $notifications = $notifications_result->fetchAll(PDO::FETCH_ASSOC);
            $total_requests = count($notifications);
            $pending_count = 0;
            $approved_count = 0;
            
            
            foreach ($notifications as $notification) {
                if ($notification['status'] == 'approved') {
                    $approved_count++;
                } else {
                    $pending_count++;
                }
            }
            ?>
            
            <div class="stats-card">
                <div class="row text-center">
                    <div class="col-md-4">
                        <h2 class="display-4 fw-bold"><?php echo $total_requests; ?></h2>
                        <p class="mb-0">إجمالي الطلبات</p>
                    </div>
                    <div class="col-md-4">
                        <h2 class="display-4 fw-bold"><?php echo $pending_count; ?></h2>
                        <p class="mb-0">طلبات قيد الانتظار</p>
                    </div>
                    <div class="col-md-4">
                        <h2 class="display-4 fw-bold"><?php echo $approved_count; ?></h2>
                        <p class="mb-0">طلبات مقبولة</p>
                    </div>
                </div>
            </div>

            
            <div class="row">
                <div class="col-md-12">
                    <?php if ($total_requests > 0): ?>
                        <?php foreach ($notifications as $notification): 
                            $status_class = ($notification['status'] ?? 'pending') == 'approved' ? 'approved' : 'pending';
                            $status_badge_class = ($notification['status'] ?? 'pending') == 'approved' ? 'bg-success' : 'bg-warning';
                            $status_text = ($notification['status'] ?? 'pending') == 'approved' ? 'تم قبوله' : 'قيد الانتظار';
                            $status_icon = ($notification['status'] ?? 'pending') == 'approved' ? 'fa-check-circle' : 'fa-clock';
                            
                            
                            $title = $notification['title'] ?? '';
                            $message = $notification['message'] ?? '';
                            $teacher_name = $notification['teacher_name'] ?? 'غير معروف';
                            $teacher_email = $notification['teacher_email'] ?? 'لا يوجد بريد';
                            $created_at = $notification['created_at'] ?? date('Y-m-d H:i:s');
                        ?>
                            <div class="card notification-card <?php echo $status_class; ?>">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-1 text-center">
                                            <div class="teacher-avatar">
                                                <i class="fas fa-user-graduate"></i>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <h5 class="card-title mb-2">
                                                <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                                            </h5>
                                            <p class="card-text mb-2">
                                                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                                            </p>
                                            <div class="teacher-info">
                                                <span class="text-muted me-3">
                                                    <i class="fas fa-user me-1"></i>
                                                    <?php echo htmlspecialchars($teacher_name, ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                                <span class="text-muted me-3">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    <?php echo htmlspecialchars($teacher_email, ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                                <span class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo date('Y/m/d H:i', strtotime($created_at)); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <span class="badge <?php echo $status_badge_class; ?> status-badge">
                                                <i class="fas <?php echo $status_icon; ?> me-1"></i>
                                                <?php echo $status_text; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <h4>لا توجد طلبات جديدة</h4>
                            <p class="text-muted">لا توجد طلبات انضمام من أساتذة جدد حالياً.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>