<?php
// نتائج الطلاب - النتيجة النهائية للطالب من جدول student_final_results
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "learning_platform");
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// التحقق من وجود جدول student_final_results
$tableExists = $conn->query("SHOW TABLES LIKE 'student_final_results'");

if ($tableExists && $tableExists->num_rows > 0) {
    // جلب النتيجة النهائية لكل طالب
    $sql = "
        SELECT sfr.student_id, u.name AS student_name, sfr.percentage, sfr.updated_at
        FROM student_final_results sfr
        INNER JOIN users u ON u.id = sfr.student_id
        ORDER BY sfr.updated_at DESC
    ";
    $results = $conn->query($sql);
} else {
    // في حال لم يتم إنشاء الجدول بعد
    $results = false;
}

?>

<div class="card p-4">
    <h5 class="mb-3">📈 النتائج النهائية للطلاب</h5>

    <?php if ($results && $results->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>الطالب</th>
                        <th class="text-center">النسبة %</th>
                        <th class="text-center">آخر تحديث</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $results->fetch_assoc()): 
                    $percentage = isset($row['percentage']) ? round($row['percentage']) : 0;
                ?> 
                    <tr>
                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                        <td class="text-center">
                            <strong><?= $percentage ?>%</strong>
                        </td>
                        <td class="text-center"><?= isset($row['updated_at']) ? date('Y-m-d H:i', strtotime($row['updated_at'])) : '-' ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info mb-0">
            لا توجد نتائج طلاب حتى الآن أو أن جدول النتائج النهائية غير موجود.
        </div>
    <?php endif; ?>
</div>


