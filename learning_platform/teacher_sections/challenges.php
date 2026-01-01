<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$conn = new mysqli("localhost", "root", "", "learning_platform");



// معالجة إضافة/تعديل التحدي
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_challenge_id'])) {
        // عملية التحديث
        $challenge_id = intval($_POST['update_challenge_id']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $type = trim($_POST['type']);
        $goal = intval($_POST['goal']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        $stmt = $conn->prepare("UPDATE challenges SET title=?, description=?, type=?, goal=?, start_date=?, end_date=? WHERE id=? AND user_id=?");
        $stmt->bind_param("sssissii", $title, $description, $type, $goal, $start_date, $end_date, $challenge_id, $teacher_id);
        $stmt->execute();
        
        // رفع الملفات إذا وجدت
        if (!empty($_FILES['pdf_file']['name'])) {
            $uploadDir = "uploads/challenges/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = uniqid() . '_' . basename($_FILES['pdf_file']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $targetPath)) {
                $conn->query("DELETE FROM challenge_files WHERE challenge_id = $challenge_id");
                $conn->query("INSERT INTO challenge_files (challenge_id, file_name, file_path) VALUES ($challenge_id, '".$_FILES['pdf_file']['name']."', '$targetPath')");
            }
        }
        
        echo "<script>alert('تم تحديث التحدي بنجاح!'); window.location.href = 'teacher_dashboard.php?section=challenges';</script>";
        exit;
    } else {
        // عملية الإضافة
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $type = trim($_POST['type']);
        $goal = intval($_POST['goal']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        $stmt = $conn->prepare("INSERT INTO challenges (title, description, type, goal, progress, user_id, start_date, end_date) VALUES (?, ?, ?, ?, 0, ?, ?, ?)");
        // الأنواع الصحيحة: s(title), s(description), s(type), i(goal), i(user_id), s(start_date), s(end_date)
        $stmt->bind_param("sssiiss", $title, $description, $type, $goal, $teacher_id, $start_date, $end_date);
        $stmt->execute();
        $challenge_id = $stmt->insert_id;
        
        // رفع الملفات إذا وجدت
        if (!empty($_FILES['pdf_file']['name'])) {
            $uploadDir = "uploads/challenges/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = uniqid() . '_' . basename($_FILES['pdf_file']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $targetPath)) {
                $conn->query("INSERT INTO challenge_files (challenge_id, file_name, file_path) VALUES ($challenge_id, '".$_FILES['pdf_file']['name']."', '$targetPath')");
            }
        }
        
        echo "<script>alert('تم إضافة التحدي بنجاح!'); window.location.href = 'teacher_dashboard.php?section=challenges';</script>";
        exit;
    }
}
if (isset($_GET['delete'])) {
    $challenge_id = intval($_GET['delete']);

    // حذف الملفات المرتبطة بالتحدي
    $conn->query("DELETE FROM challenge_files WHERE challenge_id = $challenge_id");

    // حذف التحدي بعد حذف العلاقات التابعة له
    $stmt = $conn->prepare("DELETE FROM challenges WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $challenge_id, $teacher_id);
    $stmt->execute();

    echo "<script>alert('تم حذف التحدي بنجاح!'); window.location.href = 'teacher_dashboard.php?section=challenges';</script>";
    exit;
}



// جلب جميع التحديات
$challenges = $conn->query("SELECT * FROM challenges ORDER BY start_date DESC");
?>

<!-- نموذج إضافة/تعديل التحدي -->
<div class="card p-4 mb-4">
    <h5 class="mb-3"><?= isset($_GET['edit']) ? '✏️ تعديل التحدي' : '➕ إضافة تحدي جديد' ?></h5>
    <form method="post" enctype="multipart/form-data">
        <?php if (isset($_GET['edit'])): ?>
            <input type="hidden" name="update_challenge_id" value="<?= $_GET['edit'] ?>">
        <?php endif; ?>
        
        <div class="mb-3">
            <label class="form-label">عنوان التحدي</label>
            <input type="text" name="title" class="form-control" value="<?= isset($challenge['title']) ? htmlspecialchars($challenge['title']) : '' ?>" required>
        </div>
        
        <div class="mb-3">
            <label class="form-label">الوصف</label>
            <textarea name="description" class="form-control" rows="3" required><?= isset($challenge['description']) ? htmlspecialchars($challenge['description']) : '' ?></textarea>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">نوع التحدي</label>
                <select name="type" class="form-select" id="challenge-type" required>
                    <option value="اختبارات" <?= (isset($challenge['type']) && $challenge['type'] == 'اختبارات') ? 'selected' : '' ?>>اختبارات</option>
                    <option value="قراءة" <?= (isset($challenge['type']) && $challenge['type'] == 'قراءة') ? 'selected' : '' ?>>قراءة</option>
                    <option value="مشروع" <?= (isset($challenge['type']) && $challenge['type'] == 'مشروع') ? 'selected' : '' ?>>مشروع</option>
                </select>
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">الهدف</label>
                <input type="number" name="goal" class="form-control" value="<?= isset($challenge['goal']) ? $challenge['goal'] : '10' ?>" required>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">تاريخ البدء</label>
                <input type="date" name="start_date" class="form-control" value="<?= isset($challenge['start_date']) ? $challenge['start_date'] : date('Y-m-d') ?>" required>
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">تاريخ الانتهاء</label>
                <input type="date" name="end_date" class="form-control" value="<?= isset($challenge['end_date']) ? $challenge['end_date'] : date('Y-m-d', strtotime('+1 week')) ?>" required>
            </div>
        </div>
        
        <!-- قسم إضافة ملف PDF -->
        <div class="mb-3" id="pdf-section" style="<?= (isset($challenge['type']) && $challenge['type'] == 'قراءة') ? '' : 'display:none;' ?>">
            <label class="form-label">رفع ملف PDF للقراءة</label>
            <input type="file" name="pdf_file" class="form-control" accept=".pdf">
            <?php if (isset($_GET['edit'])): ?>
                <?php
                $file = $conn->query("SELECT * FROM challenge_files WHERE challenge_id = ".$_GET['edit'])->fetch_assoc();
                if ($file): ?>
                    <div class="mt-2">
                        <span class="badge bg-info">الملف الحالي: <?= htmlspecialchars($file['file_name']) ?></span>
                        <a href="delete_file.php?challenge_id=<?= $_GET['edit'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل تريد حذف الملف؟')">حذف الملف</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <button type="submit" class="btn btn-success">💾 حفظ التحدي</button>
        <a href="teacher_dashboard.php?section=challenges" class="btn btn-secondary">إلغاء</a>
    </form>
</div>

<!-- قائمة التحديات -->
<div class="card p-4">
    <h5 class="mb-3">🏆 قائمة التحديات</h5>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>العنوان</th>
                    <th>النوع</th>
                    <th>الوصف</th>
                    <th>الفترة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($challenge = $challenges->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($challenge['title']) ?></td>
                    <td>
                        <?= htmlspecialchars($challenge['type']) ?>
                        <?php if ($challenge['type'] == 'قراءة'): ?>
                            <?php $file = $conn->query("SELECT * FROM challenge_files WHERE challenge_id = ".$challenge['id'])->fetch_assoc(); ?>
                            <?php if ($file): ?>
                                <span class="badge bg-success">مع ملف</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><?= strlen($challenge['description']) > 50 ? substr($challenge['description'], 0, 50).'...' : $challenge['description'] ?></td>
                    <td>
                        <?= date('Y/m/d', strtotime($challenge['start_date'])) ?> 
                        إلى 
                        <?= date('Y/m/d', strtotime($challenge['end_date'])) ?>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="teacher_dashboard.php?section=challenges&edit=<?= $challenge['id'] ?>" class="btn btn-sm btn-warning">تعديل</a>
                            <a href="teacher_dashboard.php?section=challenges&delete=<?= $challenge['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا التحدي؟')">حذف</a>
                            <a href="view_challenge.php?id=<?= $challenge['id'] ?>" class="btn btn-sm btn-info">عرض</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// إظهار/إخفاء الأقسام حسب نوع التحدي
document.getElementById('challenge-type').addEventListener('change', function() {
    const type = this.value;
    const pdfSection = document.getElementById('pdf-section');
    if (pdfSection) {
        pdfSection.style.display = type === 'قراءة' ? 'block' : 'none';
    }
});

</script>