<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}


require_once 'db_connection.php';

try {
    
    $stats_query = "
        SELECT 
            (SELECT COUNT(*) FROM users WHERE role = 'student') AS total_students,
            (SELECT COUNT(*) FROM users WHERE role = 'teacher' AND status = 'approved') AS total_teachers,
            (SELECT COUNT(*) FROM student_final_results) AS total_quiz_attempts
    ";
    $stats = $conn->query($stats_query)->fetch(PDO::FETCH_ASSOC);

    
    $recent_results_query = "
        SELECT sfr.id, sfr.percentage, sfr.updated_at, u.name AS student_name
        FROM student_final_results sfr
        JOIN users u ON sfr.student_id = u.id 
        ORDER BY sfr.updated_at DESC 
        LIMIT 10
    ";
    $recent_results = $conn->query($recent_results_query)->fetchAll(PDO::FETCH_ASSOC);

    
    $best_finals_query = "
        SELECT u.name AS student_name, sfr.percentage
        FROM student_final_results sfr
        JOIN users u ON u.id = sfr.student_id
        ORDER BY sfr.percentage DESC, sfr.updated_at DESC
        LIMIT 10
    ";
    $best_finals = $conn->query($best_finals_query)->fetchAll(PDO::FETCH_ASSOC);

    
    $top_students_query = "
        SELECT u.id, u.name, AVG(sfr.percentage) AS avg_score, COUNT(sfr.id) AS finals_count
        FROM users u
        JOIN student_final_results sfr ON u.id = sfr.student_id
        WHERE u.role = 'student'
        GROUP BY u.id, u.name
        ORDER BY avg_score DESC
        LIMIT 5
    ";
    $top_students = $conn->query($top_students_query)->fetchAll(PDO::FETCH_ASSOC);

    
    $struggling_students_query = "
        SELECT u.id, u.name, COUNT(sfr.id) AS quiz_count, AVG(sfr.percentage) AS avg_score
        FROM users u
        JOIN student_final_results sfr ON u.id = sfr.student_id
        WHERE u.role = 'student'
        GROUP BY u.id, u.name
        HAVING avg_score < 60 
        ORDER BY avg_score ASC 
        LIMIT 5
    ";
    $struggling_students = $conn->query($struggling_students_query)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقارير الأداء | لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
        }
        .sidebar .nav-link.active {
            background-color: #007bff;
            color: white;
        }
        .stat-card {
            border-radius: 12px;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
        }
        .chart-container {
            height: 300px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        
        <div class="col-md-3 col-lg-2 sidebar p-3">
            <div class="text-center mb-4">
                <h4>لوحة التحكم</h4>
                <p class="text-muted">مرحباً، <?= htmlspecialchars($_SESSION['name']) ?></p>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="admin_dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> الرئيسية</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_teachers.php"><i class="fas fa-chalkboard-teacher me-2"></i> إدارة المعلمين</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_students.php"><i class="fas fa-user-graduate me-2"></i> إدارة الطلاب</a></li>
                <li class="nav-item"><a class="nav-link active" href="admin_reports.php"><i class="fas fa-chart-bar me-2"></i> تقارير الأداء</a></li>
                <li class="nav-item mt-4"><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> تسجيل الخروج</a></li>
            </ul>
        </div>

        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                <h1 class="h2">تقارير أداء الطلاب</h1>
                <div>
                    <button class="btn btn-outline-primary btn-sm me-2" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> طباعة
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" id="exportBtn">
                        <i class="fas fa-file-export me-1"></i> تصدير
                    </button>
                </div>
            </div>

            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stat-card bg-primary text-white">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6>إجمالي الطلاب</h6>
                                <h2><?= $stats['total_students'] ?></h2>
                            </div>
                            <i class="fas fa-user-graduate fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card bg-success text-white">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6>إجمالي المعلمين</h6>
                                <h2><?= $stats['total_teachers'] ?></h2>
                            </div>
                            <i class="fas fa-chalkboard-teacher fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card bg-info text-white">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6>عدد الاختبارات</h6>
                                <h2><?= $stats['total_quiz_attempts'] ?></h2>
                            </div>
                            <i class="fas fa-tasks fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">أفضل 10 نتائج نهائية</div>
                        <div class="card-body"><canvas id="bestFinalsChart"></canvas></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">أفضل الطلاب أداءً</div>
                        <div class="card-body"><canvas id="topStudentsChart"></canvas></div>
                    </div>
                </div>
            </div>

            
            <div class="card mb-4">
                <div class="card-header bg-light">أحدث النتائج النهائية</div>
                <div class="card-body table-responsive">
                    <?php if (count($recent_results) > 0): ?>
                        <table class="table table-hover">
                            <thead>
                                <tr><th>الطالب</th><th>النسبة النهائية</th><th>التاريخ</th><th>التقييم</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recent_results as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['student_name']) ?></td>
                                    <td><?= round($r['percentage'], 1) ?>%</td>
                                    <td><?= date('Y-m-d H:i', strtotime($r['updated_at'])) ?></td>
                                    <td>
                                        <?php
                                            $s = (float)$r['percentage'];
                                            if ($s >= 90) echo '<span class="badge bg-success">ممتاز</span>';
                                            elseif ($s >= 80) echo '<span class="badge bg-primary">جيد جداً</span>';
                                            elseif ($s >= 70) echo '<span class="badge bg-info">جيد</span>';
                                            elseif ($s >= 60) echo '<span class="badge bg-warning text-dark">مقبول</span>';
                                            else echo '<span class="badge bg-danger">ضعيف</span>';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">لا توجد نتائج حالياً.</div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="card mb-5">
                <div class="card-header bg-danger text-white">
                    <i class="fas fa-exclamation-triangle me-2"></i> الطلاب الذين يحتاجون إلى مساعدة
                </div>
                <div class="card-body">
                    <?php if (count($struggling_students) > 0): ?>
                        <table class="table table-hover">
                            <thead><tr><th>الطالب</th><th>عدد الاختبارات</th><th>متوسط الدرجات</th></tr></thead>
                            <tbody>
                                <?php foreach ($struggling_students as $s): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($s['name']) ?></td>
                                        <td><?= $s['quiz_count'] ?></td>
                                        <td><?= round($s['avg_score'], 1) ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-success">جميع الطلاب يؤدون بشكل جيد!</div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const bestFinalNames = <?= json_encode(array_column($best_finals, 'student_name')) ?>;
    const bestFinalPercents = <?= json_encode(array_map('floatval', array_column($best_finals, 'percentage'))) ?>;

    const topNames = <?= json_encode(array_column($top_students, 'name')) ?>;
    const topScores = <?= json_encode(array_map('floatval', array_column($top_students, 'avg_score'))) ?>;

    new Chart(document.getElementById('bestFinalsChart'), {
        type: 'bar',
        data: {
            labels: bestFinalNames,
            datasets: [{ label: 'النسبة النهائية (%)', data: bestFinalPercents, backgroundColor: '#007bff' }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true, max: 100 } } }
    });

    new Chart(document.getElementById('topStudentsChart'), {
        type: 'bar',
        data: {
            labels: topNames,
            datasets: [{ label: 'متوسط الدرجات (%)', data: topScores, backgroundColor: '#28a745' }]
        },
        options: { indexAxis: 'y', scales: { x: { beginAtZero: true, max: 100 } } }
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
