<?php
// التحقق من الصلاحيات
if ($_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$conn = new mysqli("localhost", "root", "", "learning_platform");

// معالجة العمليات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_language'])) {
        $name = trim($_POST['name']);
        $code = trim($_POST['code']);
        
        $stmt = $conn->prepare("INSERT INTO languages (name, code) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $code);
        $stmt->execute();
    }
    
    if (isset($_POST['update_language'])) {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $code = trim($_POST['code']);
        
        $stmt = $conn->prepare("UPDATE languages SET name = ?, code = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $code, $id);
        $stmt->execute();
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM languages WHERE id = $id");
}

// جلب اللغات
$languages = $conn->query("SELECT * FROM languages ORDER BY name ASC");
?>

<div class="card p-4">
    <h4 class="mb-4">🌐 إدارة اللغات</h4>

    <!-- نموذج الإضافة/التعديل -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="post">
                <?php if (isset($_GET['edit'])): 
                    $edit_id = intval($_GET['edit']);
                    $edit_lang = $conn->query("SELECT * FROM languages WHERE id = $edit_id")->fetch_assoc();
                ?>
                    <input type="hidden" name="id" value="<?= $edit_id ?>">
                <?php endif; ?>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label>اسم اللغة</label>
                        <input type="text" name="name" class="form-control" 
                               value="<?= $edit_lang['name'] ?? '' ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label>كود اللغة (مثال: ar)</label>
                        <input type="text" name="code" class="form-control" 
                               value="<?= $edit_lang['code'] ?? '' ?>" required>
                    </div>
                </div>
                
                <div class="mt-3 text-end">
                    <?php if (isset($_GET['edit'])): ?>
                        <button type="submit" name="update_language" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> تحديث
                        </button>
                        <a href="?section=languages" class="btn btn-secondary">إلغاء</a>
                    <?php else: ?>
                        <button type="submit" name="add_language" class="btn btn-primary">
                            <i class="bi bi-plus"></i> إضافة
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول اللغات -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>اسم اللغة</th>
                    <th>الكود</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($lang = $languages->fetch_assoc()): ?>
                <tr>
                    <td><?= $lang['id'] ?></td>
                    <td><?= htmlspecialchars($lang['name']) ?></td>
                    <td><?= strtoupper($lang['code']) ?></td>
                    <td>
                        <a href="?section=languages&edit=<?= $lang['id'] ?>" 
                           class="btn btn-sm btn-warning">
                           <i class="bi bi-pencil"></i>
                        </a>
                        <a href="?section=languages&delete=<?= $lang['id'] ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('هل أنت متأكد من الحذف؟')">
                           <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>