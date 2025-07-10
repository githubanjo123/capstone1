# Faculty Dashboard Setup Guide

## Problem Fixed: Faculty doesn't load assigned subjects

The issue has been identified and fixed. The faculty dashboard wasn't loading assigned subjects due to missing database setup or configuration issues.

## 🚀 Quick Fix

### Step 1: Database Setup
You need to set up the database with the required tables and sample data.

#### Option A: Manual SQL Setup
1. Open your MySQL client (phpMyAdmin, MySQL Workbench, etc.)
2. Run the SQL commands from `database_schema.sql`
3. This will create all tables and insert sample data

#### Option B: PHP Setup Script (if PHP is available)
```bash
php setup_database.php
```

### Step 2: Verify Database Structure
After setting up the database, verify these tables exist:
- `users` (with faculty records)
- `subjects` (with course information)
- `subject_assignments` (linking faculty to subjects)
- `exams` (for storing exams)
- `questions` (for storing exam questions)

### Step 3: Test the Fix
1. Open `debug_faculty.html` in your browser
2. Click "Debug API" to test the connection
3. Verify faculty and subject data appears
4. Open `faculty_dashboard.html` to see working dashboard

## 🔧 Enhanced Features Added

### Debug Tools
- **Debug API**: `api/debug_faculty_subjects.php` - Provides detailed information about database state
- **Debug Page**: `debug_faculty.html` - Visual tool for testing and troubleshooting
- **Enhanced Error Messages**: Clear feedback about what's missing or wrong

### Faculty Selector
- Added faculty dropdown in dashboard header for easy testing
- Can switch between different faculty members (Dr. John Smith, Dr. Jane Doe)
- Welcome message updates dynamically

### Improved Error Handling
- Detailed error messages with specific instructions
- Browser console logging for debugging
- Retry buttons for failed operations
- Database setup guidance

## 📋 Sample Data Included

### Faculty Users
- **Dr. John Smith** (ID: 2, FAC001)
  - Mathematics - 3rd Year A
  - Physics - 2nd Year B

- **Dr. Jane Doe** (ID: 3, FAC002)
  - Chemistry - 1st Year A
  - English - 2nd Year A

### Subjects
- MATH101 - Mathematics
- PHYS101 - Physics
- CHEM101 - Chemistry
- ENG101 - English
- IT101 - Introduction to Information Technology

## 🐛 Troubleshooting

### Common Issues and Solutions

#### 1. "No classes assigned yet" message
**Cause**: Database not set up or no subject assignments
**Solution**: 
- Run the SQL from `database_schema.sql`
- Check the debug API for detailed information

#### 2. API connection errors
**Cause**: PHP server not running or database not accessible
**Solution**:
- Ensure PHP server is running (XAMPP, WAMP, or local development server)
- Verify MySQL server is running
- Check `api/config.php` database credentials

#### 3. Faculty doesn't exist error
**Cause**: Wrong faculty ID or faculty not in database
**Solution**:
- Use the faculty selector to switch between valid faculty members
- Check debug information for available faculty IDs

#### 4. Database connection failed
**Cause**: Incorrect database credentials or MySQL not running
**Solution**:
- Update `api/config.php` with correct database settings
- Ensure MySQL server is running
- Create 'capstone' database if it doesn't exist

## 🎯 Testing Steps

1. **Database Setup**
   ```sql
   -- Create database
   CREATE DATABASE capstone;
   
   -- Import schema
   USE capstone;
   -- Run all commands from database_schema.sql
   ```

2. **Test Debug API**
   - Open `debug_faculty.html`
   - Should show faculty and subject data
   - No errors in browser console

3. **Test Faculty Dashboard**
   - Open `faculty_dashboard.html`
   - Should show assigned classes for selected faculty
   - Can create exams for assigned subjects

4. **Switch Faculty Members**
   - Use dropdown to switch between Dr. John Smith and Dr. Jane Doe
   - Each should show their respective assigned subjects

## 🔗 Files Modified/Created

### New Files
- `api/debug_faculty_subjects.php` - Debug API endpoint
- `setup_database.php` - Database setup script
- `debug_faculty.html` - Debug testing page
- `FACULTY_DASHBOARD_SETUP.md` - This setup guide

### Modified Files
- `faculty_dashboard.html` - Enhanced with debugging and faculty selector
- `api/get_faculty_subjects.php` - Improved error handling
- `database_schema.sql` - Complete schema with sample data

## ✅ Verification Checklist

- [ ] MySQL server is running
- [ ] Database 'capstone' exists
- [ ] All tables created from schema
- [ ] Sample data inserted (users, subjects, assignments)
- [ ] `debug_faculty.html` shows faculty and subjects
- [ ] `faculty_dashboard.html` loads without errors
- [ ] Can switch between faculty members
- [ ] Assigned subjects display correctly
- [ ] Can create exams for assigned subjects

## 💡 Production Notes

For production deployment:
1. Remove the faculty selector (it's for testing only)
2. Implement proper session management to get faculty ID
3. Disable debug mode by setting `debugMode = false`
4. Remove debug API endpoint for security
5. Use environment variables for database credentials

The faculty dashboard should now work correctly and display assigned subjects for each faculty member! 🎉