# Faculty Dashboard - Exam System

## Overview
The faculty dashboard has been redesigned to provide a modern, intuitive interface for faculty members to create and manage exams for their assigned classes.

## Features

### Your Classes Section
- **Class Cards**: Each subject assignment is displayed as a card showing:
  - Subject name and course code
  - Year level and section
  - Number of exams created
  - Create Exam button

### Create New Exam Section
- **Exam Details**: Title and instructions input
- **Class Information**: Display of selected subject, year, and section
- **Questions Management**: 
  - Add multiple choice or true/false questions
  - Edit and delete questions
  - Question preview with options and correct answers
  - Points assignment per question

## Database Setup

1. **Import the schema**: Run `database_schema.sql` in your MySQL database
2. **Configure database**: Update `api/config.php` with your database credentials

## File Structure

### Frontend
- `faculty_dashboard.html` - Main dashboard interface

### Backend API
- `api/config.php` - Database configuration
- `api/get_faculty_subjects.php` - Retrieves faculty's assigned subjects
- `api/create_exam.php` - Creates exams with questions
- `api/logout.php` - Handles user logout

### Database Schema
- `database_schema.sql` - Complete database setup

## How to Use

### For Faculty Members

1. **Login**: Access the dashboard after logging in as a faculty member
2. **View Classes**: See all assigned subjects with current exam counts
3. **Create Exam**:
   - Click on any class card to start creating an exam
   - Fill in exam title and instructions
   - Add questions using the "Add Question" button
   - Choose between multiple choice or true/false questions
   - Set points for each question
   - Preview and edit questions before submitting
4. **Submit**: Create the exam when all questions are added

### Question Types Supported

#### Multiple Choice
- Question text
- Four options (A, B, C, D)
- Correct answer selection
- Custom points

#### True/False
- Question text
- True/False answer selection
- Custom points

## Design Features

- **Modern UI**: Clean, professional design with Tailwind CSS
- **Responsive**: Works on desktop, tablet, and mobile devices
- **Interactive**: Hover effects, smooth transitions, and intuitive navigation
- **Real-time Feedback**: Success/error messages for user actions
- **Question Management**: Easy add, edit, and delete functionality

## Technical Implementation

### JavaScript Features
- Async/await for API calls
- Form validation
- Dynamic UI updates
- Question management system
- Error handling with user feedback

### PHP Backend
- PDO for secure database operations
- Transaction support for data integrity
- JSON API responses
- Error logging and handling
- Input validation and sanitization

### Security Features
- Prepared statements to prevent SQL injection
- Input validation and sanitization
- CORS headers for API security
- Session management for logout

## Sample Data

The schema includes sample data:
- 2 faculty members (Dr. John Smith, Dr. Jane Doe)
- 5 subjects (Math, Physics, Chemistry, English, IT)
- Sample subject assignments
- Student accounts for testing

## Default Credentials

Faculty accounts (password: `password123`):
- **Dr. John Smith**: FAC001
- **Dr. Jane Doe**: FAC002

Admin account:
- **Admin User**: ADMIN001

## Browser Compatibility

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Dependencies

- **Frontend**: Tailwind CSS (via CDN)
- **Backend**: PHP 7.4+, MySQL 5.7+
- **Web Server**: Apache/Nginx with PHP support