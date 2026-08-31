<?php
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            $this->initTables();
            return;
        } catch(PDOException $e) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                $this->conn = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $this->conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $this->conn->exec("USE " . DB_NAME);
                $this->initTables();
                return;
            } catch(PDOException $e2) {
                try {
                    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
                    $this->conn = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    $this->conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $this->conn->exec("USE " . DB_NAME);
                    $this->initTables();
                    return;
                } catch(PDOException $e3) {
                    die("❌ Database connection failed: " . $e3->getMessage());
                }
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new Database();
        return self::$instance;
    }
    
    public function getConnection() { return $this->conn; }
    
    private function initTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin','user') DEFAULT 'user',
            daily_limit INT DEFAULT 500,
            remaining_limit INT DEFAULT 0,
            telegram_bot_token VARCHAR(255) DEFAULT NULL,
            telegram_chat_id VARCHAR(255) DEFAULT NULL,
            telegram_connected BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS extractions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            batch_id INT DEFAULT NULL,
            keyword VARCHAR(255),
            keyword_used VARCHAR(255) DEFAULT NULL,
            target_count INT DEFAULT 100,
            emails TEXT,
            total INT DEFAULT 0,
            domain_stats TEXT,
            status VARCHAR(20) DEFAULT 'completed',
            processed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id), INDEX(batch_id), INDEX(created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS batch_jobs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            status ENUM('queued','processing','completed','failed') DEFAULT 'queued',
            total_keywords INT DEFAULT 0,
            processed_keywords INT DEFAULT 0,
            total_emails INT DEFAULT 0,
            started_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS saved_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            extraction_id INT,
            filename VARCHAR(255),
            filepath VARCHAR(255),
            email_count INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS licenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE NOT NULL,
            user_id INT DEFAULT NULL,
            limit_amount INT NOT NULL,
            used BOOLEAN DEFAULT FALSE,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            redeemed_at TIMESTAMP NULL,
            expiry_date TIMESTAMP NULL,
            INDEX(user_id), INDEX(code), INDEX(used)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(255),
            ip VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id), INDEX(created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(50) UNIQUE,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $this->conn->exec($sql);
        
        if (!adminExists()) createDefaultAdmin();
        
        $settings = [
            'daily_limit' => '500',
            'extraction_timeout' => '60',
            'captcha_enabled' => 'true',
            'license_prefix' => 'GH',
            'default_user_limit' => '500'
        ];
        foreach ($settings as $key => $value) {
            $stmt = $this->conn->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }
        
        try { $this->conn->exec("ALTER TABLE extractions ADD COLUMN batch_id INT DEFAULT NULL AFTER user_id"); } catch(PDOException $e) {}
        try { $this->conn->exec("ALTER TABLE extractions ADD COLUMN keyword_used VARCHAR(255) DEFAULT NULL AFTER keyword"); } catch(PDOException $e) {}
        try { $this->conn->exec("ALTER TABLE extractions ADD COLUMN status VARCHAR(20) DEFAULT 'completed'"); } catch(PDOException $e) {}
        try { $this->conn->exec("ALTER TABLE extractions ADD COLUMN processed_at TIMESTAMP NULL"); } catch(PDOException $e) {}
        try { $this->conn->exec("ALTER TABLE users ADD COLUMN remaining_limit INT DEFAULT 0 AFTER daily_limit"); } catch(PDOException $e) {}
    }
}
?>