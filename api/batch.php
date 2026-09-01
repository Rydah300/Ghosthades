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

if ($action === 'start') {
    $keywords = $input['keywords'] ?? [];
    $targetPerKeyword = (int)($input['target_per_keyword'] ?? 50);
    
    if (empty($keywords)) {
        echo json_encode(['error' => 'no keywords provided']);
        exit;
    }
    
    $totalNeeded = count($keywords) * $targetPerKeyword;
    if (!canExtract($user_id, $totalNeeded)) {
        $limits = checkUserLimits($user_id);
        echo json_encode(['error' => 'insufficient limit. need ' . $totalNeeded . ', have ' . $limits['total_remaining']]);
        exit;
    }
    
    $batch_id = createBatchJob($user_id, $keywords);
    updateBatchJob($batch_id, ['status' => 'processing', 'started_at' => date('Y-m-d H:i:s')]);
    
    $processed = 0;
    $totalEmails = 0;
    $logs = [];
    
    foreach ($keywords as $keyword) {
        $keyword = trim($keyword);
        if (empty($keyword)) continue;
        
        if (!canExtract($user_id, $targetPerKeyword)) {
            $logs[] = ['time' => date('H:i:s'), 'type' => 'error', 'message' => "❌ Limit exhausted, stopping batch."];
            break;
        }
        
        $logs[] = ['time' => date('H:i:s'), 'type' => 'info', 'message' => "→ Processing: <span class='keyword-highlight'>" . htmlspecialchars($keyword) . "</span>"];
        
        $extractLog = [];
        $emails = extractEmailsFromGoogle($keyword, $targetPerKeyword, $user_id, $extractLog);
        
        foreach ($extractLog as $logMsg) {
            $logs[] = ['time' => date('H:i:s'), 'type' => 'info', 'message' => $logMsg];
        }
        
        if (!empty($emails)) {
            deductLimit($user_id, count($emails));
            $result = saveExtractionResult($user_id, $keyword, $emails, $targetPerKeyword, $batch_id, 'completed');
            $totalEmails += count($emails);
            $logs[] = ['time' => date('H:i:s'), 'type' => 'success', 'message' => "✅ Found " . count($emails) . " emails for: " . htmlspecialchars($keyword)];
            
            $stmt = $db->prepare("SELECT telegram_bot_token, telegram_chat_id, telegram_connected FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && $user['telegram_connected']) {
                $filename = '../storage/emails_' . date('Y-m-d_H-i-s') . '_' . $user_id . '.txt';
                file_put_contents($filename, implode("\n", $emails));
                sendTelegramFile($user['telegram_bot_token'], $user['telegram_chat_id'], $filename);
                unlink($filename);
                $logs[] = ['time' => date('H:i:s'), 'type' => 'success', 'message' => "✈️ Sent to Telegram"];
            }
        } else {
            $logs[] = ['time' => date('H:i:s'), 'type' => 'error', 'message' => "❌ No emails for: " . htmlspecialchars($keyword)];
        }
        
        $processed++;
        updateBatchJob($batch_id, ['processed_keywords' => $processed, 'total_emails' => $totalEmails]);
    }
    
    updateBatchJob($batch_id, ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s'), 'processed_keywords' => $processed, 'total_emails' => $totalEmails]);
    logAction($user_id, 'Batch completed: ' . $processed . ' keywords, ' . $totalEmails . ' emails');
    
    echo json_encode(['batch_id' => $batch_id, 'status' => 'completed', 'processed' => $processed, 'total_emails' => $totalEmails, 'logs' => $logs]);
    
} elseif ($action === 'status') {
    $batch_id = (int)($_GET['batch_id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM batch_jobs WHERE id = ? AND user_id = ?");
    $stmt->execute([$batch_id, $user_id]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$batch) { echo json_encode(['error' => 'batch not found']); exit; }
    
    $stmt = $db->prepare("SELECT keyword, total, status, processed_at FROM extractions WHERE batch_id = ? ORDER BY processed_at ASC");
    $stmt->execute([$batch_id]);
    $extractions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $logs = [];
    $logs[] = ['time' => $batch['created_at'], 'type' => 'info', 'message' => "📋 Batch created with " . $batch['total_keywords'] . " keywords"];
    if ($batch['started_at']) $logs[] = ['time' => $batch['started_at'], 'type' => 'info', 'message' => "🔄 Processing started..."];
    foreach ($extractions as $ex) {
        $statusIcon = $ex['status'] === 'completed' ? '✅' : '❌';
        $logs[] = ['time' => $ex['processed_at'], 'type' => $ex['status'], 'message' => "$statusIcon " . htmlspecialchars($ex['keyword']) . " → " . $ex['total'] . " emails"];
    }
    if ($batch['completed_at']) $logs[] = ['time' => $batch['completed_at'], 'type' => 'success', 'message' => "🏁 Batch completed: " . $batch['total_emails'] . " total emails"];
    
    echo json_encode(['batch_id' => $batch['id'], 'status' => $batch['status'], 'total_keywords' => $batch['total_keywords'], 'processed_keywords' => $batch['processed_keywords'], 'total_emails' => $batch['total_emails'], 'logs' => $logs]);
    
} elseif ($action === 'history') {
    $limit = (int)($_GET['limit'] ?? 10);
    $stmt = $db->prepare("SELECT * FROM batch_jobs WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} else {
    http_response_code(400);
    echo json_encode(['error' => 'invalid action']);
}
?>
