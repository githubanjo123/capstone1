<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Redirect admin users to admin dashboard
if ($_SESSION['user_type'] === 'admin') {
    header('Location: admin/index.php');
    exit();
}

// Faculty users only from here
if ($_SESSION['user_type'] !== 'faculty') {
    header('Location: login.php');
    exit();
}

// Include database configuration
require_once 'db.php';

// Get faculty information
$faculty_id = $_SESSION['user_id'];
$faculty_name = $_SESSION['user_name'] ?? 'Faculty';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - Exam System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
        }
        .btn-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
            border: none;
            border-radius: 25px;
        }
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            border: none;
            border-radius: 25px;
        }
        .question-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            margin-bottom: 10px;
            border-radius: 10px;
        }
        .exam-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .loading {
            display: none;
        }
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-graduation-cap"></i> Faculty Dashboard</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($faculty_name); ?></span>
                <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Alert Container -->
        <div id="alertContainer"></div>

        <!-- Dashboard Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="text-center mb-4">Create New Exam</h2>
            </div>
        </div>

        <!-- Exam Creation Form -->
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="exam-form">
                    <form id="examForm">
                        <!-- Class Selection -->
                        <div class="mb-3">
                            <label for="classSelect" class="form-label"><i class="fas fa-users"></i> Select Class</label>
                            <select class="form-select" id="classSelect" required>
                                <option value="">Loading classes...</option>
                            </select>
                        </div>

                        <!-- Exam Details -->
                        <div class="mb-3">
                            <label for="examTitle" class="form-label"><i class="fas fa-book"></i> Exam Title</label>
                            <input type="text" class="form-control" id="examTitle" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="examDate" class="form-label"><i class="fas fa-calendar"></i> Exam Date</label>
                                <input type="date" class="form-control" id="examDate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="examTime" class="form-label"><i class="fas fa-clock"></i> Exam Time</label>
                                <input type="time" class="form-control" id="examTime" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="examDuration" class="form-label"><i class="fas fa-hourglass-half"></i> Duration (minutes)</label>
                                <input type="number" class="form-control" id="examDuration" min="15" max="300" required>
                            </div>
                            <div class="col-md-6">
                                <label for="totalMarks" class="form-label"><i class="fas fa-star"></i> Total Marks</label>
                                <input type="number" class="form-control" id="totalMarks" min="1" required>
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div class="mb-4">
                            <label for="examInstructions" class="form-label"><i class="fas fa-info-circle"></i> Instructions</label>
                            <textarea class="form-control" id="examInstructions" rows="3" placeholder="Enter exam instructions..."></textarea>
                        </div>

                        <!-- Questions Section -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5><i class="fas fa-question-circle"></i> Questions</h5>
                                <button type="button" class="btn btn-success" onclick="addQuestion()">
                                    <i class="fas fa-plus"></i> Add Question
                                </button>
                            </div>
                            <div id="questionsContainer">
                                <!-- Questions will be added here dynamically -->
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-save"></i> Create Exam
                            </button>
                            <div class="loading mt-3">
                                <i class="fas fa-spinner fa-spin"></i> Creating exam...
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Question Modal -->
    <div class="modal fade" id="questionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="questionForm">
                        <div class="mb-3">
                            <label for="questionText" class="form-label">Question Text</label>
                            <textarea class="form-control" id="questionText" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="questionType" class="form-label">Question Type</label>
                            <select class="form-select" id="questionType" onchange="handleQuestionTypeChange()">
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="true_false">True/False</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="questionMarks" class="form-label">Marks</label>
                            <input type="number" class="form-control" id="questionMarks" min="1" required>
                        </div>

                        <div id="optionsContainer">
                            <!-- Options will be added here based on question type -->
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveQuestion()">Save Question</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/faculty-dashboard.js"></script>
</body>
</html>