<?php
$teacher_id = $_SESSION['user_id'];
$conn = new mysqli("localhost", "root", "", "learning_platform");
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// جلب كل الوحدات
$units = [];
$units_query = $conn->query("SELECT id, title FROM units ORDER BY order_num ASC");
while ($unit = $units_query->fetch_assoc()) {
    $units[$unit['id']] = $unit['title'];
}

// تعديل الدرس
if (isset($_GET['edit'])) {
    $lesson_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT title, content, unit_id FROM lessons WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $lesson_id, $teacher_id);
    $stmt->execute();
    $stmt->bind_result($title, $content, $unit_id);
    $stmt->fetch();
    $stmt->close();
?>
<div class="card p-4 mb-4">
    <h5 class="mb-3">✏️ تعديل الدرس</h5>
    <form method="post">
        <input type="hidden" name="update_lesson_id" value="<?= $lesson_id ?>">
        <div class="mb-3">
            <label class="form-label">عنوان الدرس</label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($title) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">الوحدة</label>
            <select name="unit_id" class="form-select" required>
                <?php foreach ($units as $id => $title_unit): ?>
                    <option value="<?= $id ?>" <?= $unit_id == $id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($title_unit) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">محتوى الدرس</label>
            <textarea name="content" class="form-control" rows="5" required><?= htmlspecialchars($content) ?></textarea>
        </div>
        <button type="submit" class="btn btn-success">💾 حفظ التعديلات</button>
        <a href="teacher_dashboard.php?section=lessons" class="btn btn-secondary">إلغاء</a>
    </form>
</div>
<?php
}

// تحديث الدرس
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_lesson_id'])) {
    $id = intval($_POST['update_lesson_id']);
    $new_title = trim($_POST['title']);
    $new_content = trim($_POST['content']);
    $unit_id = intval($_POST['unit_id']);

    $update = $conn->prepare("UPDATE lessons SET title = ?, content = ?, unit_id = ? WHERE id = ? AND teacher_id = ?");
    $update->bind_param("ssiii", $new_title, $new_content, $unit_id, $id, $teacher_id);
    $update->execute();
    $update->close();

    echo "<script>window.location.href = 'teacher_dashboard.php?section=lessons';</script>";
    exit;
}

// إضافة درس
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lesson'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $unit_id = intval($_POST['unit_id']);

    $stmt = $conn->prepare("INSERT INTO lessons (title, content, unit_id, teacher_id, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssii", $title, $content, $unit_id, $teacher_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>window.location.href = 'teacher_dashboard.php?section=lessons';</script>";
    exit;
}

// حذف الدرس
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $conn->query("DELETE FROM lessons WHERE id = $delete_id AND teacher_id = $teacher_id");
    echo "<script>window.location.href = 'teacher_dashboard.php?section=lessons';</script>";
    exit;
}

// جلب الدروس مع الوحدات
$lessons_result = $conn->query("SELECT lessons.*, units.title AS unit_title FROM lessons 
    LEFT JOIN units ON lessons.unit_id = units.id 
    WHERE lessons.teacher_id = $teacher_id 
    ORDER BY units.order_num ASC, lessons.created_at DESC");
$lessons_by_unit = [];
while ($row = $lessons_result->fetch_assoc()) {
    $lessons_by_unit[$row['unit_title']][] = $row;
}
?>

<!-- زر إضافة -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5>📚 إدارة الدروس</h5>
    <a href="?section=lessons&action=add" class="btn btn-primary">➕ إضافة درس</a>
</div>

<!-- نموذج الإضافة -->
<?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
<div class="card p-4 mb-4">
    <h5 class="mb-3">➕ إضافة درس جديد</h5>
    <form method="post">
        <input type="hidden" name="add_lesson" value="1">
        <div class="mb-3">
            <label class="form-label">عنوان الدرس</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">الوحدة المرتبطة</label>
            <select name="unit_id" class="form-select" required>
                <option value="">-- اختر وحدة --</option>
                <?php foreach ($units as $id => $title_unit): ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($title_unit) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">محتوى الدرس</label>
            <textarea name="content" class="form-control" rows="6" required></textarea>
        </div>
        <button type="submit" class="btn btn-success">💾 حفظ الدرس</button>
        <a href="teacher_dashboard.php?section=lessons" class="btn btn-secondary">إلغاء</a>
    </form>
</div>
<?php endif; ?>

<!-- عرض الدروس حسب الوحدات -->
<?php foreach ($lessons_by_unit as $unit_title => $lessons): ?>
    <div class="card p-3 mb-4">
        <h5 class="mb-3">📦 وحدة: <?= htmlspecialchars($unit_title) ?></h5>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>العنوان</th>
                    <th>المحتوى</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lessons as $lesson): ?>
                    <tr>
                        <td><?= htmlspecialchars($lesson['title']) ?></td>
                        <td><?= mb_strimwidth(strip_tags($lesson['content']), 0, 60, '...') ?></td>
                        <td>
                            <a href="?section=lessons&edit=<?= $lesson['id'] ?>" class="btn btn-sm btn-warning">تعديل</a>
                            <a href="?section=lessons&delete=<?= $lesson['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>
