#!/bin/bash

# Faculty Dashboard Test Runner
# Runs all unit tests for the exam system

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test configuration
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
TEST_RESULTS_DIR="test-results-${TIMESTAMP}"
COVERAGE_DIR="coverage"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Faculty Dashboard Test Suite${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Create results directory
mkdir -p "$TEST_RESULTS_DIR"
mkdir -p "$COVERAGE_DIR"

# Check if required tools are installed
check_dependencies() {
    echo -e "${YELLOW}Checking dependencies...${NC}"
    
    # Check for Node.js and npm
    if ! command -v node &> /dev/null; then
        echo -e "${RED}Error: Node.js is not installed${NC}"
        echo "Please install Node.js to run frontend tests"
        exit 1
    fi
    
    if ! command -v npm &> /dev/null; then
        echo -e "${RED}Error: npm is not installed${NC}"
        echo "Please install npm to run frontend tests"
        exit 1
    fi
    
    # Check for PHP
    if ! command -v php &> /dev/null; then
        echo -e "${RED}Error: PHP is not installed${NC}"
        echo "Please install PHP to run backend tests"
        exit 1
    fi
    
    # Check for PHPUnit
    if ! command -v phpunit &> /dev/null && [ ! -f "vendor/bin/phpunit" ]; then
        echo -e "${YELLOW}Warning: PHPUnit not found globally${NC}"
        echo "Attempting to install via Composer..."
        
        if command -v composer &> /dev/null; then
            composer require --dev phpunit/phpunit
        else
            echo -e "${RED}Error: Composer not found. Please install PHPUnit${NC}"
            exit 1
        fi
    fi
    
    echo -e "${GREEN}✓ All dependencies found${NC}"
    echo ""
}

# Install frontend dependencies
setup_frontend() {
    echo -e "${YELLOW}Setting up frontend test environment...${NC}"
    
    if [ ! -d "node_modules" ]; then
        echo "Installing Node.js dependencies..."
        npm install
    fi
    
    echo -e "${GREEN}✓ Frontend setup complete${NC}"
    echo ""
}

# Run frontend tests
run_frontend_tests() {
    echo -e "${BLUE}Running Frontend Tests (Jest)${NC}"
    echo "================================"
    
    # Set test environment
    export NODE_ENV=test
    
    # Run Jest tests with coverage
    if npm test -- --coverage --ci --watchAll=false --testResultsProcessor=jest-junit 2>&1 | tee "$TEST_RESULTS_DIR/frontend-tests.log"; then
        echo -e "${GREEN}✓ Frontend tests passed${NC}"
        frontend_passed=true
    else
        echo -e "${RED}✗ Frontend tests failed${NC}"
        frontend_passed=false
    fi
    
    # Copy coverage reports
    if [ -d "coverage" ]; then
        cp -r coverage "$TEST_RESULTS_DIR/frontend-coverage"
    fi
    
    echo ""
}

# Run backend tests  
run_backend_tests() {
    echo -e "${BLUE}Running Backend Tests (PHPUnit)${NC}"
    echo "================================"
    
    # Determine PHPUnit command
    if command -v phpunit &> /dev/null; then
        PHPUNIT_CMD="phpunit"
    else
        PHPUNIT_CMD="vendor/bin/phpunit"
    fi
    
    # Run PHPUnit tests
    if $PHPUNIT_CMD --configuration phpunit.xml --coverage-html "$TEST_RESULTS_DIR/backend-coverage" --log-junit "$TEST_RESULTS_DIR/backend-junit.xml" 2>&1 | tee "$TEST_RESULTS_DIR/backend-tests.log"; then
        echo -e "${GREEN}✓ Backend tests passed${NC}"
        backend_passed=true
    else
        echo -e "${RED}✗ Backend tests failed${NC}"
        backend_passed=false
    fi
    
    echo ""
}

# Run integration tests
run_integration_tests() {
    echo -e "${BLUE}Running Integration Tests${NC}"
    echo "========================="
    
    # Run only integration tests
    if $PHPUNIT_CMD --configuration phpunit.xml --testsuite "Integration Tests" 2>&1 | tee "$TEST_RESULTS_DIR/integration-tests.log"; then
        echo -e "${GREEN}✓ Integration tests passed${NC}"
        integration_passed=true
    else
        echo -e "${RED}✗ Integration tests failed${NC}"
        integration_passed=false
    fi
    
    echo ""
}

# Generate test report
generate_report() {
    echo -e "${BLUE}Generating Test Report${NC}"
    echo "======================"
    
    REPORT_FILE="$TEST_RESULTS_DIR/test-summary.html"
    
    cat > "$REPORT_FILE" << EOF
<!DOCTYPE html>
<html>
<head>
    <title>Faculty Dashboard Test Results - $TIMESTAMP</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f0f0f0; padding: 20px; border-radius: 5px; }
        .passed { color: #28a745; }
        .failed { color: #dc3545; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .metrics { display: flex; gap: 20px; margin: 10px 0; }
        .metric { padding: 10px; background: #f8f9fa; border-radius: 3px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Faculty Dashboard Test Results</h1>
        <p><strong>Timestamp:</strong> $TIMESTAMP</p>
        <p><strong>Environment:</strong> $(uname -a)</p>
    </div>
    
    <div class="section">
        <h2>Test Summary</h2>
        <div class="metrics">
            <div class="metric">
                <strong>Frontend Tests:</strong> 
                <span class="$([ "$frontend_passed" = true ] && echo 'passed' || echo 'failed')">
                    $([ "$frontend_passed" = true ] && echo 'PASSED' || echo 'FAILED')
                </span>
            </div>
            <div class="metric">
                <strong>Backend Tests:</strong> 
                <span class="$([ "$backend_passed" = true ] && echo 'passed' || echo 'failed')">
                    $([ "$backend_passed" = true ] && echo 'PASSED' || echo 'FAILED')
                </span>
            </div>
            <div class="metric">
                <strong>Integration Tests:</strong> 
                <span class="$([ "$integration_passed" = true ] && echo 'passed' || echo 'failed')">
                    $([ "$integration_passed" = true ] && echo 'PASSED' || echo 'FAILED')
                </span>
            </div>
        </div>
    </div>
    
    <div class="section">
        <h2>Coverage Reports</h2>
        <ul>
            <li><a href="frontend-coverage/index.html">Frontend Coverage Report</a></li>
            <li><a href="backend-coverage/index.html">Backend Coverage Report</a></li>
        </ul>
    </div>
    
    <div class="section">
        <h2>Detailed Logs</h2>
        <ul>
            <li><a href="frontend-tests.log">Frontend Test Log</a></li>
            <li><a href="backend-tests.log">Backend Test Log</a></li>
            <li><a href="integration-tests.log">Integration Test Log</a></li>
        </ul>
    </div>
    
    <div class="section">
        <h2>System Information</h2>
        <pre>
Node.js Version: $(node --version 2>/dev/null || echo "Not available")
npm Version: $(npm --version 2>/dev/null || echo "Not available")
PHP Version: $(php --version | head -n1 2>/dev/null || echo "Not available")
PHPUnit Version: $($PHPUNIT_CMD --version 2>/dev/null || echo "Not available")
        </pre>
    </div>
</body>
</html>
EOF

    echo -e "${GREEN}✓ Test report generated: $REPORT_FILE${NC}"
    echo ""
}

# Print summary
print_summary() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}Test Execution Summary${NC}"
    echo -e "${BLUE}========================================${NC}"
    
    if [ "$frontend_passed" = true ]; then
        echo -e "${GREEN}✓ Frontend Tests: PASSED${NC}"
    else
        echo -e "${RED}✗ Frontend Tests: FAILED${NC}"
    fi
    
    if [ "$backend_passed" = true ]; then
        echo -e "${GREEN}✓ Backend Tests: PASSED${NC}"
    else
        echo -e "${RED}✗ Backend Tests: FAILED${NC}"
    fi
    
    if [ "$integration_passed" = true ]; then
        echo -e "${GREEN}✓ Integration Tests: PASSED${NC}"
    else
        echo -e "${RED}✗ Integration Tests: FAILED${NC}"
    fi
    
    echo ""
    echo -e "${YELLOW}Results saved to: $TEST_RESULTS_DIR${NC}"
    
    if [ "$frontend_passed" = true ] && [ "$backend_passed" = true ] && [ "$integration_passed" = true ]; then
        echo -e "${GREEN}🎉 All tests passed!${NC}"
        exit 0
    else
        echo -e "${RED}❌ Some tests failed. Check the logs for details.${NC}"
        exit 1
    fi
}

# Cleanup function
cleanup() {
    echo -e "${YELLOW}Cleaning up...${NC}"
    # Clean up any temporary files if needed
}

# Trap cleanup on exit
trap cleanup EXIT

# Main execution
main() {
    check_dependencies
    setup_frontend
    
    # Initialize test results
    frontend_passed=false
    backend_passed=false
    integration_passed=false
    
    # Run tests based on arguments
    if [ "$#" -eq 0 ] || [[ "$*" == *"frontend"* ]]; then
        run_frontend_tests
    fi
    
    if [ "$#" -eq 0 ] || [[ "$*" == *"backend"* ]]; then
        run_backend_tests
    fi
    
    if [ "$#" -eq 0 ] || [[ "$*" == *"integration"* ]]; then
        run_integration_tests
    fi
    
    generate_report
    print_summary
}

# Parse command line arguments
case "${1:-all}" in
    "frontend")
        main frontend
        ;;
    "backend")
        main backend
        ;;
    "integration")
        main integration
        ;;
    "all"|*)
        main
        ;;
esac