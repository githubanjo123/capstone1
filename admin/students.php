<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Include database configuration
require_once '../db.php';

// Get students grouped by year and section
$students_query = "
    SELECT s.*, 
           COUNT(DISTINCT en.subject_id) as enrolled_subjects,
           COUNT(DISTINCT ea.attempt_id) as exam_attempts
    FROM users s
    LEFT JOIN enrollments en ON s.user_id = en.student_id
    LEFT JOIN exam_attempts ea ON s.user_id = ea.student_id
    WHERE s.user_type = 'student'
    GROUP BY s.user_id
    ORDER BY s.user_id
";

$students = $pdo->query($students_query)->fetchAll();

// Group students by year (extracted from user_id pattern like 2020-001)
$students_by_year = [];
$years = [];
foreach ($students as $student) {
    $year = substr($student['user_id'], 0, 4);
    if (!isset($students_by_year[$year])) {
        $students_by_year[$year] = [];
        $years[] = $year;
    }
    $students_by_year[$year][] = $student;
}

// Get enrollment statistics
$enrollment_stats = $pdo->query("
    SELECT 
        SUBSTRING(s.user_id, 1, 4) as year,
        COUNT(DISTINCT s.user_id) as total_students,
        COUNT(DISTINCT en.subject_id) as total_enrollments,
        COUNT(DISTINCT ea.attempt_id) as total_attempts
    FROM users s
    LEFT JOIN enrollments en ON s.user_id = en.student_id
    LEFT JOIN exam_attempts ea ON s.user_id = ea.student_id
    WHERE s.user_type = 'student'
    GROUP BY SUBSTRING(s.user_id, 1, 4)
    ORDER BY year DESC
")->fetchAll();

// Get subjects for enrollment
$subjects = $pdo->query("
    SELECT subject_id, subject_name, subject_code, year, section, 
           u.user_name as faculty_name
    FROM subjects s
    LEFT JOIN users u ON s.faculty_id = u.user_id
    ORDER BY subject_name
")->fetchAll();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'enroll') {
            $student_id = $_POST['student_id'] ?? '';
            $subject_ids = $_POST['subject_ids'] ?? [];
            
            if (empty($student_id) || empty($subject_ids)) {
                throw new Exception('Student and subjects are required');
            }
            
            // Enroll student in selected subjects
            $enrolled_count = 0;
            foreach ($subject_ids as $subject_id) {
                // Check if already enrolled
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND subject_id = ?");
                $stmt->execute([$student_id, $subject_id]);
                
                if ($stmt->fetchColumn() == 0) {
                    $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, subject_id) VALUES (?, ?)");
                    $stmt->execute([$student_id, $subject_id]);
                    $enrolled_count++;
                }
            }
            
            $message = "Student enrolled in $enrolled_count subjects successfully!";
            
        } elseif ($action === 'unenroll') {
            $student_id = $_POST['student_id'] ?? '';
            $subject_id = $_POST['subject_id'] ?? '';
            
            if (empty($student_id) || empty($subject_id)) {
                throw new Exception('Student and subject are required');
            }
            
            $stmt = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ? AND subject_id = ?");
            $stmt->execute([$student_id, $subject_id]);
            
            $message = 'Student unenrolled successfully!';
        }
        
        // Refresh data after changes
        $students = $pdo->query($students_query)->fetchAll();
        $students_by_year = [];
        foreach ($students as $student) {
            $year = substr($student['user_id'], 0, 4);
            if (!isset($students_by_year[$year])) {
                $students_by_year[$year] = [];
            }
            $students_by_year[$year][] = $student;
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Admin Dashboard</title>
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
        }
        
        .btn-primary { background: var(--primary-gradient); border: none; }
        .btn-success { background: var(--success-gradient); border: none; }
        .btn-danger { background: var(--danger-gradient); border: none; }
        .btn-warning { background: var(--warning-gradient); border: none; }
        .btn-info { background: var(--info-gradient); border: none; }
        
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .year-tab {
            border-radius: 10px 10px 0 0;
            background: white;
            border: 1px solid #dee2e6;
            margin-right: 5px;
        }
        
        .year-tab.active {
            background: var(--primary-gradient);
            color: white;
            border-color: var(--primary-gradient);
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-gradient);
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 0.5em 0.75em;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #666;
        }
        
        .tab-content {
            background: white;
            border-radius: 0 15px 15px 15px;
            padding: 20px;
        }
        
        .enrollment-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-enrolled {
            background: #d4edda;
            color: #155724;
        }
        
        .status-not-enrolled {
            background: #f8d7da;
            color: #721c24;
        }
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
            <div class="col-md-3 col-lg-2 sidebar">
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
                    <a class="nav-link active" href="students.php">
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
                    <h2>Student Management</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#enrollModal">
                        <i class="fas fa-plus"></i> Enroll Student
                    </button>
                </div>

                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <?php foreach ($enrollment_stats as $stat): ?>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $stat['total_students']; ?></div>
                                <div class="text-muted">Class of <?php echo $stat['year']; ?></div>
                                <small class="text-muted">
                                    <?php echo $stat['total_enrollments']; ?> enrollments • 
                                    <?php echo $stat['total_attempts']; ?> attempts
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Students by Year Tabs -->
                <div class="mb-4">
                    <ul class="nav nav-tabs" id="yearTabs" role="tablist">
                        <?php foreach ($years as $index => $year): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                                        id="year-<?php echo $year; ?>-tab" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#year-<?php echo $year; ?>" 
                                        type="button" 
                                        role="tab">
                                    <i class="fas fa-users"></i> Class of <?php echo $year; ?>
                                    <span class="badge bg-primary ms-2"><?php echo count($students_by_year[$year]); ?></span>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Tab Content -->
                <div class="tab-content" id="yearTabsContent">
                    <?php foreach ($years as $index => $year): ?>
                        <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" 
                             id="year-<?php echo $year; ?>" 
                             role="tabpanel">
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Class of <?php echo $year; ?> Students</h5>
                                <span class="badge bg-info"><?php echo count($students_by_year[$year]); ?> students</span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Student ID</th>
                                            <th>Email</th>
                                            <th>Enrolled Subjects</th>
                                            <th>Exam Attempts</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students_by_year[$year] as $student): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="student-avatar me-3">
                                                            <?php echo strtoupper(substr($student['user_name'], 0, 2)); ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($student['user_name']); ?></div>
                                                            <small class="text-muted">Student</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($student['user_id']); ?></code>
                                                </td>
                                                <td><?php echo htmlspecialchars($student['email'] ?? '-'); ?></td>
                                                <td>
                                                    <span class="badge bg-success">
                                                        <?php echo $student['enrolled_subjects']; ?> subjects
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo $student['exam_attempts']; ?> attempts
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm btn-primary" 
                                                                onclick="enrollStudent('<?php echo $student['user_id']; ?>', '<?php echo htmlspecialchars($student['user_name']); ?>')">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-info" 
                                                                onclick="viewEnrollments('<?php echo $student['user_id']; ?>', '<?php echo htmlspecialchars($student['user_name']); ?>')">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Enroll Student Modal -->
    <div class="modal fade" id="enrollModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enroll Student in Subjects</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="enrollForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="enroll">
                        <input type="hidden" name="student_id" id="enroll_student_id">
                        <div class="mb-3">
                            <label class="form-label">Student</label>
                            <input type="text" class="form-control" id="enroll_student_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Subjects</label>
                            <div class="row" style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($subjects as $subject): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="subject_ids[]" 
                                                   value="<?php echo $subject['subject_id']; ?>" 
                                                   id="subject_<?php echo $subject['subject_id']; ?>">
                                            <label class="form-check-label" for="subject_<?php echo $subject['subject_id']; ?>">
                                                <strong><?php echo htmlspecialchars($subject['subject_name']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($subject['subject_code']); ?> - 
                                                    Year <?php echo $subject['year']; ?> Section <?php echo $subject['section']; ?>
                                                    <?php if ($subject['faculty_name']): ?>
                                                        <br>Faculty: <?php echo htmlspecialchars($subject['faculty_name']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Enroll Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Enrollments Modal -->
    <div class="modal fade" id="viewEnrollmentsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Student Enrollments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Student</label>
                        <input type="text" class="form-control" id="view_student_name" readonly>
                    </div>
                    <div id="enrollmentsList">
                        <!-- Enrollments will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function enrollStudent(studentId, studentName) {
            document.getElementById('enroll_student_id').value = studentId;
            document.getElementById('enroll_student_name').value = studentName;
            
            // Reset checkboxes
            document.querySelectorAll('input[name="subject_ids[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            const modal = new bootstrap.Modal(document.getElementById('enrollModal'));
            modal.show();
        }

        async function viewEnrollments(studentId, studentName) {
            document.getElementById('view_student_name').value = studentName;
            
            try {
                const response = await fetch(`../api/get-student-enrollments.php?student_id=${studentId}`);
                const data = await response.json();
                
                if (data.success) {
                    const enrollmentsList = document.getElementById('enrollmentsList');
                    
                    if (data.enrollments.length === 0) {
                        enrollmentsList.innerHTML = '<p class="text-muted">No enrollments found.</p>';
                    } else {
                        enrollmentsList.innerHTML = `
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Code</th>
                                            <th>Year & Section</th>
                                            <th>Faculty</th>
                                            <th>Enrolled</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.enrollments.map(enrollment => `
                                            <tr>
                                                <td>${enrollment.subject_name}</td>
                                                <td><code>${enrollment.subject_code}</code></td>
                                                <td>
                                                    <span class="badge bg-primary">Year ${enrollment.year}</span>
                                                    <span class="badge bg-secondary">Section ${enrollment.section}</span>
                                                </td>
                                                <td>${enrollment.faculty_name || 'Not assigned'}</td>
                                                <td><span class="badge bg-success">Enrolled</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-danger" 
                                                            onclick="unenrollStudent('${studentId}', '${enrollment.subject_id}', '${enrollment.subject_name}')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }
                } else {
                    document.getElementById('enrollmentsList').innerHTML = '<p class="text-danger">Error loading enrollments.</p>';
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('enrollmentsList').innerHTML = '<p class="text-danger">Error loading enrollments.</p>';
            }
            
            const modal = new bootstrap.Modal(document.getElementById('viewEnrollmentsModal'));
            modal.show();
        }

        function unenrollStudent(studentId, subjectId, subjectName) {
            if (confirm(`Are you sure you want to unenroll the student from ${subjectName}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="unenroll">
                    <input type="hidden" name="student_id" value="${studentId}">
                    <input type="hidden" name="subject_id" value="${subjectId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>