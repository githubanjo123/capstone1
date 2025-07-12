# Faculty Dashboard - Exam Management System

A comprehensive web-based exam management system that allows faculty members to create, manage, and conduct online exams for their students.

## 🚀 Features

### Faculty Features
- **User Authentication**: Secure login system for faculty members
- **Subject Management**: View assigned subjects and classes
- **Exam Creation**: Create comprehensive exams with multiple question types
- **Question Management**: Add, edit, and delete questions with ease
- **Multiple Question Types**: Support for Multiple Choice and True/False questions
- **Exam Validation**: Automatic validation of exam structure and marks
- **Responsive Design**: Modern, mobile-friendly interface

### System Features
- **Database Integration**: MySQL database with proper relationships
- **Session Management**: Secure session handling
- **API Endpoints**: RESTful API for frontend-backend communication
- **Error Handling**: Comprehensive error handling and logging
- **Security**: SQL injection prevention and input validation

## 🛠️ Technology Stack

### Frontend
- **HTML5**: Modern semantic markup
- **CSS3**: Custom styling with Bootstrap 5
- **JavaScript**: ES6+ with modern features
- **Bootstrap 5**: Responsive UI framework
- **Font Awesome**: Icon library

### Backend
- **PHP 7.4+**: Server-side scripting
- **MySQL**: Database management
- **PDO**: Database abstraction layer
- **JSON**: Data exchange format

### Development Tools
- **Jest**: JavaScript testing framework
- **PHPUnit**: PHP testing framework
- **Composer**: PHP dependency management

## 📋 Prerequisites

Before running this application, make sure you have:

- **PHP 7.4 or higher**
- **MySQL 5.7 or higher**
- **Web server** (Apache/Nginx)
- **Composer** (for PHP dependencies)
- **Node.js** (for testing)

## 🔧 Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd faculty-dashboard
```

### 2. Database Setup
```bash
# Create database and import schema
mysql -u root -p < database.sql
```

### 3. Configure Database Connection
Edit `db.php` with your database credentials:
```php
$host = 'localhost';
$dbname = 'exam_system';
$username = 'your_username';
$password = 'your_password';
```

### 4. Set Up Web Server
Configure your web server to point to the project directory.

### 5. Install Dependencies (Optional - for testing)
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies for testing
cd tests
npm install
```

## 🚀 Usage

### 1. Access the Application
Open your web browser and navigate to your configured URL.

### 2. Login
Use the following demo credentials:
- **Username**: FAC001
- **Password**: password123

### 3. Create an Exam
1. Select a class from the dropdown
2. Fill in exam details (title, date, time, duration, marks)
3. Add questions using the "Add Question" button
4. Choose question type (Multiple Choice or True/False)
5. Enter question text and options
6. Set correct answer and marks
7. Submit the exam

## 📁 Project Structure

```
faculty-dashboard/
├── index.php                 # Main dashboard page
├── login.php                 # Login page
├── logout.php                # Logout functionality
├── db.php                    # Database configuration
├── database.sql              # Database schema
├── .gitignore               # Git ignore file
├── README.md                # This file
├── js/
│   └── faculty-dashboard.js  # Frontend JavaScript
├── api/
│   ├── get-faculty-subjects.php  # Get faculty subjects API
│   └── create-exam.php           # Create exam API
└── tests/                    # Test suite
    ├── frontend/
    │   └── faculty-dashboard.test.js
    ├── backend/
    │   ├── GetFacultySubjectsTest.php
    │   ├── CreateExamTest.php
    │   └── LogoutTest.php
    ├── integration/
    │   └── FacultyDashboardIntegrationTest.php
    ├── package.json
    ├── phpunit.xml
    ├── bootstrap.php
    ├── jest.setup.js
    ├── run-tests.sh
    └── README.md
```

## 🎯 API Endpoints

### GET /api/get-faculty-subjects.php
Retrieves subjects assigned to the logged-in faculty member.

**Response:**
```json
{
  "success": true,
  "subjects": [
    {
      "subject_id": 1,
      "subject_name": "Mathematics",
      "year": 3,
      "section": "A",
      "exam_count": 2
    }
  ]
}
```

### POST /api/create-exam.php
Creates a new exam with questions.

**Request:**
```json
{
  "subject_id": 1,
  "title": "Mid-Term Exam",
  "exam_date": "2024-02-15",
  "exam_time": "10:00",
  "duration": 120,
  "total_marks": 100,
  "instructions": "Read carefully...",
  "questions": [
    {
      "question_text": "What is 2+2?",
      "question_type": "multiple_choice",
      "options": ["2", "3", "4", "5"],
      "correct_answer": "3",
      "marks": 10
    }
  ]
}
```

## 🧪 Testing

The project includes a comprehensive test suite covering frontend, backend, and integration tests.

### Run All Tests
```bash
cd tests
chmod +x run-tests.sh
./run-tests.sh
```

### Run Specific Test Types
```bash
# Frontend tests only
./run-tests.sh frontend

# Backend tests only
./run-tests.sh backend

# Integration tests only
./run-tests.sh integration
```

### Test Coverage
- **Frontend**: Jest tests for JavaScript functionality
- **Backend**: PHPUnit tests for API endpoints
- **Integration**: End-to-end workflow testing

## 🔒 Security Features

- **SQL Injection Prevention**: Prepared statements with PDO
- **Session Management**: Secure session handling
- **Input Validation**: Server-side validation of all inputs
- **Authentication**: Role-based access control
- **Error Handling**: Proper error logging without exposing sensitive information

## 🎨 User Interface

The application features a modern, responsive design with:
- **Gradient backgrounds** for visual appeal
- **Card-based layouts** for better organization
- **Interactive modals** for question management
- **Real-time validation** and feedback
- **Mobile-responsive** design
- **Accessibility** features

## 📊 Database Schema

### Key Tables
- **users**: Faculty, admin, and student accounts
- **subjects**: Courses assigned to faculty
- **exams**: Exam information and metadata
- **questions**: Individual exam questions
- **enrollments**: Student-subject relationships
- **exam_attempts**: Student exam sessions
- **student_answers**: Individual question responses

## 🔧 Configuration

### Database Configuration
Edit `db.php` to configure your database connection:
```php
$host = 'localhost';
$dbname = 'exam_system';
$username = 'your_username';
$password = 'your_password';
```

### Session Configuration
Sessions are configured with secure defaults. Modify session settings in individual PHP files if needed.

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `db.php`
   - Ensure MySQL service is running
   - Verify database exists and schema is imported

2. **Login Issues**
   - Verify user exists in database
   - Check password hash matches
   - Ensure session is properly configured

3. **API Errors**
   - Check PHP error logs
   - Verify JSON format in requests
   - Ensure proper authentication

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Run the test suite
6. Submit a pull request

## 📝 License

This project is licensed under the MIT License. See LICENSE file for details.

## 👥 Demo Users

The system comes with pre-configured demo users:

### Faculty
- **Username**: FAC001, **Password**: password123 (Dr. John Smith)
- **Username**: FAC002, **Password**: password123 (Dr. Jane Doe)

### Admin
- **Username**: admin, **Password**: password123

### Students
- **Username**: 2020-001, **Password**: password123 (Alice Johnson)
- **Username**: 2020-002, **Password**: password123 (Bob Wilson)
- **Username**: 2020-003, **Password**: password123 (Charlie Brown)
- **Username**: 2020-004, **Password**: password123 (Diana Prince)

## 📞 Support

For support or questions:
1. Check the troubleshooting section
2. Review the test suite for examples
3. Check PHP and JavaScript console logs
4. Verify database schema and data

## 🚀 Future Enhancements

Potential features for future development:
- **Student Interface**: Allow students to take exams
- **Result Management**: Grade calculation and reporting
- **Bulk Question Import**: Import questions from CSV/Excel
- **Question Bank**: Reusable question repository
- **Analytics Dashboard**: Exam performance analytics
- **Email Notifications**: Automated exam reminders
- **Mobile App**: Native mobile application

---

**Happy Teaching! 🎓✨**