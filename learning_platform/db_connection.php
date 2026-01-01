<?php

if (!defined('DB_HOST')) {
    
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'learning_platform');
    define('DB_CHARSET', 'utf8mb4');

    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        
        date_default_timezone_set('Asia/Riyadh');

    } catch (PDOException $e) {
        
        error_log($e->getMessage());
        die("حدث خطأ تقني، يرجى المحاولة لاحقًا.");
    }
}


?>