
use softexam_db;
-- Drop tables if they exist
DROP TABLE IF EXISTS student_answers, student_exams, choices, questions, exams, exam_schedule, instructor_courses, courses, users;

-- Users
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin', 'instructor', 'student'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Courses
CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(100),
    academic_year YEAR,
    semester ENUM('I', 'II')
);

-- Instructor-Course assignments
CREATE TABLE instructor_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT,
    course_id INT,
    FOREIGN KEY (instructor_id) REFERENCES users(user_id),
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
);

-- Exam Schedule
CREATE TABLE exam_schedule (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT,
    exam_date DATE,
    start_time TIME,
    end_time TIME,
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
);

-- Exams
CREATE TABLE exams (
    exam_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT,
    instructor_id INT,
    title VARCHAR(255),
    description TEXT,
    time_limit INT, -- in minutes
    total_marks INT,
    status ENUM('active', 'inactive') DEFAULT 'inactive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    FOREIGN KEY (instructor_id) REFERENCES users(user_id)
);


-- Questions
CREATE TABLE questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT,
    question_text TEXT,
    question_type ENUM('true_false', 'multiple_choice', 'blank_space'),
    correct_answer TEXT,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id)
);

-- Choices (for multiple choice questions)
CREATE TABLE choices (
    choice_id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT,
    choice_text VARCHAR(255),
    is_correct BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (question_id) REFERENCES questions(question_id)
);

-- Student Exams
CREATE TABLE student_exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    exam_id INT,
    started_at DATETIME,
    submitted_at DATETIME,
    score DECIMAL(5,2),
    FOREIGN KEY (student_id) REFERENCES users(user_id),
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id)
);

-- Student Answers
CREATE TABLE student_answers (
    answer_id INT AUTO_INCREMENT PRIMARY KEY,
    student_exam_id INT,
    question_id INT,
    answer_text TEXT,
    is_correct BOOLEAN,
    FOREIGN KEY (student_exam_id) REFERENCES student_exams(id),
    FOREIGN KEY (question_id) REFERENCES questions(question_id)
);

-- Feedbacks table
CREATE TABLE feedbacks (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    course_id INT,
    exam_id INT,
    message TEXT,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id),
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id)
);

-- Seed Data
INSERT INTO users (name, email, password, role) VALUES
('Admin One', 'admin@gmail.com', 'adminpass', 'admin'),
('Instructor One', 'instructor1@gmail.com', 'instructorpass1', 'instructor'),
('Instructor Two', 'instructor2@gmail.com', 'instructorpass2', 'instructor'),
('Student One', 'student1@gmail.com', 'studentpass1', 'student'),
('Student Two', 'student2@gmail.com', 'studentpass2', 'student');

INSERT INTO courses (course_name, academic_year, semester) VALUES
('Database Systems', 2024, 'I'),
('Web Development', 2024, 'II');

INSERT INTO instructor_courses (instructor_id, course_id) VALUES
(2, 1),
(2, 2);

INSERT INTO exam_schedule (course_id, exam_date, start_time, end_time) VALUES
(1, '2025-06-10', '10:00:00', '11:00:00'),
(2, '2025-06-12', '14:00:00', '15:30:00');

INSERT INTO exams (course_id, instructor_id, title, description, time_limit, total_marks)
VALUES (
    1,
    2,
    'DB Midterm Exam',
    'This exam covers SQL basics, ER models, and constraints.',
    60,
    100
);


INSERT INTO questions (exam_id, question_text, question_type, correct_answer) VALUES
(1, 'MySQL is a NoSQL DBMS.', 'true_false', 'false'),
(1, 'Which of these are SQL constraints?', 'multiple_choice', 'PRIMARY KEY'),
(1, '____ is used to uniquely identify rows in a table.', 'blank_space', 'Primary key');

INSERT INTO choices (question_id, choice_text, is_correct) VALUES
(2, 'PRIMARY KEY', true),
(2, 'FOREIGN TABLE', false),
(2, 'UNIQUE ID', false),
(2, 'DATA BLOCK', false);

INSERT INTO student_exams (student_id, exam_id, started_at) VALUES
(3, 1, NOW());

INSERT INTO student_answers (student_exam_id, question_id, answer_text, is_correct) VALUES
(1, 1, 'false', true),
(1, 2, 'PRIMARY KEY', true),
(1, 3, 'Primary key', true);

INSERT INTO feedbacks (student_id, course_id, exam_id, message, rating)
VALUES (3, 1, 1, 'The exam was well structured and fair.', 5);


-- Update student exam submission

UPDATE student_exams
SET submitted_at = NOW(), score = 100
WHERE id = 1;
