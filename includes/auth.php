<?php
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        die('🚫 admin access only.');
    }
}

function checkUserLimits($user_id) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT daily_limit, remaining_limit FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) return ['daily' => 0, 'remaining' => 0, 'used' => 0];
    
    $stmt = $db->prepare("SELECT SUM(total) FROM extractions WHERE user_id = ? AND DATE(created_at) = DATE('now') AND status != 'failed'");
    $stmt->execute([$user_id]);
    $today_used = (int)$stmt->fetchColumn();
    
    return [
        'daily_limit' => (int)$user['daily_limit'],
        'daily_used' => $today_used,
        'daily_remaining' => max(0, $user['daily_limit'] - $today_used),
        'remaining_limit' => (int)$user['remaining_limit'],
        'total_remaining' => $user['remaining_limit'] + max(0, $user['daily_limit'] - $today_used)
    ];
}

function canExtract($user_id, $target_count) {
    $limits = checkUserLimits($user_id);
    $is_unlimited = $limits['daily_limit'] > 99998 || $limits['remaining_limit'] > 99998;
    if ($is_unlimited) return true;
    return $limits['total_remaining'] >= $target_count;
}

function deductLimit($user_id, $amount) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT daily_limit, remaining_limit FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user['daily_limit'] > 99998 || $user['remaining_limit'] > 99998) return true;
    
    $remaining = $user['remaining_limit'];
    if ($remaining >= $amount) {
        $stmt = $db->prepare("UPDATE users SET remaining_limit = remaining_limit - ? WHERE id = ?");
        $stmt->execute([$amount, $user_id]);
        return true;
    }
    if ($remaining > 0) {
        $stmt = $db->prepare("UPDATE users SET remaining_limit = 0 WHERE id = ?");
        $stmt->execute([$user_id]);
    }
    return true;
}

function redeemLicense($user_id, $code) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, limit_amount FROM licenses WHERE code = ? AND used = 0 AND (expiry_date IS NULL OR expiry_date > datetime('now'))");
    $stmt->execute([$code]);
    $license = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$license) return ['success' => false, 'message' => 'Invalid or used license.'];
    
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("UPDATE users SET remaining_limit = remaining_limit + ? WHERE id = ?");
        $stmt->execute([$license['limit_amount'], $user_id]);
        $stmt = $db->prepare("UPDATE licenses SET used = 1, user_id = ?, redeemed_at = datetime('now') WHERE id = ?");
        $stmt->execute([$user_id, $license['id']]);
        logAction($user_id, 'Redeemed license: ' . $code);
        $db->commit();
        return ['success' => true, 'message' => 'License redeemed! +' . $license['limit_amount'] . ' limit.', 'added' => $license['limit_amount']];
    } catch(Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => 'Error redeeming license.'];
    }
}

function generateLicense($created_by, $limit_amount, $user_id = null, $expiry_days = null) {
    $db = Database::getInstance()->getConnection();
    $prefix = getSetting('license_prefix', 'GH');
    $code = $prefix . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 12));
    $expiry = $expiry_days ? date('Y-m-d H:i:s', strtotime("+$expiry_days days")) : null;
    
    $stmt = $db->prepare("INSERT INTO licenses (code, user_id, limit_amount, created_by, expiry_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$code, $user_id, $limit_amount, $created_by, $expiry]);
    logAction($created_by, 'Generated license: ' . $code);
    return $code;
}

function generateBulkLicenses($created_by, $limit_amount, $count, $expiry_days = null) {
    $codes = [];
    for ($i = 0; $i < $count; $i++) {
        $codes[] = generateLicense($created_by, $limit_amount, null, $expiry_days);
    }
    return $codes;
}

function getSetting($key, $default = null) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetchColumn();
    return $result !== false ? $result : $default;
}

function logAction($user_id, $action) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $action, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
}

function createBatchJob($user_id, $keywords) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("INSERT INTO batch_jobs (user_id, total_keywords, status) VALUES (?, ?, 'queued')");
    $stmt->execute([$user_id, count($keywords)]);
    return $db->lastInsertId();
}

function updateBatchJob($batch_id, $data) {
    $db = Database::getInstance()->getConnection();
    $set = []; $params = [];
    foreach ($data as $key => $value) {
        $set[] = "$key = ?";
        $params[] = $value;
    }
    $params[] = $batch_id;
    $stmt = $db->prepare("UPDATE batch_jobs SET " . implode(', ', $set) . " WHERE id = ?");
    $stmt->execute($params);
}

function getBatchJobs($user_id, $limit = 10) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM batch_jobs WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createRandomUsers($count, $daily_limit = 500, $remaining_limit = 0) {
    $db = Database::getInstance()->getConnection();
    $users = [];
    for ($i = 0; $i < $count; $i++) {
        $username = generateRandomUsername();
        $password = generateRandomPassword();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, role, daily_limit, remaining_limit) VALUES (?, ?, 'user', ?, ?)");
        try {
            $stmt->execute([$username, $hash, $daily_limit, $remaining_limit]);
            $users[] = ['username' => $username, 'password' => $password];
            logAction($_SESSION['user_id'] ?? 0, 'Generated user: ' . $username);
        } catch(PDOException $e) {
            if ($e->errorInfo[1] == 19) {
                $username = generateRandomUsername() . rand(10, 99);
                $stmt->execute([$username, $hash, $daily_limit, $remaining_limit]);
                $users[] = ['username' => $username, 'password' => $password];
                logAction($_SESSION['user_id'] ?? 0, 'Generated user: ' . $username);
            }
        }
    }
    return $users;
}

function changePassword($user_id, $new_password) {
    $db = Database::getInstance()->getConnection();
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hash, $user_id]);
    logAction($user_id, 'Changed password');
    return true;
}
?>
