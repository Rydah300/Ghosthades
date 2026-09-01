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
$action = $_GET['action'] ?? $input['action'] ?? '';

if ($action === 'connect') {
    $botToken = trim($input['bot_token'] ?? '');
    $chatId = trim($input['chat_id'] ?? '');
    if (!$botToken || !$chatId) {
        echo json_encode(['status' => 'error', 'error' => 'missing token or chat ID']);
        exit;
    }
    $test = sendTelegramMessage($botToken, $chatId, "✅ Your Bot Has Been Connected To GhostHades Lead Extractor");
    if (isset($test['ok']) && $test['ok'] === true) {
        $stmt = $db->prepare("UPDATE users SET telegram_bot_token = ?, telegram_chat_id = ?, telegram_connected = 1 WHERE id = ?");
        $stmt->execute([$botToken, $chatId, $user_id]);
        logAction($user_id, 'Connected Telegram bot');
        echo json_encode(['status' => 'ok', 'message' => 'bot connected successfully']);
    } else {
        echo json_encode(['status' => 'error', 'error' => 'invalid token or chat ID']);
    }
    
} elseif ($action === 'status') {
    $stmt = $db->prepare("SELECT telegram_bot_token, telegram_chat_id, telegram_connected FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['connected' => $user['telegram_connected'] ?? false]);
    
} elseif ($action === 'test') {
    $stmt = $db->prepare("SELECT telegram_bot_token, telegram_chat_id FROM users WHERE id = ? AND telegram_connected = 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        sendTelegramMessage($user['telegram_bot_token'], $user['telegram_chat_id'], "✅ Test message from GhostHades Extractor — your bot is working.");
        echo json_encode(['status' => 'ok', 'message' => 'test sent']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'telegram not connected']);
    }
    
} elseif ($action === 'send_results') {
    $emails = $input['emails'] ?? [];
    if (empty($emails)) {
        echo json_encode(['status' => 'error', 'message' => 'no emails to send']);
        exit;
    }
    $stmt = $db->prepare("SELECT telegram_bot_token, telegram_chat_id FROM users WHERE id = ? AND telegram_connected = 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'telegram not connected']);
        exit;
    }
    $filename = '../storage/emails_' . date('Y-m-d_H-i-s') . '.txt';
    file_put_contents($filename, implode("\n", $emails));
    sendTelegramFile($user['telegram_bot_token'], $user['telegram_chat_id'], $filename);
    unlink($filename);
    echo json_encode(['status' => 'ok', 'message' => 'sent ' . count($emails) . ' emails to telegram']);
    
} else {
    http_response_code(400);
    echo json_encode(['error' => 'invalid action']);
}
?>
