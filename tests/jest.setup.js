/**
 * Jest Setup File
 * Configures the testing environment for Faculty Dashboard tests
 */

// Global test configuration
global.console = {
  ...console,
  // Suppress console.log during tests unless needed
  log: process.env.NODE_ENV === 'test' ? jest.fn() : console.log,
  debug: process.env.NODE_ENV === 'test' ? jest.fn() : console.debug,
  info: process.env.NODE_ENV === 'test' ? jest.fn() : console.info,
  warn: console.warn,
  error: console.error,
};

// Mock global objects that don't exist in Node.js
global.window = global.window || {
  location: {
    href: 'http://localhost',
    reload: jest.fn()
  }
};

global.document = global.document || {
  getElementById: jest.fn(),
  createElement: jest.fn(),
  addEventListener: jest.fn(),
  querySelector: jest.fn(),
  querySelectorAll: jest.fn()
};

// Mock localStorage
const localStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn(),
  length: 0,
  key: jest.fn()
};
global.localStorage = localStorageMock;

// Mock sessionStorage
const sessionStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn(),
  length: 0,
  key: jest.fn()
};
global.sessionStorage = sessionStorageMock;

// Mock fetch API
global.fetch = jest.fn();

// Mock alert, confirm, prompt
global.alert = jest.fn();
global.confirm = jest.fn();
global.prompt = jest.fn();

// Mock setTimeout and setInterval for consistent testing
jest.useFakeTimers();

// Custom matchers for better testing
expect.extend({
  toBeValidResponse(received) {
    const pass = received && 
                 typeof received === 'object' && 
                 typeof received.success === 'boolean' &&
                 typeof received.message === 'string';
    
    if (pass) {
      return {
        message: () => `expected ${JSON.stringify(received)} not to be a valid API response`,
        pass: true,
      };
    } else {
      return {
        message: () => `expected ${JSON.stringify(received)} to be a valid API response with 'success' (boolean) and 'message' (string) properties`,
        pass: false,
      };
    }
  },
  
  toBeValidExam(received) {
    const pass = received &&
                 typeof received === 'object' &&
                 typeof received.title === 'string' &&
                 received.title.length > 0 &&
                 Array.isArray(received.questions) &&
                 received.questions.length > 0;
    
    if (pass) {
      return {
        message: () => `expected ${JSON.stringify(received)} not to be a valid exam`,
        pass: true,
      };
    } else {
      return {
        message: () => `expected ${JSON.stringify(received)} to be a valid exam with title and questions`,
        pass: false,
      };
    }
  },
  
  toBeValidQuestion(received) {
    const pass = received &&
                 typeof received === 'object' &&
                 typeof received.text === 'string' &&
                 received.text.length > 0 &&
                 typeof received.type === 'string' &&
                 ['multiple_choice', 'true_false'].includes(received.type) &&
                 typeof received.correct_answer === 'string';
    
    if (pass) {
      return {
        message: () => `expected ${JSON.stringify(received)} not to be a valid question`,
        pass: true,
      };
    } else {
      return {
        message: () => `expected ${JSON.stringify(received)} to be a valid question`,
        pass: false,
      };
    }
  }
});

// Global test helpers
global.testHelpers = {
  // Create mock DOM element
  createMockElement: (options = {}) => ({
    textContent: options.textContent || '',
    value: options.value || '',
    innerHTML: options.innerHTML || '',
    classList: {
      add: jest.fn(),
      remove: jest.fn(),
      contains: jest.fn(() => false),
      toggle: jest.fn()
    },
    addEventListener: jest.fn(),
    removeEventListener: jest.fn(),
    setAttribute: jest.fn(),
    getAttribute: jest.fn(() => null),
    ...options
  }),
  
  // Create mock fetch response
  createMockFetchResponse: (data, options = {}) => ({
    ok: options.ok !== false,
    status: options.status || 200,
    statusText: options.statusText || 'OK',
    json: jest.fn().mockResolvedValue(data),
    text: jest.fn().mockResolvedValue(JSON.stringify(data)),
    headers: new Map(Object.entries(options.headers || {}))
  }),
  
  // Create mock exam data
  createMockExam: (overrides = {}) => ({
    title: 'Test Exam',
    instructions: 'Test instructions',
    subject_id: 1,
    year_level: 3,
    section: 'A',
    created_by: 2,
    questions: [
      {
        text: 'What is 2 + 2?',
        type: 'multiple_choice',
        options: { A: '3', B: '4', C: '5', D: '6' },
        correct_answer: 'B',
        points: 1
      }
    ],
    ...overrides
  }),
  
  // Create mock class data
  createMockClass: (overrides = {}) => ({
    subject_id: 1,
    subject_name: 'Mathematics',
    course_code: 'MATH101',
    year_level: 3,
    section: 'A',
    exam_count: 0,
    ...overrides
  }),
  
  // Wait for async operations
  waitFor: (condition, timeout = 1000) => {
    return new Promise((resolve, reject) => {
      const startTime = Date.now();
      const check = () => {
        if (condition()) {
          resolve();
        } else if (Date.now() - startTime > timeout) {
          reject(new Error('Timeout waiting for condition'));
        } else {
          setTimeout(check, 10);
        }
      };
      check();
    });
  }
};

// Setup and teardown helpers
beforeEach(() => {
  // Clear all mocks before each test
  jest.clearAllMocks();
  
  // Reset localStorage and sessionStorage
  localStorageMock.clear();
  sessionStorageMock.clear();
  
  // Reset fetch mock
  fetch.mockClear();
  
  // Reset timers
  jest.clearAllTimers();
});

afterEach(() => {
  // Clean up any remaining timers
  jest.runOnlyPendingTimers();
  jest.useRealTimers();
  jest.useFakeTimers();
});

// Global error handler for unhandled promise rejections
process.on('unhandledRejection', (reason, promise) => {
  console.error('Unhandled Rejection at:', promise, 'reason:', reason);
});

// Suppress ResizeObserver warnings in tests
global.ResizeObserver = jest.fn().mockImplementation(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
  disconnect: jest.fn(),
}));

// Mock IntersectionObserver
global.IntersectionObserver = jest.fn().mockImplementation(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
  disconnect: jest.fn(),
}));

// Set test environment variables
process.env.NODE_ENV = 'test';
process.env.API_BASE_URL = 'http://localhost';

console.log('Jest setup completed - Faculty Dashboard test environment ready');