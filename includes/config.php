<?php
// ============================================
// SQLITE — FIXED SESSION + DATABASE
// ============================================

// --- FIX SESSION: Use storage folder for sessions ---
$storagePath = __DIR__ . '/../storage';
if (!is_dir($storagePath)) {
    mkdir($storagePath, 0777, true);
    chmod($storagePath, 0777);
}

// Set session save path to storage folder
ini_set('session.save_path', $storagePath);
session_save_path($storagePath);

// Session settings
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);

// --- Load Database ---
require_once __DIR__ . '/db_sqlite.php';

// --- App Config ---
define('SECRET_KEY', getenv('SECRET_KEY') ?: 'gh0sth4d3s_auto_' . md5(__DIR__));
define('APP_URL', getenv('APP_URL') ?: 'https://your-app.railway.app');
define('DEFAULT_LIMIT', 100);
define('CAPTCHA_API_KEY', getenv('CAPTCHA_API_KEY') ?: '');
define('CAPTCHA_SERVICE', getenv('CAPTCHA_SERVICE') ?: '2captcha');
define('TELEGRAM_CHANNEL_URL', getenv('TELEGRAM_CHANNEL_URL') ?: '');
define('APP_PORT', getenv('PORT') ?: '8080');

// --- Start Session ---
session_start();

// --- Error Reporting ---
error_reporting(0);
ini_set('display_errors', 0);

// --- Anti-inspect ---
if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/curl|wget|postman|insomnia|python|go-http|java/i', $_SERVER['HTTP_USER_AGENT'])) {
    http_response_code(403);
    die('🚫 access denied.');
}

// --- Default Admin Credentials (hidden) ---
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', 'admin123');

// --- Helper Functions ---
function adminExists() {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        return $stmt->fetchColumn() > 0;
    } catch(Exception $e) {
        return false;
    }
}

function createDefaultAdmin() {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE username = '" . DEFAULT_ADMIN_USER . "'");
        if ($stmt->fetchColumn() == 0) {
            $hash = password_hash(DEFAULT_ADMIN_PASS, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, role, daily_limit, remaining_limit) VALUES (?, ?, 'admin', 99999, 99999)");
            $stmt->execute([DEFAULT_ADMIN_USER, $hash]);
            return true;
        }
    } catch(Exception $e) {
        return false;
    }
    return false;
}

function generateRandomUsername() {
    $adjectives = ['neon', 'shadow', 'cyber', 'ghost', 'dark', 'void', 'phantom', 'cipher', 'static', 'pulse', 'frost', 'ember', 'shade', 'echo', 'trace'];
    $nouns = ['wolf', 'hawk', 'viper', 'raven', 'tiger', 'fox', 'lynx', 'drake', 'owl', 'crow', 'lion', 'bear', 'eagle', 'fury', 'storm'];
    return $adjectives[array_rand($adjectives)] . '_' . $nouns[array_rand($nouns)] . '_' . rand(100, 999);
}

function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';
    return substr(str_shuffle(str_repeat($chars, 5)), 0, $length);
}
?>
