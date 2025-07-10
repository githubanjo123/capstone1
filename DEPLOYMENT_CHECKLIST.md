# Deployment Checklist

Use this checklist to ensure your exam management system is properly set up and configured.

## ✅ Pre-Deployment Setup

### Database Configuration
- [ ] MySQL/MariaDB is installed and running
- [ ] Database named `capstone` has been created
- [ ] `database_schema.sql` has been imported successfully
- [ ] Database connection credentials are correct in `api/config.php`
- [ ] Sample data (users, subjects, assignments) is present in the database

### Web Server Setup
- [ ] PHP 7.4+ is installed and configured
- [ ] Web server (Apache/Nginx) is running
- [ ] Project files are placed in the web server directory
- [ ] PHP sessions are enabled
- [ ] PDO MySQL extension is installed

### File Permissions
- [ ] Web server has read access to all project files
- [ ] PHP can write to session directory
- [ ] Error logging is configured (optional but recommended)

## ✅ Core Functionality Tests

### Authentication System
- [ ] Login page loads without errors
- [ ] Admin login works (ADMIN001 / password123)
- [ ] Faculty login works (FAC001 / password123)
- [ ] Student login works (2020-001 / password123)
- [ ] Logout functionality works for all roles
- [ ] Session validation prevents unauthorized access

### Student Features
- [ ] Student dashboard loads and shows welcome message
- [ ] Available exams are displayed correctly
- [ ] Exam taking interface works
- [ ] Timer functions correctly during exams
- [ ] Exam submission works and shows score
- [ ] Previously taken exams show "Completed" status

### Faculty Features
- [ ] Faculty dashboard loads and shows assigned subjects
- [ ] Exam creation form works
- [ ] Questions can be added (multiple choice and true/false)
- [ ] Questions can be edited and deleted
- [ ] Exams are created successfully
- [ ] Created exams appear for appropriate students

### Admin Features
- [ ] Admin dashboard loads with all sections
- [ ] User management (add/edit/delete) works
- [ ] Subject management works
- [ ] Subject assignments to faculty work
- [ ] User statistics and filtering work

## ✅ Security Verification

### Session Management
- [ ] Unauthenticated users are redirected to login
- [ ] Users cannot access pages outside their role
- [ ] Session expires appropriately
- [ ] Multiple concurrent sessions work correctly

### Data Protection
- [ ] SQL injection prevention (using prepared statements)
- [ ] Input validation works on all forms
- [ ] Error messages don't reveal sensitive information
- [ ] Password fields are properly handled

## ✅ User Interface Tests

### Responsive Design
- [ ] System works on desktop browsers
- [ ] Mobile interface is functional
- [ ] Tailwind CSS loads correctly
- [ ] All icons and styling appear correctly

### User Experience
- [ ] Navigation is intuitive
- [ ] Error messages are helpful
- [ ] Success confirmations appear
- [ ] Loading states are handled gracefully

## ✅ Performance Verification

### Database Performance
- [ ] Page load times are acceptable
- [ ] Database queries are optimized
- [ ] Large user lists load efficiently
- [ ] Exam submissions are processed quickly

### Browser Compatibility
- [ ] Chrome/Chromium works correctly
- [ ] Firefox works correctly
- [ ] Safari works correctly (if available)
- [ ] Edge works correctly

## ✅ Final Production Setup

### Configuration Review
- [ ] Database credentials are secure
- [ ] Default passwords have been changed (production)
- [ ] PHP error reporting is configured appropriately
- [ ] CORS settings are properly configured

### Backup Strategy
- [ ] Database backup plan is in place
- [ ] File backup plan is established
- [ ] Recovery procedures are documented

### Monitoring
- [ ] Error logging is working
- [ ] Performance monitoring is set up (optional)
- [ ] User activity can be tracked (optional)

## Common Issues and Solutions

### Database Connection Failed
```
Check api/config.php settings
Verify MySQL service is running
Test database connection manually
```

### Sessions Not Working
```
Verify PHP session configuration
Check file permissions for session directory
Clear browser cache and cookies
```

### Styling Issues
```
Verify internet connection for Tailwind CSS CDN
Check browser console for CSS errors
Ensure HTML markup is correct
```

### Permission Denied Errors
```
Check web server user permissions
Verify PHP configuration
Test with simple PHP info page
```

## Quick Test Procedure

1. **Login Test**: Try logging in with each role
2. **Student Test**: Take a sample exam as a student
3. **Faculty Test**: Create a simple exam with 2-3 questions
4. **Admin Test**: Add a new user and subject
5. **End-to-End Test**: Complete full workflow from admin setup to student exam

## Support Resources

- Check PHP error logs: `/var/log/php_errors.log` (location varies)
- Browser developer tools for frontend debugging
- MySQL query logs for database issues
- Network tab for API request/response debugging

---

✅ **System Ready**: All checks passed
❌ **Needs Attention**: Issues found, see solutions above
⚠️ **Partial**: Some features working, others need attention