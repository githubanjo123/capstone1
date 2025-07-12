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
        if ($action === 'add') {
            $subject_code = $_POST['subject_code'] ?? '';
            $subject_name = $_POST['subject_name'] ?? '';
            $year = $_POST['year'] ?? '';
            $section = $_POST['section'] ?? '';
            
            // Validate inputs
            if (empty($subject_code) || empty($subject_name) || empty($year) || empty($section)) {
                throw new Exception('All fields are required');
            }
            
            // Check if subject code already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE subject_code = ?");
            $stmt->execute([$subject_code]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Subject code already exists');
            }
            
            // Insert subject
            $stmt = $pdo->prepare("INSERT INTO subjects (subject_code, subject_name, year, section) VALUES (?, ?, ?, ?)");
            $stmt->execute([$subject_code, $subject_name, $year, $section]);
            
            $message = 'Subject added successfully!';
            
        } elseif ($action === 'edit') {
            $subject_id = $_POST['subject_id'] ?? '';
            $subject_code = $_POST['subject_code'] ?? '';
            $subject_name = $_POST['subject_name'] ?? '';
            $year = $_POST['year'] ?? '';
            $section = $_POST['section'] ?? '';
            
            if (empty($subject_id) || empty($subject_code) || empty($subject_name) || empty($year) || empty($section)) {
                throw new Exception('All fields are required');
            }
            
            // Check if subject code already exists (excluding current subject)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE subject_code = ? AND subject_id != ?");
            $stmt->execute([$subject_code, $subject_id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Subject code already exists');
            }
            
            // Update subject
            $stmt = $pdo->prepare("UPDATE subjects SET subject_code = ?, subject_name = ?, year = ?, section = ? WHERE subject_id = ?");
            $stmt->execute([$subject_code, $subject_name, $year, $section, $subject_id]);
            
            $message = 'Subject updated successfully!';
            
        } elseif ($action === 'delete') {
            $subject_id = $_POST['subject_id'] ?? '';
            
            if (empty($subject_id)) {
                throw new Exception('Subject ID is required');
            }
            
            // Check if subject has associated exams
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE subject_id = ?");
            $stmt->execute([$subject_id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cannot delete subject with associated exams');
            }
            
            $stmt = $pdo->prepare("DELETE FROM subjects WHERE subject_id = ?");
            $stmt->execute([$subject_id]);
            
            $message = 'Subject deleted successfully!';
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get subjects with pagination
$page = $_GET['page'] ?? 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$filter_year = $_GET['year'] ?? '';
$search = $_GET['search'] ?? '';

$where_conditions = [];
$params = [];

if ($filter_year) {
    $where_conditions[] = "year = ?";
    $params[] = $filter_year;
}

if ($search) {
    $where_conditions[] = "(subject_name LIKE ? OR subject_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) FROM subjects $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_subjects = $count_stmt->fetchColumn();
$total_pages = ceil($total_subjects / $per_page);

// Get subjects with faculty info
$sql = "SELECT s.*, u.user_name as faculty_name, COUNT(e.exam_id) as exam_count
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

// Get available years for filter
$years_stmt = $pdo->query("SELECT DISTINCT year FROM subjects ORDER BY year");
$years = $years_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Management - Admin Dashboard</title>
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
        
        .year-badge {
            font-size: 0.8rem;
            padding: 0.3em 0.6em;
            border-radius: 20px;
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
                    <a class="nav-link active" href="subjects.php">
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
                    <h2>Subject Management</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="fas fa-plus"></i> Add Subject
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

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <select class="form-select" name="year">
                                    <option value="">All Years</option>
                                    <?php foreach ($years as $year_option): ?>
                                        <option value="<?php echo $year_option['year']; ?>" <?php echo $filter_year == $year_option['year'] ? 'selected' : ''; ?>>
                                            Year <?php echo $year_option['year']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <input type="text" class="form-control" name="search" placeholder="Search subjects..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Subjects Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Code</th>
                                        <th>Year & Section</th>
                                        <th>Faculty</th>
                                        <th>Exams</th>
                                        <th>Created</th>
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
                                                        <small class="text-muted">ID: <?php echo $subject['subject_id']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($subject['subject_code']); ?></code>
                                            </td>
                                            <td>
                                                <span class="year-badge badge bg-primary">
                                                    Year <?php echo $subject['year']; ?>
                                                </span>
                                                <span class="badge bg-secondary ms-1">
                                                    Section <?php echo htmlspecialchars($subject['section']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($subject['faculty_name']): ?>
                                                    <span class="badge bg-success">
                                                        <?php echo htmlspecialchars($subject['faculty_name']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Not Assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo $subject['exam_count']; ?> exams
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($subject['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-info" onclick="editSubject(<?php echo $subject['subject_id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteSubject(<?php echo $subject['subject_id']; ?>, '<?php echo htmlspecialchars($subject['subject_name']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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
                                            <a class="page-link" href="?page=<?php echo $i; ?>&year=<?php echo $filter_year; ?>&search=<?php echo urlencode($search); ?>">
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

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="subject_code" class="form-label">Subject Code *</label>
                            <input type="text" class="form-control" id="subject_code" name="subject_code" required placeholder="e.g., MATH101">
                        </div>
                        <div class="mb-3">
                            <label for="subject_name" class="form-label">Subject Name *</label>
                            <input type="text" class="form-control" id="subject_name" name="subject_name" required placeholder="e.g., Mathematics">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="year" class="form-label">Year *</label>
                                    <select class="form-select" id="year" name="year" required>
                                        <option value="">Select Year</option>
                                        <option value="1">1st Year</option>
                                        <option value="2">2nd Year</option>
                                        <option value="3">3rd Year</option>
                                        <option value="4">4th Year</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="section" class="form-label">Section *</label>
                                    <select class="form-select" id="section" name="section" required>
                                        <option value="">Select Section</option>
                                        <option value="A">Section A</option>
                                        <option value="B">Section B</option>
                                        <option value="C">Section C</option>
                                        <option value="D">Section D</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Subject Modal -->
    <div class="modal fade" id="editSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editSubjectForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="subject_id" id="edit_subject_id">
                        <div class="mb-3">
                            <label for="edit_subject_code" class="form-label">Subject Code *</label>
                            <input type="text" class="form-control" id="edit_subject_code" name="subject_code" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_subject_name" class="form-label">Subject Name *</label>
                            <input type="text" class="form-control" id="edit_subject_name" name="subject_name" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_year" class="form-label">Year *</label>
                                    <select class="form-select" id="edit_year" name="year" required>
                                        <option value="">Select Year</option>
                                        <option value="1">1st Year</option>
                                        <option value="2">2nd Year</option>
                                        <option value="3">3rd Year</option>
                                        <option value="4">4th Year</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_section" class="form-label">Section *</label>
                                    <select class="form-select" id="edit_section" name="section" required>
                                        <option value="">Select Section</option>
                                        <option value="A">Section A</option>
                                        <option value="B">Section B</option>
                                        <option value="C">Section C</option>
                                        <option value="D">Section D</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Subject Modal -->
    <div class="modal fade" id="deleteSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="deleteSubjectForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="subject_id" id="delete_subject_id">
                        <p>Are you sure you want to delete subject <strong id="delete_subject_name"></strong>?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> This action cannot be undone.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function editSubject(subjectId) {
            try {
                const response = await fetch(`../api/get-subject.php?subject_id=${subjectId}`);
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('edit_subject_id').value = data.subject.subject_id;
                    document.getElementById('edit_subject_code').value = data.subject.subject_code;
                    document.getElementById('edit_subject_name').value = data.subject.subject_name;
                    document.getElementById('edit_year').value = data.subject.year;
                    document.getElementById('edit_section').value = data.subject.section;
                    
                    const modal = new bootstrap.Modal(document.getElementById('editSubjectModal'));
                    modal.show();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error fetching subject:', error);
                alert('Error loading subject data');
            }
        }

        function deleteSubject(subjectId, subjectName) {
            document.getElementById('delete_subject_id').value = subjectId;
            document.getElementById('delete_subject_name').textContent = subjectName;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteSubjectModal'));
            modal.show();
        }
    </script>
</body>
</html>