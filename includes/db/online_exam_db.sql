-- users table to store online examination system users 
CREATE TABLE users (
    user_id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Instructor', 'Student') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Active', 'Inactive') DEFAULT 'Active'
);

-- insert user

INSERT INTO users (user_id, name, email, password, role, status)
VALUES 
('U001', 'Alice Johnson', 'alice@example.com', 'alice@example.com', 'Student', 'Active'),
('U002', 'Bob Smith', 'bob@example.com', 'bob@example.com', 'Student', 'Active'),
('U003', 'Emily Carter', 'emily@example.com', 'emily@example.com', 'Instructor', 'Active'),
('U004', 'John Miller', 'john@example.com', 'john@example.com', 'Instructor', 'Inactive'),
('U005', 'Admin User', 'admin@example.com', 'admin@example.com', 'Admin', 'Active');

-- course

CREATE TABLE courses (
    course_id VARCHAR(50) PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- insert course

INSERT INTO courses (course_id, course_name)
VALUES 
('C001', 'Introduction to Programming'),
('C002', 'Data Structures and Algorithms'),
('C003', 'Database Systems'),
('C004', 'Web Development'),
('C005', 'Software Engineering Principles');

-- assigned students

CREATE TABLE assigned_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    course_id VARCHAR(50) NOT NULL,
    assigned_on DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',

    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
);

-- assign students

INSERT INTO assigned_students (student_id, course_id)
VALUES 
('U001', 'C001'),
('U001', 'C002'),
('U002', 'C001'),
('U002', 'C003'),
('U001', 'C004'),
('U002', 'C004');


-- assigned instructor

CREATE TABLE assigned_instructors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id VARCHAR(50) NOT NULL,
    course_id VARCHAR(50) NOT NULL,
    assigned_on DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',

    FOREIGN KEY (instructor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
);

-- insert instructor

INSERT INTO assigned_instructors (instructor_id, course_id)
VALUES 
('U003', 'C001'),
('U003', 'C002'),
('U003', 'C003'),
('U004', 'C004'),
('U004', 'C005');

-- exams table

CREATE TABLE exams (
    exam_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id VARCHAR(50) NOT NULL,
    instructor_id VARCHAR(50) NOT NULL,
    exam_title VARCHAR(100) NOT NULL,
    duration_minutes INT NOT NULL,
    total_marks INT NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Inactive',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- insert exams
INSERT INTO exams (course_id, instructor_id, exam_title, duration_minutes, total_marks, status)
VALUES 
('CS101', 'INST1001', 'Midterm Exam - Programming', 90, 100, 'Active');

-- insert question 
INSERT INTO questions (exam_id, question_text, question_type, correct_answer, marks)
VALUES 
(7, 'What is the output of 2 + 2 in JavaScript?', 'multiple_choice', '4', 5),
(7, 'Java is a statically typed language. True or False?', 'true_false', 'True', 3),
(7, 'Fill in the blank: The ___ tag is used to define JavaScript in HTML.', 'fill_blank', 'script', 4);
-- insert options
INSERT INTO question_options (question_id, option_text)
VALUES 
(10, '3'),
(10, '4'),
(10, '22'),
(10, 'undefined');


-- insert exams

INSERT INTO exams (course_id, instructor_id, exam_title, duration_minutes, total_marks, status)
VALUES 
('C001', 'U003', 'Midterm Exam - Programming', 90, 100, 'Active'),
('C002', 'U003', 'Final Exam - Algorithms', 120, 100, 'Inactive'),
('C003', 'U003', 'Quiz - Database Basics', 45, 50, 'Active'),
('C004', 'U004', 'Web Development Assessment', 60, 75, 'Active'),
('C005', 'U004', 'Software Engineering Final', 120, 100, 'Inactive');


-- questions table 

CREATE TABLE questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'fill_blank') NOT NULL,
    correct_answer TEXT NOT NULL,  -- stores correct answer (value or keyword)
    marks INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE
);

-- insert question 

INSERT INTO questions (exam_id, question_text, question_type, correct_answer, marks)
VALUES
-- Multiple Choice
(1, 'Which of the following is a programming language?', 'multiple_choice', 'Python', 5),

-- True/False
(1, 'HTML is used to style web pages.', 'true_false', 'False', 5),

-- Fill in the Blank
(1, 'The keyword used to define a function in Python is _____.', 'fill_blank', 'def', 5);

-- option table for multiple choice

CREATE TABLE question_options (
    option_id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    option_text TEXT NOT NULL,

    FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE
);

-- insert options 

INSERT INTO question_options (question_id, option_text)
VALUES
(1, 'Python'),
(1, 'HTTP'),
(1, 'CSS'),
(1, 'MySQL');


-- student answer 

CREATE TABLE student_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    exam_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_text TEXT,  -- the student's answer (text or option)
    is_correct BOOLEAN,  -- optional: store result after evaluation
    answered_on DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE,

    UNIQUE (student_id, exam_id, question_id)  -- one answer per question per student
);

-- insert student answer

INSERT INTO student_answers (student_id, exam_id, question_id, answer_text, is_correct)
VALUES 
-- Student U001 answers
('U001', 1, 1, 'Option A', TRUE),
('U001', 1, 2, 'True', FALSE),
('U001', 1, 3, 'Inheritance', TRUE),

-- Student U002 answers
('U002', 1, 1, 'Option C', FALSE),
('U002', 1, 2, 'False', TRUE),
('U002', 1, 3, 'Encapsulation', FALSE);
-- student exam status 

CREATE TABLE student_exam_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    exam_id INT NOT NULL,
    has_taken BOOLEAN DEFAULT FALSE,
    taken_on DATETIME,
    score FLOAT,  -- store exam result

    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE,

    UNIQUE (student_id, exam_id)  -- prevent duplicate entries
);

-- insert student exam status 

INSERT INTO student_exam_status (student_id, exam_id, has_taken, taken_on, score)
VALUES 
('U001', 1, TRUE, '2025-05-10 09:00:00', 85),
('U002', 1, TRUE, '2025-05-10 09:15:00', 90),
('U003', 2, FALSE, NULL, NULL),  -- Student has not taken this exam
('U004', 2, TRUE, '2025-05-10 10:00:00', 78),
('U005', 1, TRUE, '2025-05-10 09:30:00', 95);


-- exam schedule

CREATE TABLE exam_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    scheduled_date DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE
);

-- insert exam schedule

INSERT INTO exam_schedules (exam_id, scheduled_date)
VALUES 
(1, '2025-05-15 09:00:00'),
(2, '2025-05-16 14:00:00'),
(3, '2025-05-17 10:30:00');


-- feedbacks table

CREATE TABLE feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    exam_id INT NOT NULL,
    feedback_text TEXT,
    rate INT CHECK (rate BETWEEN 1 AND 5),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE
);

-- insert feedback

INSERT INTO feedbacks (student_id, exam_id, feedback_text, rate)
VALUES 
('U001', 1, 'The exam was challenging but fair. I appreciate the variety of questions.', 4),
('U002', 1, 'The exam was too long. It would be helpful to have more time.', 3),
('U003', 2, 'I enjoyed the exam. The questions were well-structured and clear.', 5),
('U004', 2, 'Some of the questions were unclear, which made it difficult to answer.', 2),
('U005', 1, 'Good exam overall, but I think some questions could be improved for clarity.', 4);


-- / indexing for better performance

CREATE INDEX idx_exam_id ON questions(exam_id);
CREATE INDEX idx_question_id ON question_options(question_id);
CREATE INDEX idx_student_exam ON student_exam_status(student_id, exam_id);


