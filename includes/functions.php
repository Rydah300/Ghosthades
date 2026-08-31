<?php
function extractEmailsFromGoogle($keyword, $targetCount, $user_id, &$log = null) {
    $emails = [];
    $pages = 0;
    $maxPages = 10;
    $captchaSolver = new CaptchaSolver();
    $log = [];
    
    while (count($emails) < $targetCount && $pages < $maxPages) {
        $start = $pages * 10;
        $url = "https://www.google.com/search?q=" . urlencode($keyword) . "&start=" . $start;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HEADER => true
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $log[] = "→ Page " . ($pages + 1) . ": " . $url;
        
        if (strpos($response, 'g-recaptcha') !== false || strpos($response, 'captcha') !== false) {
            $log[] = "⚠️ Captcha detected";
            preg_match('/data-sitekey="([^"]+)"/', $response, $matches);
            if (isset($matches[1]) && CAPTCHA_API_KEY) {
                $captchaResponse = $captchaSolver->solveGoogleRecaptcha($matches[1], $url);
                if ($captchaResponse) {
                    $log[] = "✅ Captcha solved";
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL => $url . '&g-recaptcha-response=' . urlencode($captchaResponse),
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_TIMEOUT => 30
                    ]);
                    $response = curl_exec($ch);
                    curl_close($ch);
                } else {
                    $log[] = "❌ Captcha solving failed";
                    break;
                }
            } else {
                $log[] = "⚠️ No captcha API key";
                break;
            }
        }
        
        preg_match_all('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $response, $matches);
        $found = count($matches[0]);
        $log[] = "→ Found " . $found . " emails";
        
        foreach ($matches[0] as $email) {
            if (!in_array($email, $emails)) {
                $emails[] = $email;
                if (count($emails) >= $targetCount) break;
            }
        }
        $pages++;
        sleep(2 + rand(0, 3));
    }
    
    $log[] = "✅ Extraction complete: " . count($emails) . " total";
    return $emails;
}

function getDomainStats($emails) {
    $domains = [];
    foreach ($emails as $email) {
        $parts = explode('@', $email);
        if (isset($parts[1])) {
            $domain = strtolower($parts[1]);
            $domains[$domain] = ($domains[$domain] ?? 0) + 1;
        }
    }
    arsort($domains);
    return $domains;
}

function saveExtractionResult($user_id, $keyword, $emails, $targetCount, $batch_id = null, $status = 'completed') {
    $db = Database::getInstance()->getConnection();
    $domainStats = getDomainStats($emails);
    
    $stmt = $db->prepare("INSERT INTO extractions (user_id, batch_id, keyword, keyword_used, target_count, emails, total, domain_stats, status, processed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $user_id, $batch_id, $keyword, $keyword, $targetCount,
        implode("\n", $emails), count($emails), json_encode($domainStats), $status
    ]);
    
    $extractionId = $db->lastInsertId();
    
    if (!empty($emails)) {
        $filename = 'emails_' . date('Y-m-d_H-i-s') . '_' . $user_id . '.txt';
        $filepath = __DIR__ . '/../storage/' . $filename;
        file_put_contents($filepath, implode("\n", $emails));
        $stmt = $db->prepare("INSERT INTO saved_results (user_id, extraction_id, filename, filepath, email_count) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $extractionId, $filename, $filepath, count($emails)]);
    }
    
    return ['id' => $extractionId, 'filename' => $filename ?? null, 'count' => count($emails)];
}

function sendTelegramMessage($botToken, $chatId, $message) {
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['chat_id' => $chatId, 'text' => $message, 'parse_mode' => 'HTML']),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function sendTelegramFile($botToken, $chatId, $filepath) {
    $url = "https://api.telegram.org/bot{$botToken}/sendDocument";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['chat_id' => $chatId, 'document' => new CURLFile($filepath)],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
?>