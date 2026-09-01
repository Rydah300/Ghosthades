<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'list') {
    $stmt = $db->prepare("SELECT * FROM saved_results WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} elseif ($action === 'download') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM saved_results WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && file_exists($row['filepath'])) {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $row['filename'] . '"');
        readfile($row['filepath']);
        exit;
    }
    http_response_code(404);
    echo json_encode(['error' => 'file not found']);
    
} elseif ($action === 'send_telegram') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM saved_results WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $stmt = $db->prepare("SELECT telegram_bot_token, telegram_chat_id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && $user['telegram_bot_token'] && $user['telegram_chat_id']) {
            sendTelegramFile($user['telegram_bot_token'], $user['telegram_chat_id'], $row['filepath']);
            echo json_encode(['status' => 'ok', 'message' => 'sent to telegram']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'telegram not connected']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'not found']);
    }
    
} elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare("SELECT filepath FROM saved_results WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && file_exists($row['filepath'])) unlink($row['filepath']);
    $stmt = $db->prepare("DELETE FROM saved_results WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    echo json_encode(['status' => 'ok']);
    
} elseif ($action === 'save' || $action === 'auto_save') {
    echo json_encode(['status' => 'ok']);
    
} else {
    http_response_code(400);
    echo json_encode(['error' => 'invalid action']);
}
?>
