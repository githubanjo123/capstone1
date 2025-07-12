<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Include database configuration
require_once '../db.php';

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'assign') {
            $subject_id = $_POST['subject_id'] ?? '';
            $faculty_id = $_POST['faculty_id'] ?? '';
            
            if (empty($subject_id) || empty($faculty_id)) {
                throw new Exception('Subject and Faculty are required');
            }
            
            // Check if faculty exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_id = ? AND user_type = 'faculty'");
            $stmt->execute([$faculty_id]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('Faculty member not found');
            }
            
            // Update subject assignment
            $stmt = $pdo->prepare("UPDATE subjects SET faculty_id = ? WHERE subject_id = ?");
            $stmt->execute([$faculty_id, $subject_id]);
            
            $message = 'Subject assigned to faculty successfully!';
            
        } elseif ($action === 'unassign') {
            $subject_id = $_POST['subject_id'] ?? '';
            
            if (empty($subject_id)) {
                throw new Exception('Subject ID is required');
            }
            
            // Remove faculty assignment
            $stmt = $pdo->prepare("UPDATE subjects SET faculty_id = NULL WHERE subject_id = ?");
            $stmt->execute([$subject_id]);
            
            $message = 'Subject unassigned successfully!';
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get subjects with faculty assignments
$page = $_GET['page'] ?? 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

$filter_assigned = $_GET['assigned'] ?? '';
$filter_year = $_GET['year'] ?? '';
$search = $_GET['search'] ?? '';

$where_conditions = [];
$params = [];

if ($filter_assigned === 'assigned') {
    $where_conditions[] = "s.faculty_id IS NOT NULL";
} elseif ($filter_assigned === 'unassigned') {
    $where_conditions[] = "s.faculty_id IS NULL";
}

if ($filter_year) {
    $where_conditions[] = "s.year = ?";
    $params[] = $filter_year;
}

if ($search) {
    $where_conditions[] = "(s.subject_name LIKE ? OR s.subject_code LIKE ? OR u.user_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) FROM subjects s LEFT JOIN users u ON s.faculty_id = u.user_id $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_subjects = $count_stmt->fetchColumn();
$total_pages = ceil($total_subjects / $per_page);

// Get subjects with faculty info
$sql = "SELECT s.*, u.user_name as faculty_name, u.email as faculty_email, COUNT(e.exam_id) as exam_count
        FROM subjects s
        LEFT JOIN users u ON s.faculty_id = u.user_id
        LEFT JOIN exams e ON s.subject_id = e.subject_id
        $where_clause
        GROUP BY s.subject_id
        ORDER BY s.subject_name, s.year, s.section
        LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$subjects = $stmt->fetchAll();

// Get all faculty members
$faculty_stmt = $pdo->prepare("SELECT user_id, user_name, email FROM users WHERE user_type = 'faculty' ORDER BY user_name");
$faculty_stmt->execute();
$faculty_members = $faculty_stmt->fetchAll();

// Get available years for filter
$years_stmt = $pdo->query("SELECT DISTINCT year FROM subjects ORDER BY year");
$years = $years_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Assignments - Admin Dashboard</title>
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
        
        .subject-icon {
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
        
        .faculty-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--success-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.8rem;
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
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .assignment-status {
            padding: 10px 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .status-assigned {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .status-unassigned {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .quick-assign {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
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
                    <a class="nav-link active" href="assignments.php">
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
                    <h2>Faculty Assignments</h2>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal">
                            <i class="fas fa-user-plus"></i> Assign Faculty
                        </button>
                        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#bulkAssignModal">
                            <i class="fas fa-users"></i> Bulk Assign
                        </button>
                    </div>
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

                <!-- Quick Stats -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="assignment-status status-assigned">
                            <h6><i class="fas fa-check-circle"></i> Assigned Subjects</h6>
                            <h4><?php 
                                $assigned_count = $pdo->query("SELECT COUNT(*) FROM subjects WHERE faculty_id IS NOT NULL")->fetchColumn();
                                echo $assigned_count;
                            ?></h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="assignment-status status-unassigned">
                            <h6><i class="fas fa-exclamation-circle"></i> Unassigned Subjects</h6>
                            <h4><?php 
                                $unassigned_count = $pdo->query("SELECT COUNT(*) FROM subjects WHERE faculty_id IS NULL")->fetchColumn();
                                echo $unassigned_count;
                            ?></h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="assignment-status" style="background: #e7f3ff; border: 1px solid #b3d9ff; color: #0056b3;">
                            <h6><i class="fas fa-users"></i> Total Faculty</h6>
                            <h4><?php echo count($faculty_members); ?></h4>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <select class="form-select" name="assigned">
                                    <option value="">All Subjects</option>
                                    <option value="assigned" <?php echo $filter_assigned === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                    <option value="unassigned" <?php echo $filter_assigned === 'unassigned' ? 'selected' : ''; ?>>Unassigned</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="year">
                                    <option value="">All Years</option>
                                    <?php foreach ($years as $year_option): ?>
                                        <option value="<?php echo $year_option['year']; ?>" <?php echo $filter_year == $year_option['year'] ? 'selected' : ''; ?>>
                                            Year <?php echo $year_option['year']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="search" placeholder="Search subjects or faculty..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Assignments Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Year & Section</th>
                                        <th>Assigned Faculty</th>
                                        <th>Exams</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="subject-icon me-3">
                                                        <i class="fas fa-book"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($subject['subject_name']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($subject['subject_code']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">Year <?php echo $subject['year']; ?></span>
                                                <span class="badge bg-secondary ms-1">Section <?php echo htmlspecialchars($subject['section']); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($subject['faculty_name']): ?>
                                                    <div class="d-flex align-items-center">
                                                        <div class="faculty-avatar me-2">
                                                            <?php echo strtoupper(substr($subject['faculty_name'], 0, 2)); ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($subject['faculty_name']); ?></div>
                                                            <small class="text-muted"><?php echo htmlspecialchars($subject['faculty_email'] ?? ''); ?></small>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo $subject['exam_count']; ?> exams
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($subject['faculty_name']): ?>
                                                    <span class="badge bg-success">Assigned</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Unassigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-primary" onclick="assignFaculty(<?php echo $subject['subject_id']; ?>, '<?php echo htmlspecialchars($subject['subject_name']); ?>', '<?php echo $subject['faculty_id'] ?? ''; ?>')">
                                                        <i class="fas fa-user-plus"></i>
                                                    </button>
                                                    <?php if ($subject['faculty_name']): ?>
                                                        <button class="btn btn-sm btn-warning" onclick="unassignFaculty(<?php echo $subject['subject_id']; ?>, '<?php echo htmlspecialchars($subject['subject_name']); ?>')">
                                                            <i class="fas fa-user-minus"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav>
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&assigned=<?php echo $filter_assigned; ?>&year=<?php echo $filter_year; ?>&search=<?php echo urlencode($search); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Faculty Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Faculty to Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="assignForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="assign">
                        <input type="hidden" name="subject_id" id="assign_subject_id">
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" id="assign_subject_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="faculty_id" class="form-label">Select Faculty *</label>
                            <select class="form-select" id="faculty_id" name="faculty_id" required>
                                <option value="">Choose Faculty Member</option>
                                <?php foreach ($faculty_members as $faculty): ?>
                                    <option value="<?php echo $faculty['user_id']; ?>">
                                        <?php echo htmlspecialchars($faculty['user_name']); ?>
                                        <?php if ($faculty['email']): ?>
                                            (<?php echo htmlspecialchars($faculty['email']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Faculty</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Unassign Faculty Modal -->
    <div class="modal fade" id="unassignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Unassign Faculty</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="unassignForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="unassign">
                        <input type="hidden" name="subject_id" id="unassign_subject_id">
                        <p>Are you sure you want to unassign the faculty from subject <strong id="unassign_subject_name"></strong>?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> This will remove the faculty assignment.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Unassign</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Assign Modal -->
    <div class="modal fade" id="bulkAssignModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Quick assignment of multiple subjects to faculty members.</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> This feature allows you to assign multiple subjects to a faculty member at once.
                    </div>
                    <form id="bulkAssignForm" method="POST">
                        <input type="hidden" name="action" value="bulk_assign">
                        <div class="mb-3">
                            <label for="bulk_faculty_id" class="form-label">Select Faculty *</label>
                            <select class="form-select" id="bulk_faculty_id" name="bulk_faculty_id" required>
                                <option value="">Choose Faculty Member</option>
                                <?php foreach ($faculty_members as $faculty): ?>
                                    <option value="<?php echo $faculty['user_id']; ?>">
                                        <?php echo htmlspecialchars($faculty['user_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Subjects</label>
                            <div class="form-check-group" style="max-height: 200px; overflow-y: auto;">
                                <?php 
                                $unassigned_subjects = $pdo->query("SELECT subject_id, subject_name, subject_code, year, section FROM subjects WHERE faculty_id IS NULL ORDER BY subject_name")->fetchAll();
                                foreach ($unassigned_subjects as $subject): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="subject_ids[]" value="<?php echo $subject['subject_id']; ?>" id="subject_<?php echo $subject['subject_id']; ?>">
                                        <label class="form-check-label" for="subject_<?php echo $subject['subject_id']; ?>">
                                            <?php echo htmlspecialchars($subject['subject_name']); ?> (<?php echo htmlspecialchars($subject['subject_code']); ?>) - Year <?php echo $subject['year']; ?> Section <?php echo $subject['section']; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="bulkAssignForm" class="btn btn-primary">Assign Selected</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function assignFaculty(subjectId, subjectName, currentFacultyId) {
            document.getElementById('assign_subject_id').value = subjectId;
            document.getElementById('assign_subject_name').value = subjectName;
            document.getElementById('faculty_id').value = currentFacultyId || '';
            
            const modal = new bootstrap.Modal(document.getElementById('assignModal'));
            modal.show();
        }

        function unassignFaculty(subjectId, subjectName) {
            document.getElementById('unassign_subject_id').value = subjectId;
            document.getElementById('unassign_subject_name').textContent = subjectName;
            
            const modal = new bootstrap.Modal(document.getElementById('unassignModal'));
            modal.show();
        }

        // Handle bulk assignment
        document.getElementById('bulkAssignForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const selectedSubjects = formData.getAll('subject_ids[]');
            
            if (selectedSubjects.length === 0) {
                alert('Please select at least one subject.');
                return;
            }
            
            // Submit the form
            this.submit();
        });
    </script>
</body>
</html>