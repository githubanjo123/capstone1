# Faculty Dashboard Test Suite

A comprehensive test suite for the Faculty Dashboard exam system, covering both frontend JavaScript and backend PHP functionality.

## 📋 Overview

This test suite provides complete coverage for:
- **Frontend JavaScript**: User interactions, form validation, API calls, question management
- **Backend PHP**: API endpoints, database operations, session management
- **Integration**: End-to-end workflow testing from frontend to backend
- **Database**: Schema validation, data integrity, transaction handling

## 🏗️ Test Structure

```
tests/
├── frontend/
│   └── faculty-dashboard.test.js     # Frontend JavaScript tests
├── backend/
│   ├── GetFacultySubjectsTest.php    # API endpoint tests
│   ├── CreateExamTest.php            # Exam creation tests
│   └── LogoutTest.php                # Session management tests
├── integration/
│   └── FacultyDashboardIntegrationTest.php  # End-to-end tests
├── package.json                      # Node.js dependencies
├── phpunit.xml                       # PHPUnit configuration
├── bootstrap.php                     # Test environment setup
├── jest.setup.js                     # Jest configuration
├── run-tests.sh                      # Test runner script
└── README.md                         # This file
```

## 🚀 Quick Start

### Prerequisites

- **Node.js** (v14+) and **npm** (v6+)
- **PHP** (v7.4+)
- **PHPUnit** (v9+)
- **Composer** (for PHP dependencies)

### Install Dependencies

```bash
# Navigate to tests directory
cd tests

# Install Node.js dependencies
npm install

# Install PHP dependencies (if using Composer)
composer install
```

### Run All Tests

```bash
# Make script executable
chmod +x run-tests.sh

# Run all tests
./run-tests.sh

# Run specific test types
./run-tests.sh frontend
./run-tests.sh backend
./run-tests.sh integration
```

## 🧪 Test Categories

### Frontend Tests (Jest)

**File**: `frontend/faculty-dashboard.test.js`

**Coverage**:
- ✅ Utility functions (ordinal suffixes, alerts)
- ✅ Class loading and selection
- ✅ Question management (add, edit, delete)
- ✅ Form validation
- ✅ Exam creation workflow
- ✅ API call handling
- ✅ Error handling
- ✅ Integration workflows

**Key Test Cases**:
```javascript
// Utility function tests
test('getOrdinalSuffix should return correct suffixes')
test('showAlert should create alert element')

// Class management tests
test('loadClasses should fetch faculty subjects')
test('selectClass should return formatted class data')

// Question management tests
test('addQuestion should add question to array')
test('editQuestion should set editing index')
test('deleteQuestion should remove question from array')

// Form validation tests
test('validateExamForm should return errors for empty title')

// Exam creation tests
test('createExam should send POST request with form data')
```

### Backend Tests (PHPUnit)

#### GetFacultySubjectsTest.php

**Coverage**:
- ✅ Faculty subject retrieval
- ✅ Exam count calculation
- ✅ Empty result handling
- ✅ SQL injection prevention
- ✅ Database integrity

**Key Test Cases**:
```php
testGetFacultySubjectsSuccess()           // Normal operation
testGetFacultySubjectsEmptyResult()       // No subjects assigned
testGetFacultySubjectsWithoutExams()      // Subjects with no exams
testSqlInjectionPrevention()              // Security testing
```

#### CreateExamTest.php

**Coverage**:
- ✅ Exam creation with multiple choice questions
- ✅ Exam creation with true/false questions
- ✅ Input validation
- ✅ Transaction rollback on errors
- ✅ JSON parsing
- ✅ Question type validation

**Key Test Cases**:
```php
testCreateExamSuccessWithMultipleChoiceQuestions()  // MC questions
testCreateExamSuccessWithTrueFalseQuestions()       // T/F questions
testTransactionRollbackOnError()                    // Error handling
testJsonQuestionsParsing()                          // Data parsing
```

#### LogoutTest.php

**Coverage**:
- ✅ Session destruction
- ✅ Session cookie clearing
- ✅ Multiple logout calls
- ✅ Empty session handling
- ✅ Security headers

**Key Test Cases**:
```php
testSessionDestroySuccess()           // Normal logout
testLogoutWithEmptySession()          // Already logged out
testMultipleLogoutCalls()             // Idempotent operation
testSessionSecurityHeaders()          // Security verification
```

### Integration Tests

**File**: `integration/FacultyDashboardIntegrationTest.php`

**Coverage**:
- ✅ Complete exam creation workflow
- ✅ Multiple exam creation
- ✅ Error handling workflow
- ✅ Different question types
- ✅ Concurrent exam creation

**Key Test Cases**:
```php
testCompleteExamCreationWorkflow()    // Full end-to-end flow
testMultipleExamCreationWorkflow()    // Multiple exams
testErrorHandlingWorkflow()           // Error scenarios
testDifferentQuestionTypesWorkflow()  // Various question types
testConcurrentExamCreation()          // Concurrent operations
```

## 📊 Coverage Reports

After running tests, coverage reports are generated:

### Frontend Coverage
- **Location**: `test-results-{timestamp}/frontend-coverage/`
- **Format**: HTML report with line-by-line coverage
- **Includes**: Statement, branch, function, and line coverage

### Backend Coverage
- **Location**: `test-results-{timestamp}/backend-coverage/`
- **Format**: HTML report with detailed coverage metrics
- **Includes**: Class, method, and line coverage

## 🔧 Configuration

### Jest Configuration (`package.json`)

```json
{
  "jest": {
    "testEnvironment": "jsdom",
    "setupFilesAfterEnv": ["<rootDir>/jest.setup.js"],
    "collectCoverageFrom": [
      "frontend/**/*.js",
      "!frontend/**/*.test.js"
    ],
    "coverageDirectory": "coverage"
  }
}
```

### PHPUnit Configuration (`phpunit.xml`)

```xml
<phpunit bootstrap="bootstrap.php">
  <testsuites>
    <testsuite name="Faculty Dashboard Backend Tests">
      <directory>backend</directory>
    </testsuite>
    <testsuite name="Integration Tests">
      <directory>integration</directory>
    </testsuite>
  </testsuites>
</phpunit>
```

## 🎯 Custom Test Helpers

### JavaScript Helpers (`jest.setup.js`)

```javascript
// Custom matchers
expect.extend({
  toBeValidResponse(received),
  toBeValidExam(received),
  toBeValidQuestion(received)
});

// Global helpers
global.testHelpers = {
  createMockElement(),
  createMockFetchResponse(),
  createMockExam(),
  createMockClass()
};
```

### PHP Helpers (`bootstrap.php`)

```php
class TestHelpers {
  public static function createTestPDO()
  public static function createTestTables(PDO $pdo)
  public static function insertTestData(PDO $pdo)
  public static function assertValidApiResponse(array $response)
}
```

## 📈 Test Metrics

### Target Coverage Goals
- **Frontend**: >90% line coverage
- **Backend**: >95% line coverage
- **Integration**: 100% critical path coverage

### Performance Benchmarks
- **Frontend tests**: < 30 seconds
- **Backend tests**: < 45 seconds
- **Integration tests**: < 60 seconds
- **Total test suite**: < 2 minutes

## 🐛 Debugging Tests

### Frontend Debugging

```bash
# Run tests in watch mode
npm test -- --watch

# Run specific test file
npm test -- frontend/faculty-dashboard.test.js

# Debug with verbose output
npm test -- --verbose
```

### Backend Debugging

```bash
# Run specific test class
phpunit --filter GetFacultySubjectsTest

# Run with verbose output
phpunit --verbose

# Run with debug information
phpunit --debug
```

## 🔍 Test Data

### Sample Test Data

The test suite uses the following sample data:

**Users**:
- Admin: ADMIN001 (Admin User)
- Faculty: FAC001 (Dr. John Smith), FAC002 (Dr. Jane Doe)
- Students: 2020-001, 2020-002, etc.

**Subjects**:
- MATH101 (Mathematics)
- PHYS101 (Physics)
- CHEM101 (Chemistry)
- ENG101 (English)

**Subject Assignments**:
- Dr. John Smith: Math (3rd Year A), Physics (2nd Year B)
- Dr. Jane Doe: Chemistry (1st Year A), English (2nd Year A)

## 🚨 Common Issues

### Frontend Issues

**Issue**: `ReferenceError: fetch is not defined`
**Solution**: Mock is set up in `jest.setup.js`

**Issue**: `Cannot read property 'getElementById' of undefined`
**Solution**: DOM mocks are configured in setup

### Backend Issues

**Issue**: `PDO connection failed`
**Solution**: Using SQLite in-memory database for tests

**Issue**: `Headers already sent`
**Solution**: Output buffering in test environment

### Integration Issues

**Issue**: `Transaction rollback failed`
**Solution**: Proper transaction handling in test helpers

## 📝 Writing New Tests

### Frontend Test Template

```javascript
describe('New Feature Tests', () => {
  let component;

  beforeEach(() => {
    component = new FeatureComponent();
    jest.clearAllMocks();
  });

  test('should perform expected behavior', () => {
    // Arrange
    const input = 'test input';
    
    // Act
    const result = component.method(input);
    
    // Assert
    expect(result).toBeDefined();
  });
});
```

### Backend Test Template

```php
class NewFeatureTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        $this->pdo = TestHelpers::createTestPDO();
        TestHelpers::createTestTables($this->pdo);
        TestHelpers::insertTestData($this->pdo);
    }

    public function testNewFeature()
    {
        // Arrange
        $input = 'test input';
        
        // Act
        $result = $this->performAction($input);
        
        // Assert
        $this->assertTrue($result['success']);
    }
}
```

## 🤝 Contributing

When adding new tests:

1. **Follow naming conventions**: `test*` for JavaScript, `test*` for PHP
2. **Add meaningful descriptions**: Describe what the test verifies
3. **Use proper setup/teardown**: Clean up after tests
4. **Mock external dependencies**: Don't rely on real APIs/databases
5. **Test edge cases**: Include error scenarios and boundary conditions
6. **Update documentation**: Add new test descriptions to README

## 📋 Test Checklist

Before submitting code:

- [ ] All existing tests pass
- [ ] New features have corresponding tests
- [ ] Edge cases are covered
- [ ] Error scenarios are tested
- [ ] Coverage targets are met
- [ ] Tests run in reasonable time
- [ ] Documentation is updated

## 🏆 Best Practices

1. **Arrange, Act, Assert**: Structure tests clearly
2. **One assertion per test**: Keep tests focused
3. **Descriptive test names**: Make intent clear
4. **Independent tests**: No test dependencies
5. **Mock external services**: Isolate units under test
6. **Clean up resources**: Proper teardown
7. **Test both success and failure**: Cover all paths

## 📞 Support

For questions about the test suite:

1. Check this README for common issues
2. Review existing test examples
3. Check test output logs for detailed errors
4. Ensure all dependencies are properly installed

---

**Happy Testing! 🧪✨**