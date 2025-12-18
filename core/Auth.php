<?php
/**
 * Authentication Class
 * 🔐 نظام المصادقة والتفويض
 */

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * User login
     */
    public function login($username, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Check if user is active
                if ($user['status'] !== 'active') {
                    return ['success' => false, 'message' => 'الحساب غير نشط'];
                }

                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['last_activity'] = time();

                // Update last login
                $updateStmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);

                // Log activity
                $this->logActivity($user['id'], 'login', 'تسجيل دخول ناجح');

                return ['success' => true, 'user' => $user];
            }

            // Log failed login
            $this->logSecurityEvent(null, 'failed_login', 'محاولة تسجيل دخول فاشلة: ' . $username);

            return ['success' => false, 'message' => 'بيانات الدخول غير صحيحة'];
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ أثناء تسجيل الدخول'];
        }
    }

    /**
     * User logout
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'تسجيل خروج');
        }

        session_destroy();
        return ['success' => true, 'message' => 'تم تسجيل الخروج بنجاح'];
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            return false;
        }

        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            if ($inactive > SESSION_TIMEOUT) {
                $this->logout();
                return false;
            }
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    /**
     * Check permissions
     */
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        $role = $_SESSION['role'] ?? 'customer';

        $permissions = [
            'admin' => ['*'],
            'employee' => [
                'view_dashboard',
                'view_cars', 'add_car', 'edit_car',
                'view_customers', 'add_customer', 'edit_customer',
                'view_rentals', 'create_rental', 'edit_rental',
                'view_payments', 'add_payment',
                'view_reports'
            ],
            'customer' => [
                'view_own_rentals',
                'view_cars',
                'create_booking'
            ]
        ];

        $rolePermissions = $permissions[$role] ?? [];

        if (in_array('*', $rolePermissions)) {
            return true;
        }

        return in_array($permission, $rolePermissions);
    }

    /**
     * Get current user
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Change password
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        try {
            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($oldPassword, $user['password'])) {
                return ['success' => false, 'message' => 'كلمة المرور القديمة غير صحيحة'];
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $result = $updateStmt->execute([$hashedPassword, $userId]);

            if ($result) {
                $this->logActivity($userId, 'password_change', 'تغيير كلمة المرور');
                return ['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح'];
            }

            return ['success' => false, 'message' => 'فشل في تغيير كلمة المرور'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'حدث خطأ'];
        }
    }

    /**
     * Log activity
     */
    private function logActivity($userId, $action, $description) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO activity_log (user_id, action, description, ip_address, user_agent, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            
            $stmt->execute([
                $userId,
                $action,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            error_log('Activity log error: ' . $e->getMessage());
        }
    }

    /**
     * Log security event
     */
    private function logSecurityEvent($userId, $eventType, $description, $severity = 'medium') {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO security_logs (user_id, event_type, description, ip_address, user_agent, severity, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW())"
            );
            
            $stmt->execute([
                $userId,
                $eventType,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $severity
            ]);
        } catch (Exception $e) {
            error_log('Security log error: ' . $e->getMessage());
        }
    }
}
?>