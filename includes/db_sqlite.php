<?php
class Database {
    private static $instance = null;
    private $db;
    private $dbFile;
    
    private function __construct() {
        $this->dbFile = __DIR__ . '/../storage/ghosthades.sqlite';
        
        // Auto-create storage folder if missing
        if (!is_dir(__DIR__ . '/../storage')) {
            mkdir(__DIR__ . '/../storage', 0777, true);
        }
        
        try {
            $this->db = new PDO('sqlite:' . $this->dbFile);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->initTables();
        } catch(PDOException $e) {
            die("❌ SQLite connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->db;
    }
    
    private function initTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'user',
            daily_limit INTEGER DEFAULT 500,
            remaining_limit INTEGER DEFAULT 0,
            telegram_bot_token TEXT DEFAULT NULL,
            telegram_chat_id TEXT DEFAULT NULL,
            telegram_connected INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS extractions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            batch_id INTEGER DEFAULT NULL,
            keyword TEXT,
            keyword_used TEXT DEFAULT NULL,
            target_count INTEGER DEFAULT 100,
            emails TEXT,
            total INTEGER DEFAULT 0,
            domain_stats TEXT,
            status TEXT DEFAULT 'completed',
            processed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id),
            INDEX(batch_id)
        );
        
        CREATE TABLE IF NOT EXISTS batch_jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            status TEXT DEFAULT 'queued',
            total_keywords INTEGER DEFAULT 0,
            processed_keywords INTEGER DEFAULT 0,
            total_emails INTEGER DEFAULT 0,
            started_at DATETIME DEFAULT NULL,
            completed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS saved_results (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            extraction_id INTEGER,
            filename TEXT,
            filepath TEXT,
            email_count INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS licenses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT UNIQUE NOT NULL,
            user_id INTEGER DEFAULT NULL,
            limit_amount INTEGER NOT NULL,
            used INTEGER DEFAULT 0,
            created_by INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            redeemed_at DATETIME DEFAULT NULL,
            expiry_date DATETIME DEFAULT NULL
        );
        
        CREATE TABLE IF NOT EXISTS logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT,
            ip TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS system_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT UNIQUE,
            setting_value TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        ";
        $this->db->exec($sql);
        
        // Create default admin (hidden)
        if (!adminExists()) {
            createDefaultAdmin();
        }
        
        // Default settings
        $settings = [
            'daily_limit' => '500',
            'extraction_timeout' => '60',
            'captcha_enabled' => 'true',
            'license_prefix' => 'GH',
            'default_user_limit' => '500'
        ];
        foreach ($settings as $key => $value) {
            $stmt = $this->db->prepare("INSERT OR IGNORE INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }
    }
}
?>
