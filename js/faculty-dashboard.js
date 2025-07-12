// Faculty Dashboard JavaScript
class FacultyDashboard {
    constructor() {
        this.questions = [];
        this.editingIndex = -1;
        this.init();
    }

    init() {
        this.loadClasses();
        this.setupEventListeners();
        this.setupQuestionTypeChange();
    }

    setupEventListeners() {
        // Form submission
        document.getElementById('examForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.createExam();
        });

        // Question modal events
        const questionModal = document.getElementById('questionModal');
        questionModal.addEventListener('hidden.bs.modal', () => {
            this.resetQuestionForm();
        });
    }

    setupQuestionTypeChange() {
        this.handleQuestionTypeChange();
    }

    // Utility function to get ordinal suffix
    getOrdinalSuffix(num) {
        const j = num % 10;
        const k = num % 100;
        if (j === 1 && k !== 11) return num + "st";
        if (j === 2 && k !== 12) return num + "nd";
        if (j === 3 && k !== 13) return num + "rd";
        return num + "th";
    }

    // Show alert messages
    showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alertContainer');
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        alertContainer.appendChild(alertDiv);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // Load classes for the faculty
    async loadClasses() {
        try {
            const response = await fetch('api/get-faculty-subjects.php');
            const data = await response.json();
            
            const classSelect = document.getElementById('classSelect');
            classSelect.innerHTML = '<option value="">Select a class...</option>';
            
            if (data.success && data.subjects) {
                data.subjects.forEach(subject => {
                    const option = document.createElement('option');
                    option.value = JSON.stringify({
                        subject_id: subject.subject_id,
                        subject_name: subject.subject_name,
                        year: subject.year,
                        section: subject.section
                    });
                    option.textContent = `${subject.subject_name} - ${this.getOrdinalSuffix(subject.year)} Year ${subject.section}`;
                    classSelect.appendChild(option);
                });
            } else {
                classSelect.innerHTML = '<option value="">No classes assigned</option>';
            }
        } catch (error) {
            console.error('Error loading classes:', error);
            this.showAlert('Error loading classes. Please refresh the page.', 'danger');
        }
    }

    // Get selected class data
    selectClass() {
        const classSelect = document.getElementById('classSelect');
        if (classSelect.value) {
            return JSON.parse(classSelect.value);
        }
        return null;
    }

    // Add new question
    addQuestion() {
        this.editingIndex = -1;
        this.resetQuestionForm();
        const modal = new bootstrap.Modal(document.getElementById('questionModal'));
        modal.show();
    }

    // Edit existing question
    editQuestion(index) {
        this.editingIndex = index;
        const question = this.questions[index];
        
        document.getElementById('questionText').value = question.question_text;
        document.getElementById('questionType').value = question.question_type;
        document.getElementById('questionMarks').value = question.marks;
        
        this.handleQuestionTypeChange();
        
        // Populate options based on question type
        if (question.question_type === 'multiple_choice') {
            const options = question.options;
            for (let i = 0; i < 4; i++) {
                const optionInput = document.getElementById(`option${i + 1}`);
                if (optionInput) {
                    optionInput.value = options[i] || '';
                }
            }
            
            // Set correct answer
            const correctAnswerSelect = document.getElementById('correctAnswer');
            if (correctAnswerSelect) {
                correctAnswerSelect.value = question.correct_answer;
            }
        } else if (question.question_type === 'true_false') {
            const correctAnswerSelect = document.getElementById('correctAnswer');
            if (correctAnswerSelect) {
                correctAnswerSelect.value = question.correct_answer;
            }
        }
        
        const modal = new bootstrap.Modal(document.getElementById('questionModal'));
        modal.show();
    }

    // Delete question
    deleteQuestion(index) {
        if (confirm('Are you sure you want to delete this question?')) {
            this.questions.splice(index, 1);
            this.renderQuestions();
            this.showAlert('Question deleted successfully', 'success');
        }
    }

    // Handle question type change
    handleQuestionTypeChange() {
        const questionType = document.getElementById('questionType').value;
        const optionsContainer = document.getElementById('optionsContainer');
        
        if (questionType === 'multiple_choice') {
            optionsContainer.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">Options</label>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <input type="text" class="form-control" id="option1" placeholder="Option 1" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <input type="text" class="form-control" id="option2" placeholder="Option 2" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <input type="text" class="form-control" id="option3" placeholder="Option 3" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <input type="text" class="form-control" id="option4" placeholder="Option 4" required>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="correctAnswer" class="form-label">Correct Answer</label>
                    <select class="form-select" id="correctAnswer" required>
                        <option value="">Select correct answer</option>
                        <option value="1">Option 1</option>
                        <option value="2">Option 2</option>
                        <option value="3">Option 3</option>
                        <option value="4">Option 4</option>
                    </select>
                </div>
            `;
        } else if (questionType === 'true_false') {
            optionsContainer.innerHTML = `
                <div class="mb-3">
                    <label for="correctAnswer" class="form-label">Correct Answer</label>
                    <select class="form-select" id="correctAnswer" required>
                        <option value="">Select correct answer</option>
                        <option value="true">True</option>
                        <option value="false">False</option>
                    </select>
                </div>
            `;
        }
    }

    // Save question
    saveQuestion() {
        const questionText = document.getElementById('questionText').value.trim();
        const questionType = document.getElementById('questionType').value;
        const questionMarks = parseInt(document.getElementById('questionMarks').value);
        const correctAnswer = document.getElementById('correctAnswer').value;
        
        if (!questionText || !questionType || !questionMarks || !correctAnswer) {
            this.showAlert('Please fill in all required fields', 'danger');
            return;
        }
        
        const question = {
            question_text: questionText,
            question_type: questionType,
            marks: questionMarks,
            correct_answer: correctAnswer
        };
        
        if (questionType === 'multiple_choice') {
            const options = [];
            for (let i = 1; i <= 4; i++) {
                const optionValue = document.getElementById(`option${i}`).value.trim();
                if (!optionValue) {
                    this.showAlert(`Please fill in Option ${i}`, 'danger');
                    return;
                }
                options.push(optionValue);
            }
            question.options = options;
        }
        
        if (this.editingIndex >= 0) {
            this.questions[this.editingIndex] = question;
            this.showAlert('Question updated successfully', 'success');
        } else {
            this.questions.push(question);
            this.showAlert('Question added successfully', 'success');
        }
        
        this.renderQuestions();
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('questionModal'));
        modal.hide();
    }

    // Reset question form
    resetQuestionForm() {
        document.getElementById('questionForm').reset();
        this.handleQuestionTypeChange();
        this.editingIndex = -1;
    }

    // Render questions
    renderQuestions() {
        const container = document.getElementById('questionsContainer');
        
        if (this.questions.length === 0) {
            container.innerHTML = '<p class="text-muted text-center">No questions added yet. Click "Add Question" to start.</p>';
            return;
        }
        
        container.innerHTML = this.questions.map((question, index) => `
            <div class="card question-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="card-title">Question ${index + 1} (${question.marks} marks)</h6>
                            <p class="card-text">${question.question_text}</p>
                            <small class="text-light">Type: ${question.question_type.replace('_', ' ').toUpperCase()}</small>
                            ${question.options ? `<br><small class="text-light">Options: ${question.options.join(', ')}</small>` : ''}
                            <br><small class="text-light">Correct Answer: ${question.correct_answer}</small>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-light" onclick="dashboard.editQuestion(${index})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-light" onclick="dashboard.deleteQuestion(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Validate exam form
    validateExamForm() {
        const errors = [];
        
        const classSelect = document.getElementById('classSelect');
        const examTitle = document.getElementById('examTitle').value.trim();
        const examDate = document.getElementById('examDate').value;
        const examTime = document.getElementById('examTime').value;
        const examDuration = document.getElementById('examDuration').value;
        const totalMarks = document.getElementById('totalMarks').value;
        
        if (!classSelect.value) {
            errors.push('Please select a class');
        }
        
        if (!examTitle) {
            errors.push('Please enter exam title');
        }
        
        if (!examDate) {
            errors.push('Please select exam date');
        }
        
        if (!examTime) {
            errors.push('Please select exam time');
        }
        
        if (!examDuration || examDuration < 15 || examDuration > 300) {
            errors.push('Please enter valid exam duration (15-300 minutes)');
        }
        
        if (!totalMarks || totalMarks < 1) {
            errors.push('Please enter valid total marks');
        }
        
        if (this.questions.length === 0) {
            errors.push('Please add at least one question');
        }
        
        // Check if total marks match sum of question marks
        const questionMarksSum = this.questions.reduce((sum, q) => sum + q.marks, 0);
        if (parseInt(totalMarks) !== questionMarksSum) {
            errors.push(`Total marks (${totalMarks}) should equal sum of question marks (${questionMarksSum})`);
        }
        
        return errors;
    }

    // Create exam
    async createExam() {
        const errors = this.validateExamForm();
        
        if (errors.length > 0) {
            this.showAlert(errors.join('<br>'), 'danger');
            return;
        }
        
        const formData = this.getFormData();
        
        try {
            // Show loading
            document.querySelector('.loading').style.display = 'block';
            document.querySelector('button[type="submit"]').disabled = true;
            
            const response = await fetch('api/create-exam.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showAlert('Exam created successfully!', 'success');
                // Reset form
                document.getElementById('examForm').reset();
                this.questions = [];
                this.renderQuestions();
                this.loadClasses();
            } else {
                this.showAlert(data.message || 'Error creating exam', 'danger');
            }
        } catch (error) {
            console.error('Error creating exam:', error);
            this.showAlert('Error creating exam. Please try again.', 'danger');
        } finally {
            // Hide loading
            document.querySelector('.loading').style.display = 'none';
            document.querySelector('button[type="submit"]').disabled = false;
        }
    }

    // Get form data
    getFormData() {
        const classData = this.selectClass();
        
        return {
            subject_id: classData.subject_id,
            title: document.getElementById('examTitle').value.trim(),
            exam_date: document.getElementById('examDate').value,
            exam_time: document.getElementById('examTime').value,
            duration: parseInt(document.getElementById('examDuration').value),
            total_marks: parseInt(document.getElementById('totalMarks').value),
            instructions: document.getElementById('examInstructions').value.trim(),
            questions: this.questions
        };
    }
}

// Global functions for onclick handlers
function addQuestion() {
    dashboard.addQuestion();
}

function handleQuestionTypeChange() {
    dashboard.handleQuestionTypeChange();
}

function saveQuestion() {
    dashboard.saveQuestion();
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.dashboard = new FacultyDashboard();
});