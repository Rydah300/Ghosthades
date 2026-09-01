<?php
// Force error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/config.php';
require_once '../includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$keyword = trim($input['keyword'] ?? '');
$targetCount = min((int)($input['target_count'] ?? 100), 5000);

if (strlen($keyword) < 3) {
    http_response_code(400);
    echo json_encode(['error' => 'Keyword too short (min 3 characters)']);
    exit;
}

// Check limits
if (!canExtract($_SESSION['user_id'], $targetCount)) {
    $limits = checkUserLimits($_SESSION['user_id']);
    http_response_code(429);
    echo json_encode([
        'error' => 'Insufficient limit. Remaining: ' . $limits['total_remaining'],
        'remaining' => $limits['total_remaining']
    ]);
    exit;
}

// Extract emails
$log = [];
try {
    // If no captcha key, use demo mode
    if (!CAPTCHA_API_KEY) {
        $log[] = '→ Demo mode: No captcha API key set';
        $log[] = '→ Returning sample emails for testing';
        $emails = [
            'demo1@gmail.com',
            'demo2@yahoo.com',
            'demo3@outlook.com',
            'demo4@company.com',
            'demo5@business.com',
            'sample@test.com',
            'example@domain.com'
        ];
    } else {
        $extractLog = [];
        $emails = extractEmailsFromGoogle($keyword, $targetCount, $_SESSION['user_id'], $extractLog);
        $log = $extractLog;
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Extraction error: ' . $e->getMessage()]);
    exit;
}

if (empty($emails)) {
    echo json_encode(['emails' => [], 'total' => 0, 'domain_stats' => [], 'log' => $log]);
    exit;
}

// Deduct limit
deductLimit($_SESSION['user_id'], count($emails));

// Save result
try {
    $result = saveExtractionResult($_SESSION['user_id'], $keyword, $emails, $targetCount, null, 'completed');
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save: ' . $e->getMessage()]);
    exit;
}

logAction($_SESSION['user_id'], 'Extracted ' . count($emails) . ' emails for: ' . $keyword);

$limits = checkUserLimits($_SESSION['user_id']);

echo json_encode([
    'emails' => $emails,
    'total' => count($emails),
    'domain_stats' => getDomainStats($emails),
    'extraction_id' => $result['id'],
    'remaining' => $limits['total_remaining'],
    'log' => $log
]);
?>
