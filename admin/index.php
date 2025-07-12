<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Include database configuration
require_once '../db.php';

// Get admin information
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['user_name'] ?? 'Admin';

// Get dashboard statistics
try {
    // Count users by type
    $stats = [];
    $user_types = ['admin', 'faculty', 'student'];
    foreach ($user_types as $type) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = ?");
        $stmt->execute([$type]);
        $stats[$type] = $stmt->fetch()['count'];
    }
    
    // Count subjects
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM subjects");
    $stats['subjects'] = $stmt->fetch()['count'];
    
    // Count exams
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM exams");
    $stats['exams'] = $stmt->fetch()['count'];
    
    // Count exam attempts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM exam_attempts");
    $stats['attempts'] = $stmt->fetch()['count'];
    
    // Recent activities
    $stmt = $pdo->query("
        SELECT 'exam' as type, title as description, created_at, u.user_name as created_by
        FROM exams e
        JOIN users u ON e.created_by = u.user_id
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $recent_activities = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    $stats = ['admin' => 0, 'faculty' => 0, 'student' => 0, 'subjects' => 0, 'exams' => 0, 'attempts' => 0];
    $recent_activities = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Exam System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
            --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: var(--primary-gradient);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .sidebar {
            background: white;
            min-height: calc(100vh - 76px);
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            padding: 0;
        }
        
        .sidebar .nav-link {
            color: #333;
            padding: 15px 20px;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: #f8f9fa;
            border-left-color: #667eea;
            color: #667eea;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .recent-activity {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .content-area {
            padding: 20px;
        }
        
        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .btn-action {
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 600;
            margin: 5px;
            transition: all 0.3s;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary { background: var(--primary-gradient); border: none; }
        .btn-success { background: var(--success-gradient); border: none; }
        .btn-danger { background: var(--danger-gradient); border: none; }
        .btn-warning { background: var(--warning-gradient); border: none; }
        .btn-info { background: var(--info-gradient); border: none; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-user-shield"></i> Admin Dashboard
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users"></i> User Management
                    </a>
                    <a class="nav-link" href="subjects.php">
                        <i class="fas fa-book"></i> Subject Management
                    </a>
                    <a class="nav-link" href="assignments.php">
                        <i class="fas fa-user-tie"></i> Faculty Assignments
                    </a>
                    <a class="nav-link" href="students.php">
                        <i class="fas fa-user-graduate"></i> Student Management
                    </a>
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar"></i> Reports & Results
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content-area">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Dashboard Overview</h2>
                    <small class="text-muted">Last updated: <?php echo date('Y-m-d H:i:s'); ?></small>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: var(--primary-gradient);">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['admin']; ?></div>
                            <div class="stat-label">Administrators</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: var(--success-gradient);">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['faculty']; ?></div>
                            <div class="stat-label">Faculty Members</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: var(--info-gradient);">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['student']; ?></div>
                            <div class="stat-label">Students</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: var(--warning-gradient);">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['subjects']; ?></div>
                            <div class="stat-label">Subjects</div>
                        </div>
                    </div>
                </div>

                <!-- Second Row of Stats -->
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: var(--danger-gradient);">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['exams']; ?></div>
                            <div class="stat-label">Total Exams</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: var(--primary-gradient);">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['attempts']; ?></div>
                            <div class="stat-label">Exam Attempts</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: var(--success-gradient);">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['attempts'] > 0 ? round(($stats['attempts'] / $stats['exams']) * 100) : 0; ?>%</div>
                            <div class="stat-label">Participation Rate</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: var(--info-gradient);">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-number"><?php echo date('j'); ?></div>
                            <div class="stat-label">Today's Date</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions and Recent Activity -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="quick-actions">
                            <h5 class="mb-3">
                                <i class="fas fa-bolt"></i> Quick Actions
                            </h5>
                            <div class="d-flex flex-wrap">
                                <a href="users.php?action=add" class="btn btn-primary btn-action">
                                    <i class="fas fa-user-plus"></i> Add User
                                </a>
                                <a href="subjects.php?action=add" class="btn btn-success btn-action">
                                    <i class="fas fa-book-open"></i> Add Subject
                                </a>
                                <a href="assignments.php" class="btn btn-warning btn-action">
                                    <i class="fas fa-user-tie"></i> Assign Faculty
                                </a>
                                <a href="reports.php" class="btn btn-info btn-action">
                                    <i class="fas fa-chart-line"></i> View Reports
                                </a>
                                <a href="students.php" class="btn btn-danger btn-action">
                                    <i class="fas fa-users"></i> Manage Students
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="recent-activity">
                            <h5 class="mb-3">
                                <i class="fas fa-history"></i> Recent Activity
                            </h5>
                            <?php if (empty($recent_activities)): ?>
                                <p class="text-muted">No recent activity found.</p>
                            <?php else: ?>
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">New <?php echo ucfirst($activity['type']); ?> Created</div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($activity['description']); ?></div>
                                            <div class="text-muted small">
                                                by <?php echo htmlspecialchars($activity['created_by']); ?> • 
                                                <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>