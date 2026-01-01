<?php
// التأكد من بدء الجلسة فقط إذا لم تكن قد بدأت
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$teacher_id = $_SESSION['user_id'];
$conn = new mysqli("localhost", "root", "", "learning_platform");

if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// جلب جميع الوحدات بدون تصفية على أساس teacher_id
$units_result = $conn->query("SELECT id, title FROM units");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quiz_id'])) {
        // هذا جزء التحديث
        $quiz_id = intval($_POST['update_quiz_id']);
        $question = trim($_POST['question']);
        $correct_answer = trim($_POST['correct_answer']);
        $options = trim($_POST['options']);
        $unit_id = intval($_POST['unit_id']);

        // تحديث الاختبار في قاعدة البيانات
        $update = $conn->prepare("UPDATE quizzes SET question = ?, correct_answer = ?, options = ?, unit_id = ? WHERE id = ? AND teacher_id = ?");
        $update->bind_param("sssiii", $question, $correct_answer, $options, $unit_id, $quiz_id, $teacher_id);
        $update->execute();
        $update->close();

        echo "<script>alert('تم تعديل الاختبار بنجاح!'); window.location.href = 'teacher_dashboard.php?section=quizzes';</script>";
        exit;
    } else {
        // هذا جزء الإضافة
        $question = trim($_POST['question']);
        $correct_answer = trim($_POST['correct_answer']);
        $options = trim($_POST['options']);
        $unit_id = intval($_POST['unit_id']);

        // إضافة الاختبار إلى قاعدة البيانات
        $stmt = $conn->prepare("INSERT INTO quizzes (question, correct_answer, options, unit_id, teacher_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $question, $correct_answer, $options, $unit_id, $teacher_id);
        $stmt->execute();
        $stmt->close();

        echo "<script>alert('تم إضافة الاختبار بنجاح!'); window.location.href = 'teacher_dashboard.php?section=quizzes';</script>";
        exit;
    }
}

// إذا كان هناك تعديل للاختبار
if (isset($_GET['edit'])) {
    $quiz_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT question, correct_answer, options, unit_id FROM quizzes WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $quiz_id, $teacher_id);
    $stmt->execute();
    $stmt->bind_result($question, $correct_answer, $options, $unit_id);
    $stmt->fetch();
    $stmt->close();
}

// حذف اختبار
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $conn->query("DELETE FROM quizzes WHERE id = $delete_id AND teacher_id = $teacher_id");
    echo "<script>alert('تم حذف الاختبار بنجاح!'); window.location.href = 'teacher_dashboard.php?section=quizzes';</script>";
    exit;
}

// عرض الاختبارات
$result = $conn->query("SELECT quizzes.*, units.title AS unit_title FROM quizzes LEFT JOIN units ON quizzes.unit_id = units.id WHERE quizzes.teacher_id = $teacher_id ORDER BY quizzes.created_at DESC");
?>

<!-- نموذج إضافة/تعديل اختبار -->
<div class="card p-4 mb-4">
    <h5 class="mb-3"><?= isset($_GET['edit']) ? '✏️ تعديل اختبار' : '➕ إضافة اختبار جديد' ?></h5>
    <form method="post">
        <?php if (isset($_GET['edit'])): ?>
            <input type="hidden" name="update_quiz_id" value="<?= $_GET['edit'] ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label class="form-label">السؤال</label>
            <input type="text" name="question" class="form-control" value="<?= isset($question) ? htmlspecialchars($question) : '' ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">الإجابة الصحيحة</label>
            <input type="text" name="correct_answer" class="form-control" value="<?= isset($correct_answer) ? htmlspecialchars($correct_answer) : '' ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">الخيارات (بصيغة JSON)</label>
            <textarea name="options" class="form-control" rows="5" placeholder='مثال: ["الخيار 1", "الخيار 2", "الخيار 3", "الخيار 4"]' required><?= isset($options) ? htmlspecialchars($options) : '' ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">الوحدة</label>
            <select name="unit_id" class="form-select" required>
                <?php
                // إعادة تعيين مؤشر النتائج لاستخدامها مرة أخرى
                $units_result->data_seek(0);
                if ($units_result->num_rows > 0) {
                    while ($unit = $units_result->fetch_assoc()):
                ?>
                    <option value="<?= $unit['id'] ?>" <?= (isset($unit_id) && $unit_id == $unit['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($unit['title']) ?>
                    </option>
                <?php
                    endwhile;
                } else {
                    echo "<option disabled>لا توجد وحدات لعرضها</option>";
                }
                ?>
            </select>
        </div>
        <button type="submit" class="btn btn-success">💾 <?= isset($_GET['edit']) ? 'تحديث الاختبار' : 'حفظ الاختبار' ?></button>
        <a href="teacher_dashboard.php?section=quizzes" class="btn btn-secondary">إلغاء</a>
    </form>
</div>

<!-- عرض الاختبارات -->
<div class="card p-4">
    <h5 class="mb-3">📚 قائمة الاختبارات</h5>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>السؤال</th>
                <th>الإجابة الصحيحة</th>
                <th>الوحدة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['question']) ?></td>
                <td><?= htmlspecialchars($row['correct_answer']) ?></td>
                <td><?= htmlspecialchars($row['unit_title']) ?></td>
                <td>
                    <a href="teacher_dashboard.php?section=quizzes&edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning">تعديل</a>
                    <a href="teacher_dashboard.php?section=quizzes&delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>