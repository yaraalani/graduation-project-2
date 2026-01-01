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

// جلب الطلاب فقط
$sql = "SELECT id, name, email, level FROM users WHERE role = 'student'";
$result = $conn->query($sql);
?>

<div class="card p-4">
    <h5 class="mb-4">👥 إدارة الطلاب</h5>

    <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-primary text-center">
                <tr>
                    <th>الاسم</th>
                    <th>البريد الإلكتروني</th>
                    <th>المستوى</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['level']) ?></td>
                        <td>
                            <a href="?section=edit_student&id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">تعديل</a>
                            <a href="teacher_sections/delete_student.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>لا يوجد طلاب مسجلين حالياً.</p>
    <?php endif; ?>
</div>
