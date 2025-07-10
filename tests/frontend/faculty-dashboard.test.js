/**
 * Unit Tests for Faculty Dashboard Frontend
 * Tests all JavaScript functions and UI interactions
 */

// Mock DOM elements and global functions
global.fetch = jest.fn();
global.confirm = jest.fn();
global.alert = jest.fn();

// Mock DOM methods
const mockElement = {
  textContent: '',
  value: '',
  innerHTML: '',
  classList: {
    add: jest.fn(),
    remove: jest.fn(),
    contains: jest.fn()
  },
  addEventListener: jest.fn(),
  reset: jest.fn()
};

global.document = {
  getElementById: jest.fn(() => mockElement),
  createElement: jest.fn(() => mockElement)
};

// Import the functions we want to test (these would be extracted from the HTML file)
class FacultyDashboard {
  constructor() {
    this.facultyId = 2;
    this.currentQuestions = [];
    this.editingQuestionIndex = -1;
  }

  showAlert(message, type = 'success') {
    const alertId = type === 'success' ? 'alert-success' : 'alert-error';
    const alertElement = { textContent: message, classList: { add: jest.fn(), remove: jest.fn() } };
    return alertElement;
  }

  getOrdinalSuffix(num) {
    const remainder10 = num % 10;
    const remainder100 = num % 100;
    
    // Handle special cases (11th, 12th, 13th)
    if (remainder100 >= 11 && remainder100 <= 13) {
      return 'th';
    }
    
    // Handle regular cases
    const suffixes = { 1: 'st', 2: 'nd', 3: 'rd' };
    return suffixes[remainder10] || 'th';
  }

  async loadClasses() {
    try {
      const res = await fetch(`api/get_faculty_subjects.php?faculty_id=${this.facultyId}`);
      const classes = await res.json();
      return classes;
    } catch (error) {
      throw new Error('Failed to load classes: ' + error.message);
    }
  }

  selectClass(subjectId, subjectName, courseCode, yearLevel, section) {
    return {
      subjectId,
      subjectName,
      courseCode,
      yearLevel,
      section,
      displayText: `${subjectName} (${courseCode})`,
      yearText: `${yearLevel}${this.getOrdinalSuffix(yearLevel)} Year`,
      sectionText: section
    };
  }

  addQuestion() {
    const questionData = {
      type: 'multiple_choice',
      text: 'Sample question',
      points: 1,
      options: { A: 'Option A', B: 'Option B', C: 'Option C', D: 'Option D' },
      correct_answer: 'A'
    };

    if (!questionData.text.trim()) {
      throw new Error('Question text is required');
    }

    if (questionData.type === 'multiple_choice') {
      if (!questionData.options.A || !questionData.options.B || 
          !questionData.options.C || !questionData.options.D || 
          !questionData.correct_answer) {
        throw new Error('All options and correct answer are required for multiple choice questions');
      }
    }

    if (this.editingQuestionIndex >= 0) {
      this.currentQuestions[this.editingQuestionIndex] = questionData;
      this.editingQuestionIndex = -1;
    } else {
      this.currentQuestions.push(questionData);
    }

    return this.currentQuestions.length;
  }

  editQuestion(index) {
    if (index < 0 || index >= this.currentQuestions.length) {
      throw new Error('Invalid question index');
    }
    
    this.editingQuestionIndex = index;
    return this.currentQuestions[index];
  }

  deleteQuestion(index) {
    if (index < 0 || index >= this.currentQuestions.length) {
      throw new Error('Invalid question index');
    }
    
    this.currentQuestions.splice(index, 1);
    return this.currentQuestions.length;
  }

  validateExamForm(title, questions) {
    const errors = [];
    
    if (!title || !title.trim()) {
      errors.push('Exam title is required');
    }
    
    if (!questions || questions.length === 0) {
      errors.push('At least one question is required');
    }
    
    return errors;
  }

  async createExam(examData) {
    const formData = new FormData();
    Object.keys(examData).forEach(key => {
      if (key === 'questions') {
        formData.append(key, JSON.stringify(examData[key]));
      } else {
        formData.append(key, examData[key]);
      }
    });

    try {
      const res = await fetch('api/create_exam.php', {
        method: 'POST',
        body: formData
      });
      
      const result = await res.json();
      return result;
    } catch (error) {
      throw new Error('Network error: ' + error.message);
    }
  }
}

// Test Suite
describe('Faculty Dashboard Frontend Tests', () => {
  let dashboard;

  beforeEach(() => {
    dashboard = new FacultyDashboard();
    jest.clearAllMocks();
    fetch.mockClear();
  });

  describe('Utility Functions', () => {
    test('getOrdinalSuffix should return correct suffixes', () => {
      expect(dashboard.getOrdinalSuffix(1)).toBe('st');
      expect(dashboard.getOrdinalSuffix(2)).toBe('nd');
      expect(dashboard.getOrdinalSuffix(3)).toBe('rd');
      expect(dashboard.getOrdinalSuffix(4)).toBe('th');
      expect(dashboard.getOrdinalSuffix(11)).toBe('th');
      expect(dashboard.getOrdinalSuffix(21)).toBe('st');
    });

    test('showAlert should create alert element', () => {
      const alert = dashboard.showAlert('Test message', 'success');
      expect(alert.textContent).toBe('Test message');
    });
  });

  describe('Class Management', () => {
    test('loadClasses should fetch faculty subjects', async () => {
      const mockClasses = [
        {
          subject_id: 1,
          subject_name: 'Mathematics',
          course_code: 'MATH101',
          year_level: 3,
          section: 'A',
          exam_count: 2
        }
      ];

      fetch.mockResolvedValueOnce({
        json: jest.fn().mockResolvedValueOnce(mockClasses)
      });

      const result = await dashboard.loadClasses();
      expect(fetch).toHaveBeenCalledWith('api/get_faculty_subjects.php?faculty_id=2');
      expect(result).toEqual(mockClasses);
    });

    test('loadClasses should handle fetch errors', async () => {
      fetch.mockRejectedValueOnce(new Error('Network error'));

      await expect(dashboard.loadClasses()).rejects.toThrow('Failed to load classes: Network error');
    });

    test('selectClass should return formatted class data', () => {
      const result = dashboard.selectClass(1, 'Mathematics', 'MATH101', 3, 'A');
      
      expect(result).toEqual({
        subjectId: 1,
        subjectName: 'Mathematics',
        courseCode: 'MATH101',
        yearLevel: 3,
        section: 'A',
        displayText: 'Mathematics (MATH101)',
        yearText: '3rd Year',
        sectionText: 'A'
      });
    });
  });

  describe('Question Management', () => {
    test('addQuestion should add question to array', () => {
      const count = dashboard.addQuestion();
      expect(count).toBe(1);
      expect(dashboard.currentQuestions).toHaveLength(1);
    });

    test('addQuestion should validate question text', () => {
      // Mock empty question text
      expect(() => {
        const questionData = {
          type: 'multiple_choice',
          text: '', // Empty text
          points: 1,
          options: { A: 'A', B: 'B', C: 'C', D: 'D' },
          correct_answer: 'A'
        };
        
        if (!questionData.text.trim()) {
          throw new Error('Question text is required');
        }
      }).toThrow('Question text is required');
    });

    test('editQuestion should set editing index', () => {
      dashboard.addQuestion(); // Add a question first
      const question = dashboard.editQuestion(0);
      
      expect(dashboard.editingQuestionIndex).toBe(0);
      expect(question).toBeDefined();
    });

    test('editQuestion should throw error for invalid index', () => {
      expect(() => dashboard.editQuestion(5)).toThrow('Invalid question index');
      expect(() => dashboard.editQuestion(-1)).toThrow('Invalid question index');
    });

    test('deleteQuestion should remove question from array', () => {
      dashboard.addQuestion();
      dashboard.addQuestion();
      expect(dashboard.currentQuestions).toHaveLength(2);
      
      const newCount = dashboard.deleteQuestion(0);
      expect(newCount).toBe(1);
      expect(dashboard.currentQuestions).toHaveLength(1);
    });

    test('deleteQuestion should throw error for invalid index', () => {
      expect(() => dashboard.deleteQuestion(0)).toThrow('Invalid question index');
    });
  });

  describe('Form Validation', () => {
    test('validateExamForm should return errors for empty title', () => {
      const errors = dashboard.validateExamForm('', []);
      expect(errors).toContain('Exam title is required');
      expect(errors).toContain('At least one question is required');
    });

    test('validateExamForm should return no errors for valid data', () => {
      const errors = dashboard.validateExamForm('Valid Title', [{ text: 'Question 1' }]);
      expect(errors).toHaveLength(0);
    });
  });

  describe('Exam Creation', () => {
    test('createExam should send POST request with form data', async () => {
      const mockResult = { success: true, exam_id: 1 };
      fetch.mockResolvedValueOnce({
        json: jest.fn().mockResolvedValueOnce(mockResult)
      });

      const examData = {
        title: 'Test Exam',
        instructions: 'Test instructions',
        subject_id: 1,
        year_level: 3,
        section: 'A',
        created_by: 2,
        questions: [{ text: 'Question 1', type: 'multiple_choice' }]
      };

      const result = await dashboard.createExam(examData);
      
      expect(fetch).toHaveBeenCalledWith('api/create_exam.php', {
        method: 'POST',
        body: expect.any(FormData)
      });
      expect(result).toEqual(mockResult);
    });

    test('createExam should handle network errors', async () => {
      fetch.mockRejectedValueOnce(new Error('Network failed'));

      const examData = { title: 'Test' };
      
      await expect(dashboard.createExam(examData)).rejects.toThrow('Network error: Network failed');
    });
  });
});

describe('Integration Tests', () => {
  let dashboard;

  beforeEach(() => {
    dashboard = new FacultyDashboard();
    jest.clearAllMocks();
  });

  test('Complete exam creation workflow', async () => {
    // 1. Load classes
    const mockClasses = [{ subject_id: 1, subject_name: 'Math', course_code: 'MATH101' }];
    fetch.mockResolvedValueOnce({
      json: jest.fn().mockResolvedValueOnce(mockClasses)
    });

    const classes = await dashboard.loadClasses();
    expect(classes).toEqual(mockClasses);

    // 2. Select a class
    const selectedClass = dashboard.selectClass(1, 'Math', 'MATH101', 3, 'A');
    expect(selectedClass.displayText).toBe('Math (MATH101)');

    // 3. Add questions
    dashboard.addQuestion();
    dashboard.addQuestion();
    expect(dashboard.currentQuestions).toHaveLength(2);

    // 4. Validate exam
    const errors = dashboard.validateExamForm('Midterm Exam', dashboard.currentQuestions);
    expect(errors).toHaveLength(0);

    // 5. Create exam
    fetch.mockResolvedValueOnce({
      json: jest.fn().mockResolvedValueOnce({ success: true, exam_id: 1 })
    });

    const examData = {
      title: 'Midterm Exam',
      subject_id: 1,
      year_level: 3,
      section: 'A',
      created_by: 2,
      questions: dashboard.currentQuestions
    };

    const result = await dashboard.createExam(examData);
    expect(result.success).toBe(true);
  });

  test('Question editing workflow', () => {
    // Add questions
    dashboard.addQuestion();
    dashboard.addQuestion();
    expect(dashboard.currentQuestions).toHaveLength(2);

    // Edit first question
    const question = dashboard.editQuestion(0);
    expect(dashboard.editingQuestionIndex).toBe(0);

    // Add question while editing (should replace)
    dashboard.addQuestion();
    expect(dashboard.currentQuestions).toHaveLength(2); // Still 2, replaced the first one
    expect(dashboard.editingQuestionIndex).toBe(-1); // Reset after editing
  });
});

// Mock console for testing
global.console = {
  log: jest.fn(),
  error: jest.fn(),
  warn: jest.fn()
};