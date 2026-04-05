<?php
// includes/sms_helper.php — Send OTP SMS via configured provider
require_once __DIR__ . '/sms_config.php';

/**
 * Send an OTP SMS to a mobile number.
 *
 * @param string $mobile  10-digit Indian mobile number
 * @param string $otp     The OTP code to send
 * @return array ['success' => bool, 'message' => string, 'dev_otp' => string|null]
 */
function sendOtpSMS(string $mobile, string $otp): array {
    // Strip country code if present
    $mobile = preg_replace('/^\+91/', '', trim($mobile));
    $mobile = preg_replace('/[^0-9]/', '', $mobile);

    if (strlen($mobile) !== 10) {
        return ['success' => false, 'message' => 'Invalid mobile number length.', 'dev_otp' => null];
    }

    // ── Dev/Mock Mode ────────────────────────────────────────────────────────
    if (SMS_DEV_MODE || SMS_PROVIDER === 'mock') {
        error_log("[SMS MOCK] OTP {$otp} → {$mobile}");
        return ['success' => true, 'message' => 'OTP ready (dev mode).', 'dev_otp' => $otp];
    }

    // ── Fast2SMS ─────────────────────────────────────────────────────────────
    if (SMS_PROVIDER === 'fast2sms') {
        return _sendFast2SMS($mobile, $otp);
    }

    // ── MSG91 ─────────────────────────────────────────────────────────────────
    if (SMS_PROVIDER === 'msg91') {
        return _sendMSG91($mobile, $otp);
    }

    // ── Textlocal ─────────────────────────────────────────────────────────────
    if (SMS_PROVIDER === 'textlocal') {
        return _sendTextlocal($mobile, $otp);
    }

    return ['success' => false, 'message' => 'No SMS provider configured.', 'dev_otp' => null];
}

// ─── Fast2SMS Implementation ─────────────────────────────────────────────────
function _sendFast2SMS(string $mobile, string $otp): array {
    if (empty(FAST2SMS_API_KEY) || FAST2SMS_API_KEY === 'YOUR_FAST2SMS_API_KEY_HERE') {
        // API key not configured — fall back to dev mode silently
        error_log("[SMS] Fast2SMS API key not configured. Falling back to dev mode. OTP:{$otp}");
        return ['success' => true, 'message' => 'OTP ready (API key not set — dev mode).', 'dev_otp' => $otp];
    }

    $message = "Your OTR System OTP is: {$otp}. Valid for " . OTP_EXPIRY_MINUTES . " minutes. Do not share this OTP with anyone.";

    // Using the Fast2SMS Bulk V2 URL with 'otp' route
    $url = 'https://www.fast2sms.com/dev/bulkV2';
    
    // Some keys work better with the 'v3' or 'v4' or 'otp' route specifically
    $params = [
        'authorization' => FAST2SMS_API_KEY,
        'variables_values' => $otp,
        'route'  => 'otp',
        'numbers' => $mobile,
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url . '?' . http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'cache-control: no-cache',
            'accept: */*'
        ],
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) {
        error_log("[SMS Fast2SMS cURL error] " . $err);
        return ['success' => false, 'message' => 'Network error: ' . $err, 'dev_otp' => null];
    }

    $result = json_decode($response, true);
    
    // LOG THE ACTUAL ERROR FOR ADMIN
    if (!$result) {
        error_log("[SMS Fast2SMS RAW Response] " . $response);
        return ['success' => false, 'message' => 'Invalid API Response: ' . $response, 'dev_otp' => null];
    }

    if (isset($result['return']) && $result['return'] === true) {
        return ['success' => true, 'message' => 'OTP sent successfully.', 'dev_otp' => null];
    }

    // Specific Fast2SMS Error Codes
    $errMsg = $result['message'][0] ?? $result['message'] ?? 'Unknown Error';
    
    if ($errMsg === 'B' || strpos($errMsg, 'Balance') !== false) {
        $errMsg = 'Insufficient Wallet Balance (Check your Fast2SMS Wallet)';
    } elseif ($errMsg === 'A' || $errMsg === 'Invalid Key') {
        $errMsg = 'Invalid API Key (Please check your Dev API tab in Fast2SMS)';
    }

    return ['success' => false, 'message' => 'SMS failed: ' . $errMsg, 'dev_otp' => null];
}

// ─── MSG91 Implementation ────────────────────────────────────────────────────
function _sendMSG91(string $mobile, string $otp): array {
    if (empty(MSG91_AUTH_KEY)) {
        return ['success' => false, 'message' => 'MSG91 auth key not configured.', 'dev_otp' => null];
    }

    $url  = 'https://api.msg91.com/api/v5/otp';
    $data = [
        'authkey'     => MSG91_AUTH_KEY,
        'mobile'      => '91' . $mobile,
        'otp'         => $otp,
        'template_id' => MSG91_TEMPLATE_ID,
        'sender'      => MSG91_SENDER_ID,
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    error_log("[SMS MSG91 Response] " . $response);

    if (isset($result['type']) && $result['type'] === 'success') {
        return ['success' => true, 'message' => 'OTP sent via MSG91.', 'dev_otp' => null];
    }
    return ['success' => false, 'message' => 'MSG91 error: ' . ($result['message'] ?? 'Unknown'), 'dev_otp' => null];
}

// ─── Textlocal Implementation ─────────────────────────────────────────────────
function _sendTextlocal(string $mobile, string $otp): array {
    // Textlocal implementation placeholder
    return ['success' => false, 'message' => 'Textlocal not implemented.', 'dev_otp' => null];
}
?>
