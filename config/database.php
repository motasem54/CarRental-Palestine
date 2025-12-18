<?php
/**
 * Database Configuration
 * 🇵🇸 Palestine Car Rental System
 * إعدادات قاعدة البيانات مع كشف الأخطاء التفصيلي
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'leadership_rental');
define('DB_USER', 'leadership_user_new_2');
define('DB_PASS', 'z5e6-cukLI7yjmBw');
define('DB_CHARSET', 'utf8mb4');

// Enable detailed error reporting (disable in production)
define('DB_DEBUG_MODE', true);

/**
 * Database Connection Class
 * يدعم PDO و MySQLi مع كشف أخطاء تفصيلي
 */
class Database {
    private static $instance = null;
    private $connection;
    private $connectionType = null; // 'PDO' or 'MySQLi'
    private $errors = [];

    private function __construct() {
        // Try PDO first, then MySQLi as fallback
        if (!$this->connectPDO()) {
            $this->connectMySQLi();
        }
        
        if (DB_DEBUG_MODE && !empty($this->errors)) {
            $this->displayDiagnostics();
        }
    }

    /**
     * Try connecting with PDO
     */
    private function connectPDO() {
        try {
            // Check if PDO is available
            if (!extension_loaded('pdo') || !extension_loaded('pdo_mysql')) {
                $this->errors[] = [
                    'type' => 'PDO Extension',
                    'message' => 'PDO or PDO_MySQL extension is not loaded',
                    'solution' => 'Enable PDO in php.ini: extension=pdo_mysql'
                ];
                return false;
            }

            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->connectionType = 'PDO';
            
            if (DB_DEBUG_MODE) {
                $this->errors[] = [
                    'type' => 'Success',
                    'message' => '✅ Connected successfully using PDO',
                    'details' => 'Server: ' . DB_HOST . ' | Database: ' . DB_NAME
                ];
            }
            
            return true;
            
        } catch (PDOException $e) {
            $this->errors[] = [
                'type' => 'PDO Connection Error',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'solution' => $this->getSolution($e->getMessage())
            ];
            return false;
        }
    }

    /**
     * Fallback to MySQLi
     */
    private function connectMySQLi() {
        try {
            // Check if MySQLi is available
            if (!extension_loaded('mysqli')) {
                $this->errors[] = [
                    'type' => 'MySQLi Extension',
                    'message' => 'MySQLi extension is not loaded',
                    'solution' => 'Enable MySQLi in php.ini: extension=mysqli'
                ];
                $this->connectionFailed();
                return false;
            }

            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->connection->connect_error) {
                throw new Exception($this->connection->connect_error);
            }
            
            $this->connection->set_charset(DB_CHARSET);
            $this->connectionType = 'MySQLi';
            
            if (DB_DEBUG_MODE) {
                $this->errors[] = [
                    'type' => 'Success',
                    'message' => '✅ Connected successfully using MySQLi (fallback)',
                    'details' => 'Server: ' . DB_HOST . ' | Database: ' . DB_NAME
                ];
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->errors[] = [
                'type' => 'MySQLi Connection Error',
                'message' => $e->getMessage(),
                'solution' => $this->getSolution($e->getMessage())
            ];
            $this->connectionFailed();
            return false;
        }
    }

    /**
     * Get solution based on error message
     */
    private function getSolution($errorMessage) {
        $solutions = [
            'Access denied' => [
                '1. تحقق من اسم المستخدم وكلمة المرور',
                '2. تأكد من صلاحيات المستخدم في cPanel',
                '3. جرب: GRANT ALL PRIVILEGES ON ' . DB_NAME . '.* TO \'' . DB_USER . '\'@\'localhost\''
            ],
            'Unknown database' => [
                '1. تأكد من وجود قاعدة البيانات: ' . DB_NAME,
                '2. أنشئ قاعدة البيانات من cPanel',
                '3. تحقق من اسم قاعدة البيانات (Case Sensitive)'
            ],
            'Can\'t connect' => [
                '1. تحقق من أن MySQL يعمل',
                '2. جرب localhost أو 127.0.0.1',
                '3. تحقق من البورت (3306)',
                '4. تحقق من firewall'
            ],
            'No such file' => [
                '1. تحقق من مسار socket: /var/run/mysqld/mysqld.sock',
                '2. تأكد من أن MySQL يعمل: service mysql status',
                '3. أعد تشغيل MySQL: service mysql restart'
            ]
        ];

        foreach ($solutions as $keyword => $solution) {
            if (stripos($errorMessage, $keyword) !== false) {
                return $solution;
            }
        }

        return ['تحقق من إعدادات قاعدة البيانات'];
    }

    /**
     * Display detailed diagnostics
     */
    private function displayDiagnostics() {
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="ar">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>تشخيص الاتصال بقاعدة البيانات</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    padding: 20px;
                    direction: rtl;
                }
                .container {
                    max-width: 900px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 15px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    overflow: hidden;
                }
                .header {
                    background: linear-gradient(135deg, #FF5722 0%, #E64A19 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .header h1 {
                    font-size: 2rem;
                    margin-bottom: 10px;
                }
                .content {
                    padding: 30px;
                }
                .error-box, .success-box, .info-box {
                    margin-bottom: 20px;
                    padding: 20px;
                    border-radius: 10px;
                    border-right: 5px solid;
                }
                .error-box {
                    background: #ffebee;
                    border-color: #f44336;
                }
                .success-box {
                    background: #e8f5e9;
                    border-color: #4caf50;
                }
                .info-box {
                    background: #e3f2fd;
                    border-color: #2196f3;
                }
                .error-title, .success-title, .info-title {
                    font-weight: bold;
                    margin-bottom: 10px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                .error-title { color: #c62828; }
                .success-title { color: #2e7d32; }
                .info-title { color: #1565c0; }
                .icon {
                    font-size: 1.5rem;
                }
                .details {
                    background: #f5f5f5;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 10px 0;
                    font-family: 'Courier New', monospace;
                    font-size: 0.9rem;
                }
                .solution {
                    margin-top: 15px;
                }
                .solution-title {
                    font-weight: bold;
                    color: #FF5722;
                    margin-bottom: 10px;
                }
                .solution ul {
                    padding-right: 20px;
                }
                .solution li {
                    margin: 8px 0;
                    line-height: 1.6;
                }
                .system-info {
                    background: #f9f9f9;
                    padding: 20px;
                    border-radius: 10px;
                    margin-top: 20px;
                }
                .system-info h3 {
                    color: #FF5722;
                    margin-bottom: 15px;
                }
                .info-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 15px;
                }
                .info-item {
                    background: white;
                    padding: 15px;
                    border-radius: 5px;
                    border-right: 3px solid #FF5722;
                }
                .info-item strong {
                    display: block;
                    color: #666;
                    margin-bottom: 5px;
                    font-size: 0.9rem;
                }
                .info-item span {
                    color: #333;
                    font-weight: bold;
                }
                .status {
                    display: inline-block;
                    padding: 5px 15px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: bold;
                }
                .status-yes {
                    background: #4caf50;
                    color: white;
                }
                .status-no {
                    background: #f44336;
                    color: white;
                }
                .code-block {
                    background: #263238;
                    color: #aed581;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 10px 0;
                    font-family: 'Courier New', monospace;
                    font-size: 0.9rem;
                    overflow-x: auto;
                    direction: ltr;
                    text-align: left;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🔍 تشخيص الاتصال بقاعدة البيانات</h1>
                    <p>Palestine Car Rental System</p>
                </div>
                
                <div class="content">
                    <?php foreach ($this->errors as $error): ?>
                        <?php if ($error['type'] === 'Success'): ?>
                            <div class="success-box">
                                <div class="success-title">
                                    <span class="icon">✅</span>
                                    <?php echo $error['message']; ?>
                                </div>
                                <?php if (isset($error['details'])): ?>
                                    <div class="details"><?php echo $error['details']; ?></div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="error-box">
                                <div class="error-title">
                                    <span class="icon">❌</span>
                                    <?php echo $error['type']; ?>
                                </div>
                                <div class="details">
                                    <strong>الخطأ:</strong><br>
                                    <?php echo htmlspecialchars($error['message']); ?>
                                    <?php if (isset($error['code'])): ?>
                                        <br><strong>الكود:</strong> <?php echo $error['code']; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (isset($error['solution'])): ?>
                                    <div class="solution">
                                        <div class="solution-title">💡 الحلول المقترحة:</div>
                                        <ul>
                                            <?php foreach ((array)$error['solution'] as $sol): ?>
                                                <li><?php echo $sol; ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <!-- System Information -->
                    <div class="system-info">
                        <h3>📊 معلومات النظام</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <strong>PHP Version:</strong>
                                <span><?php echo phpversion(); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>PDO Support:</strong>
                                <span class="status <?php echo extension_loaded('pdo') ? 'status-yes' : 'status-no'; ?>">
                                    <?php echo extension_loaded('pdo') ? 'نعم' : 'لا'; ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <strong>PDO MySQL:</strong>
                                <span class="status <?php echo extension_loaded('pdo_mysql') ? 'status-yes' : 'status-no'; ?>">
                                    <?php echo extension_loaded('pdo_mysql') ? 'نعم' : 'لا'; ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <strong>MySQLi Support:</strong>
                                <span class="status <?php echo extension_loaded('mysqli') ? 'status-yes' : 'status-no'; ?>">
                                    <?php echo extension_loaded('mysqli') ? 'نعم' : 'لا'; ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <strong>Server:</strong>
                                <span><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Operating System:</strong>
                                <span><?php echo PHP_OS; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Connection Settings -->
                    <div class="info-box" style="margin-top: 20px;">
                        <div class="info-title">
                            <span class="icon">⚙️</span>
                            إعدادات الاتصال الحالية
                        </div>
                        <div class="code-block">
Host: <?php echo DB_HOST; ?>

Database: <?php echo DB_NAME; ?>

Username: <?php echo DB_USER; ?>

Password: <?php echo str_repeat('*', strlen(DB_PASS)); ?> (مخفية)

Charset: <?php echo DB_CHARSET; ?>
                        </div>
                    </div>

                    <!-- Quick Fix Commands -->
                    <div class="info-box" style="margin-top: 20px;">
                        <div class="info-title">
                            <span class="icon">🛠️</span>
                            أوامر الإصلاح السريع
                        </div>
                        <p><strong>لتفعيل PDO في php.ini:</strong></p>
                        <div class="code-block">
extension=pdo
extension=pdo_mysql
extension=mysqli
                        </div>
                        
                        <p style="margin-top: 15px;"><strong>للتحقق من MySQL:</strong></p>
                        <div class="code-block">
# Check MySQL status
service mysql status

# Restart MySQL
service mysql restart

# Test connection
mysql -u <?php echo DB_USER; ?> -p <?php echo DB_NAME; ?>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Connection failed - show final error
     */
    private function connectionFailed() {
        if (!DB_DEBUG_MODE) {
            die('حدث خطأ في الاتصال بقاعدة البيانات. يرجى التواصل مع المسؤول.');
        }
        // Diagnostics will be shown by displayDiagnostics()
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function getConnectionType() {
        return $this->connectionType;
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>