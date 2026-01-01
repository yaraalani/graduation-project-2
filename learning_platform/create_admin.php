<?php

require_once 'db_connection.php';

echo "<h2>معلومات قاعدة البيانات</h2>";
echo "<p>اسم قاعدة البيانات: " . DB_NAME . "</p>";
echo "<p>المستخدم: " . DB_USER . "</p>";
echo "<p>المضيف: " . DB_HOST . "</p>";

try {
    
    echo "<h2>جداول قاعدة البيانات</h2>";
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>" . $table . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>لا توجد جداول في قاعدة البيانات.</p>";
    }
    
    
    $userTableExists = in_array('users', $tables);
    
    if ($userTableExists) {
        
        echo "<h2>هيكل جدول users</h2>";
        $columns = $conn->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1'>";
        echo "<tr><th>الحقل</th><th>النوع</th><th>Null</th><th>المفتاح</th><th>الافتراضي</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        
        echo "<h2>المستخدمون الموجودون</h2>";
        $users = $conn->query("SELECT id, name, email, role FROM users")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($users) > 0) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>الاسم</th><th>البريد الإلكتروني</th><th>الدور</th></tr>";
            
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td>" . $user['name'] . "</td>";
                echo "<td>" . $user['email'] . "</td>";
                echo "<td>" . $user['role'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>لا يوجد مستخدمون في قاعدة البيانات.</p>";
        }
    } else {
        echo "<p>جدول users غير موجود!</p>";
    }
    
    
    if ($userTableExists) {
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute(['admin@englishmaster.com']);
        $admin = $checkStmt->fetch();

        echo "<h2>إنشاء حساب المدير</h2>";
        
        if (!$admin) {
            
            $hashedPassword = password_hash('password', PASSWORD_DEFAULT);

        
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute(['المدير', 'admin@englishmaster.com', $hashedPassword, 'admin']);

            if ($result) {
                echo "<p style='color: green;'>تم إنشاء حساب المدير بنجاح!</p>";
                echo "<p>البريد الإلكتروني: admin@englishmaster.com</p>";
                echo "<p>كلمة المرور: password</p>";
            } else {
                echo "<p style='color: red;'>حدث خطأ أثناء إنشاء حساب المدير.</p>";
            }
        } else {
            echo "<p>حساب المدير موجود بالفعل (ID: " . $admin['id'] . ")</p>";
            echo "<p>البريد الإلكتروني: admin@englishmaster.com</p>";
            echo "<p>كلمة المرور: password</p>";
        }
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>خطأ في قاعدة البيانات: " . $e->getMessage() . "</p>";
}
?>