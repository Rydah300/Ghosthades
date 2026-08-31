<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$keyword = trim($input['keyword'] ?? '');
$targetCount = min((int)($input['target_count'] ?? 100), 5000);

if (strlen($keyword) < 3) {
    http_response_code(400);
    echo json_encode(['error' => 'keyword too short.']);
    exit;
}

if (!canExtract($_SESSION['user_id'], $targetCount)) {
    $limits = checkUserLimits($_SESSION['user_id']);
    http_response_code(429);
    echo json_encode([
        'error' => 'insufficient limit. remaining: ' . $limits['total_remaining'],
        'remaining' => $limits['total_remaining']
    ]);
    exit;
}

$extractLog = [];
$emails = extractEmailsFromGoogle($keyword, $targetCount, $_SESSION['user_id'], $extractLog);
if (empty($emails)) {
    echo json_encode(['emails' => [], 'total' => 0, 'domain_stats' => [], 'log' => $extractLog]);
    exit;
}

deductLimit($_SESSION['user_id'], count($emails));
$result = saveExtractionResult($_SESSION['user_id'], $keyword, $emails, $targetCount, null, 'completed');
logAction($_SESSION['user_id'], 'Extracted ' . count($emails) . ' emails for: ' . $keyword);

$limits = checkUserLimits($_SESSION['user_id']);

echo json_encode([
    'emails' => $emails,
    'total' => count($emails),
    'domain_stats' => getDomainStats($emails),
    'extraction_id' => $result['id'],
    'remaining' => $limits['total_remaining'],
    'log' => $extractLog
]);
?>