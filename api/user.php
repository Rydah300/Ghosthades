<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $input['action'] ?? '';

if ($action === 'list') {
    $stmt = $db->query("SELECT id, username, role, daily_limit, remaining_limit, telegram_connected FROM users");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} elseif ($action === 'add') {
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? 'user';
    $daily_limit = $input['daily_limit'] ?? 500;
    $remaining_limit = $input['remaining_limit'] ?? 0;
    
    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'error' => 'Username and password required']);
        exit;
    }
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    try {
        $stmt = $db->prepare("INSERT INTO users (username, password, role, daily_limit, remaining_limit) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $hash, $role, $daily_limit, $remaining_limit]);
        logAction($_SESSION['user_id'], 'Added user: ' . $username);
        echo json_encode(['status' => 'ok']);
    } catch(PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            echo json_encode(['status' => 'error', 'error' => 'Username already exists']);
        } else {
            echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
        }
    }
    
} elseif ($action === 'generate_bulk') {
    $count = (int)($input['count'] ?? 5);
    $daily_limit = (int)($input['daily_limit'] ?? 500);
    $remaining_limit = (int)($input['remaining_limit'] ?? 0);
    
    if ($count < 1 || $count > 100) {
        echo json_encode(['status' => 'error', 'error' => 'Count must be between 1 and 100']);
        exit;
    }
    
    $users = createRandomUsers($count, $daily_limit, $remaining_limit);
    echo json_encode(['status' => 'ok', 'users' => $users]);
    
} elseif ($action === 'change_password') {
    // Allow any logged-in user to change their own password
    $user_id = $_SESSION['user_id'];
    $current = $input['current'] ?? '';
    $new_password = $input['new_password'] ?? '';
    
    if (empty($current) || empty($new_password) || strlen($new_password) < 6) {
        echo json_encode(['status' => 'error', 'error' => 'Current password and new password (min 6 chars) required']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($current, $user['password'])) {
        echo json_encode(['status' => 'error', 'error' => 'Current password is incorrect']);
        exit;
    }
    
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hash, $user_id]);
    logAction($user_id, 'Changed password');
    echo json_encode(['status' => 'ok']);
    
} elseif ($action === 'delete') {
    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$input['id']]);
    logAction($_SESSION['user_id'], 'Deleted user ID: ' . $input['id']);
    echo json_encode(['status' => 'ok']);
    
} elseif ($action === 'logs') {
    $stmt = $db->query("SELECT l.*, u.username FROM logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 50");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} else {
    http_response_code(400);
    echo json_encode(['error' => 'invalid action']);
}
?>