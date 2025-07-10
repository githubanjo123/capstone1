# Exam Management System

A complete web-based examination system with role-based access control for students, faculty, and administrators.

## Features

### For Students
- Take exams with time limits
- View available exams for their year/section
- Real-time timer during exams
- Automatic scoring upon submission
- View exam history and scores

### For Faculty
- Create and manage exams
- Add multiple choice and true/false questions
- Assign exams to specific year/section combinations
- View assigned subjects and classes
- Set custom time limits for exams

### For Administrators
- Manage users (students and faculty)
- Create and manage subjects
- Assign subjects to faculty
- Monitor system usage
- Generate reports

## Setup Instructions

### Prerequisites
- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

### Installation Steps

1. **Clone or download the project files** to your web server directory
2. **Set up the database:**
   - Create a MySQL database named `capstone`
   - Import the `database_schema.sql` file:
     ```sql
     mysql -u root -p capstone < database_schema.sql
     ```

3. **Configure database connection:**
   - Edit `api/config.php` with your database credentials:
     ```php
     $host = 'localhost';      // Your database host
     $dbname = 'capstone';     // Database name
     $username = 'root';       // Your database username
     $password = '';           // Your database password
     ```

4. **Start your web server** and navigate to the project directory

## Default Accounts

### Administrator
- **Username:** ADMIN001
- **Password:** password123

### Faculty Members
- **Dr. John Smith:** FAC001 / password123
- **Dr. Jane Doe:** FAC002 / password123

### Students
- **Student One:** 2020-001 / password123
- **Student Two:** 2020-002 / password123
- **Student Three:** 2021-001 / password123
- **Student Four:** 2021-002 / password123

## User Guide

### Getting Started
1. Open your web browser and navigate to the system
2. Use the login page with one of the default accounts above
3. You'll be redirected to the appropriate dashboard based on your role

### Student Workflow
1. Login to view the student dashboard
2. See available exams for your year and section
3. Click "Take Exam" to start an exam
4. Answer questions within the time limit
5. Submit your exam when complete
6. View your score immediately

### Faculty Workflow
1. Login to access the faculty dashboard
2. View your assigned subjects/classes
3. Click on a class to create a new exam
4. Add exam title, instructions, and time limit
5. Add questions (multiple choice or true/false)
6. Review and create the exam
7. Students in that class can now take the exam

### Admin Workflow
1. Login to access the admin dashboard
2. **Manage Users:** Add/edit/delete students and faculty
3. **Manage Subjects:** Create new subjects/courses
4. **Subject Assignments:** Assign faculty to teach specific subjects
5. **Reports:** View system statistics and reports

## File Structure

```
exam-system/
├── api/                    # Backend API files
│   ├── config.php         # Database configuration
│   ├── login.php          # Authentication
│   ├── logout.php         # Session termination
│   ├── check_session.php  # Session validation
│   ├── get_exams.php      # Student exam list
│   ├── get_exam_questions.php # Exam questions
│   ├── submit_exam.php    # Exam submission
│   ├── create_exam.php    # Faculty exam creation
│   ├── get_faculty_subjects.php # Faculty subjects
│   ├── add_user.php       # User management
│   ├── view_all_users.php # User listing
│   ├── delete_user.php    # User deletion
│   ├── add_subject.php    # Subject creation
│   ├── subject_list.php   # Subject listing
│   └── assign_subject.php # Subject assignments
├── login.html             # Login page
├── student_dashboard.html # Student interface
├── faculty_dashboard.html # Faculty interface
├── admin_dashboard.html   # Admin interface
├── take_exam.html         # Exam taking interface
├── database_schema.sql    # Database setup
└── README.md             # This file
```

## Security Features

- Session-based authentication
- Role-based access control
- SQL injection prevention with prepared statements
- Input validation and sanitization
- CORS headers for API security
- Secure password handling

## Database Schema

### Main Tables
- **users** - Student, faculty, and admin accounts
- **subjects** - Course/subject definitions
- **subject_assignments** - Faculty-subject relationships
- **exams** - Exam definitions and metadata
- **questions** - Individual exam questions
- **exam_attempts** - Student exam sessions
- **student_answers** - Individual question responses

## API Endpoints

### Authentication
- `POST /api/login.php` - User login
- `POST /api/logout.php` - User logout
- `GET /api/check_session.php` - Session validation

### Student APIs
- `GET /api/get_exams.php` - Available exams
- `GET /api/get_exam_questions.php` - Exam questions
- `POST /api/submit_exam.php` - Submit exam answers

### Faculty APIs
- `GET /api/get_faculty_subjects.php` - Assigned subjects
- `POST /api/create_exam.php` - Create new exam

### Admin APIs
- `GET /api/view_all_users.php` - All users
- `POST /api/add_user.php` - Create user
- `POST /api/delete_user.php` - Delete user
- `GET /api/subject_list.php` - All subjects
- `POST /api/add_subject.php` - Create subject
- `POST /api/assign_subject.php` - Assign subject to faculty

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Technical Stack

- **Frontend:** HTML5, CSS3 (Tailwind CSS), JavaScript (ES6+)
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Authentication:** PHP Sessions
- **Styling:** Tailwind CSS via CDN

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check `api/config.php` settings
   - Ensure MySQL is running
   - Verify database credentials

2. **Login Issues**
   - Check that the database schema is imported
   - Verify default user accounts exist
   - Check browser console for JavaScript errors

3. **Session Issues**
   - Ensure PHP sessions are enabled
   - Check session timeout settings
   - Clear browser cookies/cache

4. **Permission Errors**
   - Verify web server has read/write access to files
   - Check PHP error logs
   - Ensure proper file permissions

### Log Files
- Check PHP error logs for backend issues
- Use browser developer tools for frontend debugging
- Database query logs can help with SQL issues

## Contributing

1. Follow the existing code structure
2. Use PDO for all database operations
3. Include session validation for protected endpoints
4. Add error handling and logging
5. Test thoroughly across different user roles

## License

This project is for educational purposes. Please refer to your institution's guidelines for usage and distribution.

## Support

For technical support or questions about the system:
1. Check the troubleshooting section above
2. Review the code comments for implementation details
3. Ensure all setup steps were followed correctly

---

**Last Updated:** 2024
**Version:** 1.0