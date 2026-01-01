<?php

$conn = new mysqli("localhost", "root", "", "learning_platform");

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

echo "بدء تحديث جدول quiz_results...\n\n";


$columns_to_add = [
    'created_at' => "ALTER TABLE `quiz_results` ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `total_questions`",
    'is_final' => "ALTER TABLE `quiz_results` ADD COLUMN `is_final` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 للنتيجة النهائية، 0 لنتيجة سؤال فردي' AFTER `total_questions`",
    'percentage' => "ALTER TABLE `quiz_results` ADD COLUMN `percentage` DECIMAL(5,2) DEFAULT NULL COMMENT 'نسبة الطالب من 0 إلى 100' AFTER `total_questions`"
];

foreach ($columns_to_add as $column_name => $sql) {
   
    $check = $conn->query("SHOW COLUMNS FROM quiz_results LIKE '$column_name'");
    
    if ($check->num_rows == 0) {
        
        if ($conn->query($sql)) {
            echo "✅ تم إضافة العمود: $column_name\n";
        } else {
            echo "❌ خطأ في إضافة العمود $column_name: " . $conn->error . "\n";
        }
    } else {
        echo "ℹ️  العمود $column_name موجود بالفعل\n";
    }
}


echo "\nبدء تحديث النتائج القديمة...\n";


$update_created = "UPDATE `quiz_results` SET `created_at` = `completed_at` WHERE (`created_at` IS NULL OR `created_at` = '0000-00-00 00:00:00') AND `completed_at` IS NOT NULL";
if ($conn->query($update_created)) {
    echo "✅ تم تحديث created_at من completed_at\n";
} else {
    echo "⚠️  تحذير في تحديث created_at: " . $conn->error . "\n";
}


$update_is_final = "UPDATE `quiz_results` SET `is_final` = 1 WHERE `quiz_id` = 0";
if ($conn->query($update_is_final)) {
    echo "✅ تم تحديث is_final للنتائج النهائية\n";
} else {
    echo "⚠️  تحذير في تحديث is_final: " . $conn->error . "\n";
}


$update_percentage = "UPDATE `quiz_results` SET `percentage` = ROUND((`score` / `total_questions`) * 100, 2) WHERE `percentage` IS NULL AND `total_questions` > 0";
if ($conn->query($update_percentage)) {
    $affected = $conn->affected_rows;
    echo "✅ تم حساب النسبة لـ $affected سجل\n";
} else {
    echo "⚠️  تحذير في حساب النسبة: " . $conn->error . "\n";
}

echo "\n✅ اكتمل التحديث بنجاح!\n";

$indexName = 'uq_final';
echo "\nالتحقق من وجود الفهرس الفريد ($indexName)...\n";
$idxCheck = $conn->query("
    SELECT 1 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'quiz_results' 
      AND INDEX_NAME = '$indexName'
");
if ($idxCheck && $idxCheck->num_rows === 0) {
    
    $createIdx = "ALTER TABLE `quiz_results` ADD UNIQUE KEY `$indexName` (`student_id`, `quiz_id`, `is_final`)";
    if ($conn->query($createIdx)) {
        echo "✅ تم إنشاء الفهرس الفريد ($indexName)\n";
    } else {
        echo "⚠️  تعذر إنشاء الفهرس ($indexName): " . $conn->error . "\n";
    }
} else {
    echo "ℹ️  الفهرس ($indexName) موجود مسبقاً\n";
}

$conn->close();
?>

