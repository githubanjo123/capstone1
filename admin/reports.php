<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Include database configuration
require_once '../db.php';

// Get comprehensive exam statistics
$exam_stats = $pdo->query("
    SELECT 
        COUNT(DISTINCT e.exam_id) as total_exams,
        COUNT(DISTINCT ea.attempt_id) as total_attempts,
        COUNT(DISTINCT ea.student_id) as unique_students,
        COUNT(DISTINCT e.created_by) as active_faculty,
        AVG(ea.score) as avg_score,
        MAX(ea.score) as highest_score,
        MIN(ea.score) as lowest_score
    FROM exams e
    LEFT JOIN exam_attempts ea ON e.exam_id = ea.exam_id
")->fetch();

// Get exam statistics by subject
$subject_stats = $pdo->query("
    SELECT 
        s.subject_name,
        s.subject_code,
        COUNT(DISTINCT e.exam_id) as exam_count,
        COUNT(DISTINCT ea.attempt_id) as attempt_count,
        AVG(ea.score) as avg_score,
        u.user_name as faculty_name
    FROM subjects s
    LEFT JOIN exams e ON s.subject_id = e.subject_id
    LEFT JOIN exam_attempts ea ON e.exam_id = ea.exam_id
    LEFT JOIN users u ON s.faculty_id = u.user_id
    GROUP BY s.subject_id
    ORDER BY exam_count DESC, s.subject_name
")->fetchAll();

// Get recent exam results
$recent_results = $pdo->query("
    SELECT 
        e.title as exam_title,
        s.subject_name,
        us.user_name as student_name,
        ea.score,
        ea.total_marks,
        ea.submitted_at,
        uf.user_name as faculty_name
    FROM exam_attempts ea
    JOIN exams e ON ea.exam_id = e.exam_id
    JOIN subjects s ON e.subject_id = s.subject_id
    JOIN users us ON ea.student_id = us.user_id
    LEFT JOIN users uf ON e.created_by = uf.user_id
    WHERE ea.status = 'submitted'
    ORDER BY ea.submitted_at DESC
    LIMIT 20
")->fetchAll();

// Get exam performance by year
$yearly_performance = $pdo->query("
    SELECT 
        SUBSTRING(us.user_id, 1, 4) as year,
        COUNT(DISTINCT ea.attempt_id) as total_attempts,
        AVG(ea.score) as avg_score,
        COUNT(DISTINCT ea.student_id) as students_attempted,
        MAX(ea.score) as highest_score
    FROM exam_attempts ea
    JOIN users us ON ea.student_id = us.user_id
    WHERE ea.status = 'submitted'
    GROUP BY SUBSTRING(us.user_id, 1, 4)
    ORDER BY year DESC
")->fetchAll();

// Get top performing students
$top_students = $pdo->query("
    SELECT 
        us.user_name as student_name,
        us.user_id as student_id,
        COUNT(ea.attempt_id) as exams_taken,
        AVG(ea.score) as avg_score,
        MAX(ea.score) as highest_score,
        SUM(ea.score) as total_score
    FROM exam_attempts ea
    JOIN users us ON ea.student_id = us.user_id
    WHERE ea.status = 'submitted'
    GROUP BY ea.student_id
    HAVING exams_taken >= 1
    ORDER BY avg_score DESC
    LIMIT 10
")->fetchAll();

// Get faculty performance statistics
$faculty_stats = $pdo->query("
    SELECT 
        uf.user_name as faculty_name,
        COUNT(DISTINCT e.exam_id) as exams_created,
        COUNT(DISTINCT ea.attempt_id) as total_attempts,
        AVG(ea.score) as avg_score,
        COUNT(DISTINCT s.subject_id) as subjects_assigned
    FROM users uf
    LEFT JOIN exams e ON uf.user_id = e.created_by
    LEFT JOIN exam_attempts ea ON e.exam_id = ea.exam_id
    LEFT JOIN subjects s ON uf.user_id = s.faculty_id
    WHERE uf.user_type = 'faculty'
    GROUP BY uf.user_id
    ORDER BY exams_created DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Results - Admin Dashboard</title>
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
        
        .content-area {
            padding: 20px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .btn-primary { background: var(--primary-gradient); border: none; }
        .btn-success { background: var(--success-gradient); border: none; }
        .btn-danger { background: var(--danger-gradient); border: none; }
        .btn-warning { background: var(--warning-gradient); border: none; }
        .btn-info { background: var(--info-gradient); border: none; }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin: 0 auto 15px;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
        }
        
        .stats-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #666;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 0.5em 0.75em;
        }
        
        .print-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .print-section {
                background: white;
                box-shadow: none;
                border: none;
                margin-bottom: 30px;
                page-break-inside: avoid;
            }
            
            body {
                background: white !important;
                font-size: 12px;
            }
            
            .table {
                font-size: 11px;
            }
        }
        
        .performance-meter {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .performance-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .grade-a { background: var(--success-gradient); }
        .grade-b { background: var(--info-gradient); }
        .grade-c { background: var(--warning-gradient); }
        .grade-d { background: var(--danger-gradient); }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark no-print">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-user-shield"></i> Admin Dashboard
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar no-print">
                <nav class="nav flex-column">
                    <a class="nav-link" href="index.php">
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
                    <a class="nav-link active" href="reports.php">
                        <i class="fas fa-chart-bar"></i> Reports & Results
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content-area">
                <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                    <h2>Reports & Analytics</h2>
                    <div class="d-flex gap-2">
                        <button class="btn btn-success" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                        <button class="btn btn-info" onclick="exportToCSV()">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                    </div>
                </div>

                <!-- Overview Statistics -->
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon" style="background: var(--primary-gradient);">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stats-number"><?php echo $exam_stats['total_exams'] ?? 0; ?></div>
                            <div class="stats-label">Total Exams</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon" style="background: var(--success-gradient);">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="stats-number"><?php echo $exam_stats['total_attempts'] ?? 0; ?></div>
                            <div class="stats-label">Total Attempts</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon" style="background: var(--info-gradient);">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stats-number"><?php echo $exam_stats['unique_students'] ?? 0; ?></div>
                            <div class="stats-label">Active Students</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon" style="background: var(--warning-gradient);">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="stats-number"><?php echo $exam_stats['avg_score'] ? round($exam_stats['avg_score']) : 0; ?>%</div>
                            <div class="stats-label">Average Score</div>
                        </div>
                    </div>
                </div>

                <!-- Subject Performance Report -->
                <div class="print-section">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-chart-line"></i> Subject Performance Report</h5>
                            <span class="badge bg-primary"><?php echo count($subject_stats); ?> subjects</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Faculty</th>
                                            <th>Exams</th>
                                            <th>Attempts</th>
                                            <th>Avg Score</th>
                                            <th>Performance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subject_stats as $subject): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($subject['subject_name']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($subject['subject_code']); ?></small>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($subject['faculty_name'] ?? 'Not assigned'); ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $subject['exam_count']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $subject['attempt_count']; ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($subject['avg_score']): ?>
                                                        <strong><?php echo round($subject['avg_score']); ?>%</strong>
                                                    <?php else: ?>
                                                        <span class="text-muted">No attempts</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($subject['avg_score']): ?>
                                                        <div class="performance-meter">
                                                            <div class="performance-fill 
                                                                <?php 
                                                                    $score = $subject['avg_score'];
                                                                    if ($score >= 90) echo 'grade-a';
                                                                    elseif ($score >= 80) echo 'grade-b';
                                                                    elseif ($score >= 70) echo 'grade-c';
                                                                    else echo 'grade-d';
                                                                ?>" 
                                                                style="width: <?php echo min($score, 100); ?>%">
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Results -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="print-section">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-clock"></i> Recent Exam Results</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Student</th>
                                                    <th>Exam</th>
                                                    <th>Subject</th>
                                                    <th>Score</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_results as $result): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($result['student_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($result['exam_title']); ?></td>
                                                        <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                $percentage = ($result['score'] / $result['total_marks']) * 100;
                                                                if ($percentage >= 90) echo 'success';
                                                                elseif ($percentage >= 80) echo 'primary';
                                                                elseif ($percentage >= 70) echo 'warning';
                                                                else echo 'danger';
                                                            ?>">
                                                                <?php echo $result['score']; ?>/<?php echo $result['total_marks']; ?>
                                                                (<?php echo round($percentage); ?>%)
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('M j, Y', strtotime($result['submitted_at'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Top Students -->
                        <div class="print-section">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-trophy"></i> Top Performers</h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach (array_slice($top_students, 0, 5) as $index => $student): ?>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="me-3">
                                                <span class="badge bg-<?php 
                                                    if ($index === 0) echo 'warning';
                                                    elseif ($index === 1) echo 'secondary';
                                                    elseif ($index === 2) echo 'info';
                                                    else echo 'primary';
                                                ?> rounded-pill">
                                                    #<?php echo $index + 1; ?>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold"><?php echo htmlspecialchars($student['student_name']); ?></div>
                                                <small class="text-muted">
                                                    <?php echo round($student['avg_score']); ?>% avg • 
                                                    <?php echo $student['exams_taken']; ?> exams
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Yearly Performance -->
                        <div class="print-section">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-calendar"></i> Performance by Year</h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($yearly_performance as $year_data): ?>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between">
                                                <strong>Class of <?php echo $year_data['year']; ?></strong>
                                                <span><?php echo round($year_data['avg_score']); ?>%</span>
                                            </div>
                                            <div class="performance-meter mt-2">
                                                <div class="performance-fill grade-b" 
                                                     style="width: <?php echo min($year_data['avg_score'], 100); ?>%">
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo $year_data['students_attempted']; ?> students • 
                                                <?php echo $year_data['total_attempts']; ?> attempts
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Faculty Performance Report -->
                <div class="print-section">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chalkboard-teacher"></i> Faculty Performance Report</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Faculty</th>
                                            <th>Subjects Assigned</th>
                                            <th>Exams Created</th>
                                            <th>Total Attempts</th>
                                            <th>Avg Student Score</th>
                                            <th>Performance Rating</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($faculty_stats as $faculty): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($faculty['faculty_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $faculty['subjects_assigned']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success"><?php echo $faculty['exams_created']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $faculty['total_attempts']; ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($faculty['avg_score']): ?>
                                                        <strong><?php echo round($faculty['avg_score']); ?>%</strong>
                                                    <?php else: ?>
                                                        <span class="text-muted">No attempts</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $rating = 'Not Available';
                                                    $badgeClass = 'secondary';
                                                    if ($faculty['avg_score']) {
                                                        if ($faculty['avg_score'] >= 90) {
                                                            $rating = 'Excellent';
                                                            $badgeClass = 'success';
                                                        } elseif ($faculty['avg_score'] >= 80) {
                                                            $rating = 'Very Good';
                                                            $badgeClass = 'primary';
                                                        } elseif ($faculty['avg_score'] >= 70) {
                                                            $rating = 'Good';
                                                            $badgeClass = 'warning';
                                                        } else {
                                                            $rating = 'Needs Improvement';
                                                            $badgeClass = 'danger';
                                                        }
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo $rating; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Print Footer -->
                <div class="text-center mt-4" style="display: none;">
                    <small class="text-muted">
                        Generated on <?php echo date('F j, Y \a\t g:i A'); ?> | 
                        Exam Management System | 
                        Total Records: <?php echo $exam_stats['total_exams']; ?> exams, <?php echo $exam_stats['total_attempts']; ?> attempts
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportToCSV() {
            // Create CSV content
            let csv = 'Subject,Faculty,Exams,Attempts,Average Score\n';
            
            <?php foreach ($subject_stats as $subject): ?>
                csv += '<?php echo addslashes($subject['subject_name']); ?>,' +
                       '<?php echo addslashes($subject['faculty_name'] ?? 'Not assigned'); ?>,' +
                       '<?php echo $subject['exam_count']; ?>,' +
                       '<?php echo $subject['attempt_count']; ?>,' +
                       '<?php echo round($subject['avg_score'] ?? 0); ?>\n';
            <?php endforeach; ?>
            
            // Create and download file
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'exam_report_' + new Date().toISOString().slice(0, 10) + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>