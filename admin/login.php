<?php
/**
 * Admin Login Page
 * 🔐 صفحة تسجيل الدخول
 */

require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();

// If already logged in, redirect
if ($auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/dashboard.php');
}

$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'الرجاء إدخال اسم المستخدم وكلمة المرور';
    } else {
        $result = $auth->login($username, $password);
        
        if ($result['success']) {
            redirect(ADMIN_URL . '/dashboard.php');
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>تسجيل الدخول - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #FF5722;
            --primary-dark: #E64A19;
            --primary-light: #FF7043;
            --dark: #1a1a2e;
            --darker: #16213e;
            --success: #00C853;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            padding: 15px;
        }

        /* Animated Background Pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 87, 34, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 87, 34, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255, 87, 34, 0.1) 0%, transparent 50%);
            animation: gradientShift 15s ease infinite;
            z-index: 0;
        }

        @keyframes gradientShift {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.1); }
        }

        /* Floating Cars Animation */
        .floating-cars {
            position: fixed;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .car-icon {
            position: absolute;
            font-size: 40px;
            color: rgba(255, 87, 34, 0.1);
            animation: floatCar 20s infinite linear;
        }

        .car-icon:nth-child(1) { left: 10%; animation-duration: 18s; animation-delay: 0s; }
        .car-icon:nth-child(2) { left: 30%; animation-duration: 22s; animation-delay: 4s; }
        .car-icon:nth-child(3) { left: 50%; animation-duration: 20s; animation-delay: 2s; }
        .car-icon:nth-child(4) { left: 70%; animation-duration: 24s; animation-delay: 6s; }
        .car-icon:nth-child(5) { left: 90%; animation-duration: 19s; animation-delay: 3s; }

        @keyframes floatCar {
            0% { transform: translateY(110vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-110vh) rotate(360deg); opacity: 0; }
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 480px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 
                        0 0 0 1px rgba(255, 255, 255, 0.1);
            animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light), var(--primary));
            background-size: 200% 100%;
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .logo-container {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-image {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(255, 87, 34, 0.4),
                        0 0 0 8px rgba(255, 87, 34, 0.1);
            animation: logoPulse 3s ease-in-out infinite;
            position: relative;
        }

        .logo-image::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 30px;
            background: linear-gradient(135deg, transparent 0%, rgba(255, 255, 255, 0.3) 50%, transparent 100%);
            animation: logoShine 3s infinite;
        }

        @keyframes logoPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes logoShine {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(200%) rotate(45deg); }
        }

        .logo-image i {
            font-size: 4rem;
            color: white;
            position: relative;
            z-index: 1;
        }

        .logo-container h1 {
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
            font-size: 1.8rem;
            letter-spacing: -0.5px;
        }

        .logo-container p {
            color: #666;
            font-size: 0.95rem;
            margin: 0;
        }

        .form-label {
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group-text {
            background: white;
            border: 2px solid #e0e0e0;
            border-left: none;
            color: var(--primary);
            padding: 12px 15px;
            border-radius: 12px 0 0 12px;
            transition: all 0.3s;
        }

        .form-control {
            background: white !important;
            border: 2px solid #e0e0e0 !important;
            border-right: none !important;
            color: var(--dark) !important;
            border-radius: 0 12px 12px 0 !important;
            padding: 12px 15px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary) !important;
            box-shadow: none !important;
        }

        .form-control:focus + .input-group-text,
        .input-group:focus-within .input-group-text {
            border-color: var(--primary);
        }

        .form-control::placeholder { color: #999; }

        .password-toggle {
            background: white;
            border: 2px solid #e0e0e0;
            border-right: none;
            cursor: pointer;
            padding: 12px 15px;
            transition: all 0.3s;
        }

        .password-toggle:hover {
            background: #f8f9fa;
            color: var(--primary);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            color: white;
            padding: 14px 30px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(255, 87, 34, 0.3);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            right: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: 0.6s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 87, 34, 0.4);
        }

        .btn-login:hover::before { right: 100%; }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);
            border: 2px solid #ffcdd2;
            border-right: 4px solid #f44336;
            border-radius: 12px;
            color: #d32f2f;
            padding: 14px 18px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .form-check {
            margin-bottom: 20px;
        }

        .form-check-input {
            width: 20px;
            height: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-check-label {
            color: #666;
            cursor: pointer;
            margin-right: 8px;
            user-select: none;
        }

        .footer-text {
            text-align: center;
            margin-top: 25px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
        }

        .footer-text p {
            margin: 5px 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        /* Mobile Responsive */
        @media (max-width: 576px) {
            body { padding: 10px; }
            
            .login-card {
                padding: 30px 20px;
                border-radius: 20px;
            }
            
            .logo-image {
                width: 100px;
                height: 100px;
                border-radius: 25px;
            }
            
            .logo-image i { font-size: 3rem; }
            
            .logo-container h1 { font-size: 1.5rem; }
            
            .logo-container p { font-size: 0.85rem; }
            
            .input-group-text,
            .form-control,
            .password-toggle {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
            
            .btn-login {
                padding: 12px 25px;
                font-size: 0.95rem;
            }
        }

        @media (max-width: 400px) {
            .login-card { padding: 25px 15px; }
            .logo-image { width: 80px; height: 80px; }
            .logo-image i { font-size: 2.5rem; }
        }

        /* Touch Optimization */
        @media (hover: none) and (pointer: coarse) {
            .btn-login:active {
                transform: scale(0.98);
            }
            
            .password-toggle:active {
                background: #e0e0e0;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="floating-cars">
        <i class="fas fa-car car-icon"></i>
        <i class="fas fa-car-side car-icon"></i>
        <i class="fas fa-taxi car-icon"></i>
        <i class="fas fa-truck car-icon"></i>
        <i class="fas fa-bus car-icon"></i>
    </div>

    <div class="login-container">
        <div class="login-card">
            <!-- Logo -->
            <div class="logo-container">
                <div class="logo-image">
                    <i class="fas fa-car"></i>
                </div>
                <h1>نظام تأجير السيارات</h1>
                <p>🇵🇸 مرحباً بك! سجّل دخولك للمتابعة</p>
            </div>

            <?php if ($error): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="" autocomplete="on">
                <div class="mb-3">
                    <label class="form-label">اسم المستخدم أو البريد</label>
                    <div class="input-group">
                        <input type="text" name="username" class="form-control" 
                               placeholder="أدخل اسم المستخدم" 
                               required 
                               autofocus
                               autocomplete="username">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">كلمة المرور</label>
                    <div class="input-group">
                        <input type="password" 
                               name="password" 
                               id="password" 
                               class="form-control"
                               placeholder="أدخل كلمة المرور" 
                               required
                               autocomplete="current-password">
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                    </div>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">
                        تذكرني
                    </label>
                </div>

                <button type="submit" name="login" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    تسجيل الدخول
                </button>
            </form>
        </div>

        <div class="footer-text">
            <p>© 2024 نظام تأجير السيارات - جميع الحقوق محفوظة</p>
            <p>🇵🇸 صُنع بكل حب في فلسطين</p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Prevent zoom on input focus (iOS)
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.style.fontSize = '16px';
            });
        });
    </script>
</body>
</html>