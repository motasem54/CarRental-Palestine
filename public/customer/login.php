<?php
require_once '../../config/settings.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance()->getConnection();
    $phone = sanitizeInput($_POST['phone']);
    $idNumber = sanitizeInput($_POST['id_number']);
    
    $stmt = $db->prepare("SELECT * FROM customers WHERE phone = ? AND id_number = ?");
    $stmt->execute([$phone, $idNumber]);
    $customer = $stmt->fetch();
    
    if ($customer) {
        $_SESSION['customer_id'] = $customer['id'];
        $_SESSION['customer_name'] = $customer['full_name'];
        redirect(BASE_URL . '/public/customer/dashboard.php');
    } else {
        $error = 'بيانات خاطئة';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول العميل - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 400px;
            margin: 0 auto;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo i {
            font-size: 4rem;
            color: #FF5722;
        }
        .btn-login {
            background: linear-gradient(135deg, #FF5722 0%, #E64A19 100%);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="logo">
                <i class="fas fa-user-circle"></i>
                <h4>بوابة العميل</h4>
                <p class="text-muted"><?php echo SITE_NAME; ?></p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="tel" name="phone" class="form-control" placeholder="+970599123456" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">رقم الهوية</label>
                    <input type="text" name="id_number" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>دخول
                </button>
            </form>
            
            <div class="text-center mt-3">
                <a href="../index.php" class="text-muted">العودة للرئيسية</a>
            </div>
        </div>
    </div>
</body>
</html>