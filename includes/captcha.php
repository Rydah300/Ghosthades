<?php
class CaptchaSolver {
    private $apiKey;
    private $service;
    
    public function __construct() {
        $this->apiKey = CAPTCHA_API_KEY;
        $this->service = CAPTCHA_SERVICE;
    }
    
    public function solveGoogleRecaptcha($siteKey, $pageUrl) {
        if ($this->service === '2captcha') {
            return $this->solve2Captcha($siteKey, $pageUrl);
        } elseif ($this->service === 'capsolver') {
            return $this->solveCapSolver($siteKey, $pageUrl);
        }
        return false;
    }
    
    private function solve2Captcha($siteKey, $pageUrl) {
        $data = ['key' => $this->apiKey, 'method' => 'userrecaptcha', 'googlekey' => $siteKey, 'pageurl' => $pageUrl, 'json' => 1];
        $ch = curl_init('https://2captcha.com/in.php');
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if ($result['status'] !== 1) return false;
        return $this->poll2CaptchaResult($result['request']);
    }
    
    private function poll2CaptchaResult($captchaId) {
        for ($i = 0; $i < 30; $i++) {
            sleep(3);
            $data = ['key' => $this->apiKey, 'action' => 'get', 'id' => $captchaId, 'json' => 1];
            $ch = curl_init('https://2captcha.com/res.php');
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
            $result = json_decode(curl_exec($ch), true);
            curl_close($ch);
            if ($result['status'] === 1) return $result['request'];
            if ($result['request'] !== 'CAPCHA_NOT_READY') return false;
        }
        return false;
    }
    
    private function solveCapSolver($siteKey, $pageUrl) {
        $data = ['clientKey' => $this->apiKey, 'task' => ['type' => 'NoCaptchaTaskProxyless', 'websiteURL' => $pageUrl, 'websiteKey' => $siteKey]];
        $ch = curl_init('https://api.capsolver.com/createTask');
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($data), CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if (!$result['taskId']) return false;
        return $this->pollCapSolverResult($result['taskId']);
    }
    
    private function pollCapSolverResult($taskId) {
        for ($i = 0; $i < 30; $i++) {
            sleep(2);
            $data = ['clientKey' => $this->apiKey, 'taskId' => $taskId];
            $ch = curl_init('https://api.capsolver.com/getTaskResult');
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($data), CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
            $result = json_decode(curl_exec($ch), true);
            curl_close($ch);
            if ($result['status'] === 'ready') return $result['solution']['gRecaptchaResponse'];
            if ($result['status'] === 'processing') continue;
            return false;
        }
        return false;
    }
}
?>