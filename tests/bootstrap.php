<?php
/**
 * PHPUnit Bootstrap File
 * Sets up the testing environment for Faculty Dashboard backend tests
 */

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone for consistent testing
date_default_timezone_set('UTC');

// Define test environment constants
define('TESTING', true);
define('TEST_DB_PATH', ':memory:');

// Autoload composer dependencies (if using composer)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Set up test database configuration
function setupTestDatabase() {
    try {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception('Failed to create test database: ' . $e->getMessage());
    }
}

// Mock global functions for testing
if (!function_exists('header')) {
    function header($string, $replace = true, $response_code = null) {
        // Mock header function for testing
        global $test_headers;
        if (!isset($test_headers)) {
            $test_headers = [];
        }
        $test_headers[] = $string;
    }
}

// Test helper functions
class TestHelpers 
{
    /**
     * Create a test PDO connection
     */
    public static function createTestPDO() 
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    }
    
    /**
     * Create test tables
     */
    public static function createTestTables(PDO $pdo) 
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS users (
                user_id INTEGER PRIMARY KEY,
                school_id VARCHAR(50) NOT NULL UNIQUE,
                full_name VARCHAR(100) NOT NULL,
                password VARCHAR(255) DEFAULT 'password123',
                role TEXT NOT NULL,
                year_level INTEGER,
                section VARCHAR(10),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS subjects (
                subject_id INTEGER PRIMARY KEY,
                course_code VARCHAR(20) NOT NULL UNIQUE,
                descriptive_title VARCHAR(100) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS subject_assignments (
                assignment_id INTEGER PRIMARY KEY,
                faculty_id INTEGER NOT NULL,
                subject_id INTEGER NOT NULL,
                year_level INTEGER NOT NULL,
                section VARCHAR(10) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (faculty_id) REFERENCES users(user_id),
                FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
            );

            CREATE TABLE IF NOT EXISTS exams (
                exam_id INTEGER PRIMARY KEY,
                title VARCHAR(200) NOT NULL,
                instructions TEXT,
                subject_id INTEGER NOT NULL,
                year_level INTEGER NOT NULL,
                section VARCHAR(10) NOT NULL,
                created_by INTEGER NOT NULL,
                status TEXT DEFAULT 'active',
                time_limit INTEGER DEFAULT 60,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
                FOREIGN KEY (created_by) REFERENCES users(user_id)
            );

            CREATE TABLE IF NOT EXISTS questions (
                question_id INTEGER PRIMARY KEY,
                exam_id INTEGER NOT NULL,
                question_text TEXT NOT NULL,
                question_type TEXT NOT NULL,
                option_a VARCHAR(500),
                option_b VARCHAR(500),
                option_c VARCHAR(500),
                option_d VARCHAR(500),
                correct_answer VARCHAR(500) NOT NULL,
                points INTEGER DEFAULT 1,
                question_order INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (exam_id) REFERENCES exams(exam_id)
            );
        ";
        
        $pdo->exec($sql);
    }
    
    /**
     * Insert test data
     */
    public static function insertTestData(PDO $pdo) 
    {
        // Insert test users
        $pdo->exec("
            INSERT INTO users (user_id, school_id, full_name, role) VALUES 
            (1, 'ADMIN001', 'Admin User', 'admin'),
            (2, 'FAC001', 'Dr. John Smith', 'faculty'),
            (3, 'FAC002', 'Dr. Jane Doe', 'faculty'),
            (4, '2020-001', 'Student One', 'student'),
            (5, '2020-002', 'Student Two', 'student')
        ");

        // Insert test subjects
        $pdo->exec("
            INSERT INTO subjects (subject_id, course_code, descriptive_title) VALUES 
            (1, 'MATH101', 'Mathematics'),
            (2, 'PHYS101', 'Physics'),
            (3, 'CHEM101', 'Chemistry'),
            (4, 'ENG101', 'English')
        ");

        // Insert test subject assignments
        $pdo->exec("
            INSERT INTO subject_assignments (faculty_id, subject_id, year_level, section) VALUES 
            (2, 1, 3, 'A'),
            (2, 2, 2, 'B'),
            (3, 3, 1, 'A'),
            (3, 4, 2, 'A')
        ");
    }
    
    /**
     * Clean up test data
     */
    public static function cleanupTestData(PDO $pdo) 
    {
        $tables = ['questions', 'exams', 'subject_assignments', 'subjects', 'users'];
        foreach ($tables as $table) {
            $pdo->exec("DELETE FROM $table");
        }
    }
    
    /**
     * Assert valid API response format
     */
    public static function assertValidApiResponse(array $response) 
    {
        if (!isset($response['success']) || !is_bool($response['success'])) {
            throw new InvalidArgumentException('Response must have boolean "success" field');
        }
        
        if (!isset($response['message']) || !is_string($response['message'])) {
            throw new InvalidArgumentException('Response must have string "message" field');
        }
    }
    
    /**
     * Create mock $_POST data
     */
    public static function mockPostData(array $data) 
    {
        global $_POST;
        $_POST = $data;
    }
    
    /**
     * Create mock $_GET data
     */
    public static function mockGetData(array $data) 
    {
        global $_GET;
        $_GET = $data;
    }
    
    /**
     * Capture output from included PHP file
     */
    public static function captureApiOutput($filePath, $postData = [], $getData = []) 
    {
        // Mock request data
        if (!empty($postData)) {
            self::mockPostData($postData);
        }
        if (!empty($getData)) {
            self::mockGetData($getData);
        }
        
        // Capture output
        ob_start();
        include $filePath;
        $output = ob_get_clean();
        
        // Try to decode JSON response
        $decoded = json_decode($output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        
        return $output;
    }
}

// Set up global test PDO connection
$GLOBALS['test_pdo'] = TestHelpers::createTestPDO();

// Global cleanup function
function cleanup_test_environment() 
{
    // Reset superglobals
    $_GET = [];
    $_POST = [];
    $_SESSION = [];
    $_COOKIE = [];
    
    // Reset global test headers
    $GLOBALS['test_headers'] = [];
    
    // Clean up PDO connection
    if (isset($GLOBALS['test_pdo'])) {
        $GLOBALS['test_pdo'] = null;
    }
}

// Register shutdown function for cleanup
register_shutdown_function('cleanup_test_environment');

// Suppress output during tests
if (!defined('PHPUNIT_RUNNING')) {
    define('PHPUNIT_RUNNING', true);
}

echo "PHPUnit Bootstrap completed - Faculty Dashboard backend test environment ready\n";
?>