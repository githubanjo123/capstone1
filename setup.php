<?php
/**
 * Setup Script for Faculty Dashboard Exam System
 * This script helps initialize the database and check system requirements
 */

// Check if setup is already completed
if (file_exists('.setup_complete')) {
    die('Setup already completed. Delete .setup_complete file to run setup again.');
}

$errors = [];
$success = [];

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    $errors[] = 'PHP 7.4 or higher is required. Current version: ' . PHP_VERSION;
} else {
    $success[] = 'PHP version: ' . PHP_VERSION . ' ✓';
}

// Check required PHP extensions
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'session'];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $errors[] = "PHP extension '$ext' is required but not loaded";
    } else {
        $success[] = "PHP extension '$ext' is loaded ✓";
    }
}

// Check if database.sql exists
if (!file_exists('database.sql')) {
    $errors[] = 'database.sql file not found';
} else {
    $success[] = 'database.sql file found ✓';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? 'exam_system';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    
    try {
        // Test database connection
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
        $pdo->exec("USE `$db_name`");
        
        // Read and execute SQL file
        $sql = file_get_contents('database.sql');
        $pdo->exec($sql);
        
        // Update db.php with new credentials
        $db_config = "<?php
// Database configuration
\$host = '$db_host';
\$dbname = '$db_name';
\$username = '$db_user';
\$password = '$db_pass';

try {
    \$pdo = new PDO(\"mysql:host=\$host;dbname=\$dbname;charset=utf8mb4\", \$username, \$password);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException \$e) {
    error_log(\"Database connection failed: \" . \$e->getMessage());
    die(\"Database connection failed. Please check your configuration.\");
}
?>";
        
        file_put_contents('db.php', $db_config);
        
        // Create setup complete marker
        file_put_contents('.setup_complete', date('Y-m-d H:i:s'));
        
        $setup_success = true;
        
    } catch (Exception $e) {
        $setup_error = 'Database setup failed: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Faculty Dashboard Exam System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .setup-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .setup-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin-bottom: 20px;
        }
        .status-item {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .status-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn-setup {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-card">
            <div class="text-center mb-4">
                <i class="fas fa-cog fa-3x text-primary mb-3"></i>
                <h2>Faculty Dashboard Setup</h2>
                <p class="text-muted">Configure your exam management system</p>
            </div>
            
            <?php if (isset($setup_success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Setup completed successfully!
                    <br><br>
                    <strong>Demo Login Credentials:</strong><br>
                    Username: FAC001<br>
                    Password: password123
                    <br><br>
                    <a href="index.php" class="btn btn-primary">Go to Dashboard</a>
                </div>
            <?php else: ?>
                
                <h4>System Requirements Check</h4>
                
                <?php foreach ($success as $msg): ?>
                    <div class="status-item status-success">
                        <i class="fas fa-check"></i> <?php echo $msg; ?>
                    </div>
                <?php endforeach; ?>
                
                <?php foreach ($errors as $msg): ?>
                    <div class="status-item status-error">
                        <i class="fas fa-times"></i> <?php echo $msg; ?>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($errors)): ?>
                    <div class="alert alert-success mt-3">
                        <i class="fas fa-check-circle"></i> All requirements met! You can proceed with database setup.
                    </div>
                    
                    <hr>
                    
                    <h4>Database Configuration</h4>
                    
                    <?php if (isset($setup_error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $setup_error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="db_host" class="form-label">Database Host</label>
                                    <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="db_name" class="form-label">Database Name</label>
                                    <input type="text" class="form-control" id="db_name" name="db_name" value="exam_system" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="db_user" class="form-label">Database Username</label>
                                    <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="db_pass" class="form-label">Database Password</label>
                                    <input type="password" class="form-control" id="db_pass" name="db_pass" placeholder="Leave blank if no password">
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-setup">
                                <i class="fas fa-database"></i> Setup Database
                            </button>
                        </div>
                    </form>
                    
                <?php else: ?>
                    <div class="alert alert-danger mt-3">
                        <i class="fas fa-exclamation-triangle"></i> Please fix the above issues before proceeding.
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>
        
        <div class="setup-card">
            <h5>What this setup does:</h5>
            <ul>
                <li>Creates the exam_system database</li>
                <li>Sets up all required tables and relationships</li>
                <li>Inserts sample data for testing</li>
                <li>Configures database connection</li>
                <li>Creates demo users for immediate testing</li>
            </ul>
            
            <h5>After setup:</h5>
            <ul>
                <li>Access the faculty dashboard</li>
                <li>Login with demo credentials</li>
                <li>Create and manage exams</li>
                <li>Run the test suite to verify functionality</li>
            </ul>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>