<?php
require_once __DIR__ . '/config.php';

// Simple error logger
function log_error($msg) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
    @file_put_contents($logDir . '/errors.log', date('Y-m-d H:i:s') . ' -> ' . $msg . PHP_EOL, FILE_APPEND);
}

function is_user_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user_id() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function require_user_login() {
    if (!is_user_logged_in()) {
        header('Location: ' . BASE_URL . 'user/login.php');
        exit;
    }
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

function require_admin_login() {
    if (!is_admin_logged_in()) {
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit;
    }
}

function is_staff_logged_in() {
    return isset($_SESSION['staff_id']);
}

function require_staff_login() {
    if (!is_staff_logged_in()) {
        header('Location: ' . BASE_URL . 'staff/login.php');
        exit;
    }
}

function redirect($path) {
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}

function sanitize($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function generate_invoice_id($user_id) {
    return 'INV-' . $user_id . '-' . time();
}

function get_user_by_id($mysqli, $user_id) {
    if (empty($mysqli)) return null;
    try {
        $stmt = $mysqli->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    } catch (\Throwable $e) {
        log_error('get_user_by_id error: ' . $e->getMessage());
        return null;
    }
}

function get_active_subscription_for_vehicle($mysqli, $vehicle_id) {
    if (empty($mysqli)) return null;
    try {
        $now = date('Y-m-d H:i:s');
        $stmt = $mysqli->prepare('SELECT s.*, p.name AS plan_name FROM subscriptions s JOIN subscription_plans p ON s.plan_id = p.id WHERE s.vehicle_id = ? AND s.start_at <= ? AND s.end_at >= ? ORDER BY s.end_at DESC LIMIT 1');
        $stmt->bind_param('iss', $vehicle_id, $now, $now);
        $stmt->execute();
        $result = $stmt->get_result();
        $subscription = $result->fetch_assoc();
        $stmt->close();
        return $subscription;
    } catch (\Throwable $e) {
        log_error('get_active_subscription_for_vehicle error: ' . $e->getMessage());
        return null;
    }
}

function calculate_subscription_pricing($mysqli, $user_id, $vehicle_id, $plan_id) {
    $pricing = [
        'duration_days' => 0,
        'base_price' => 0.0,
        'final_price' => 0.0,
        'first_time_applied' => 0,
        'promotion_discount_percent' => 0.0
    ];

    if (empty($mysqli)) return $pricing;
    try {
        // Get plan
        $stmt = $mysqli->prepare('SELECT duration_days, price FROM subscription_plans WHERE id = ?');
        $stmt->bind_param('i', $plan_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $plan = $result->fetch_assoc();
        $stmt->close();

        if (!$plan) return $pricing;

        $pricing['duration_days'] = (int)$plan['duration_days'];
        $pricing['base_price'] = (float)$plan['price'];
        $final = $pricing['base_price'];

        // Check first-time purchase
        $stmt = $mysqli->prepare('SELECT is_first_purchase FROM users WHERE id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && (int)$user['is_first_purchase'] === 1) {
            $pricing['first_time_applied'] = 1;
            $final = $final * 0.5; // 50% discount
        }

        // Check promotions for vehicle type (especially bikes)
        $stmt = $mysqli->prepare('SELECT type FROM vehicles WHERE id = ?');
        $stmt->bind_param('i', $vehicle_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $vehicle = $result->fetch_assoc();
        $stmt->close();

        if ($vehicle) {
            $vehicle_type = $vehicle['type'];
            $now = date('Y-m-d H:i:s');
            $stmt = $mysqli->prepare('SELECT discount_percent FROM promotions WHERE vehicle_type = ? AND active = 1 AND start_at <= ? AND end_at >= ? ORDER BY discount_percent DESC LIMIT 1');
            $stmt->bind_param('sss', $vehicle_type, $now, $now);
            $stmt->execute();
            $result = $stmt->get_result();
            $promo = $result->fetch_assoc();
            $stmt->close();

            if ($promo) {
                $pricing['promotion_discount_percent'] = (float)$promo['discount_percent'];
                $final = $final * (1 - ($pricing['promotion_discount_percent'] / 100.0));
            }
        }

        $pricing['final_price'] = round($final, 2);
        return $pricing;
    } catch (\Throwable $e) {
        log_error('calculate_subscription_pricing error: ' . $e->getMessage());
        return $pricing;
    }
}

function generate_qr_image($data, $filePath) {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    // Expecting phpqrcode library installed at lib/phpqrcode/qrlib.php
    $qrlib = __DIR__ . '/lib/phpqrcode/qrlib.php';
    if (!file_exists($qrlib)) {
        // Fallback: try to generate a visible PNG placeholder using GD
        if (function_exists('imagecreatetruecolor') && function_exists('imagestring') && function_exists('imagepng')) {
            $size = 240;
            $im = imagecreatetruecolor($size, $size);
            $bg = imagecolorallocate($im, 240, 240, 240);
            $fg = imagecolorallocate($im, 60, 60, 60);
            imagefilledrectangle($im, 0, 0, $size, $size, $bg);
            $text = 'QR';
            $fontWidth = imagefontwidth(5);
            $fontHeight = imagefontheight(5);
            $textWidth = strlen($text) * $fontWidth;
            $x = ($size - $textWidth) / 2;
            $y = ($size - $fontHeight) / 2;
            imagestring($im, 5, (int)$x, (int)$y, $text, $fg);
            @imagepng($im, $filePath);
            imagedestroy($im);
            return;
        }

        // Final fallback: small transparent PNG
        $placeholderBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
        $pngData = base64_decode($placeholderBase64);
        file_put_contents($filePath, $pngData);
        return;
    }

        require_once $qrlib;
    if (class_exists('QRcode')) {
        if (!defined('QR_ECLEVEL_L')) {
            define('QR_ECLEVEL_L', 'L');
        }
        /** @phpstan-ignore-next-line */
        /** @psalm-suppress UndefinedClass */
        QRcode::png($data, $filePath, 'L', 4);
    }
}

function sendSMS($phone, $message) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (empty($phone) || empty($message)) {
        return false;
    }

    // If you use Fast2SMS, configure SMS_API_KEY in config.php and implement API call here.
    if (!empty(SMS_API_KEY)) {
        $curl = curl_init();
        $payload = [
            'sender_id' => 'TXTIND',
            'message' => $message,
            'route' => 'v3',
            'numbers' => $phone
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://www.fast2sms.com/dev/bulkV2',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_HTTPHEADER => [
                'authorization: ' . SMS_API_KEY,
                'cache-control: no-cache'
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        return $err ? false : true;
    }

    // Twilio example (if configured)
    if (!empty(TWILIO_SID) && !empty(TWILIO_TOKEN) && !empty(TWILIO_FROM)) {
        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_SID . '/Messages.json';
        $data = [
            'From' => TWILIO_FROM,
            'To' => '+91' . $phone,
            'Body' => $message,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, TWILIO_SID . ':' . TWILIO_TOKEN);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        return $err ? false : true;
    }

    // No SMS provider configured; for development, just log to file
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    file_put_contents($logDir . '/sms.log', date('Y-m-d H:i:s') . ' -> ' . $phone . ' : ' . $message . PHP_EOL, FILE_APPEND);

    return true;
}

?>
