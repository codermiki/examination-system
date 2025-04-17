-- ================================================================= --
--                    ONLINE EXAMINATION SYSTEM                      --
--                        DATABASE SCHEMA                            --
-- ================================================================= --

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `online_exam_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `online_exam_system`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','instructor','student') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--
CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--
CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) DEFAULT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `course_instructors` (Linking instructors to courses - ManyToMany)
--
CREATE TABLE `course_instructors` (
    `course_id` int(11) NOT NULL,
    `instructor_id` int(11) NOT NULL,
    `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`course_id`, `instructor_id`),
    KEY `instructor_id` (`instructor_id`),
    CONSTRAINT `course_instructors_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    -- Ensure instructor_id refers to a user with 'instructor' role (Checked via application logic or trigger)
    CONSTRAINT `course_instructors_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------


--
-- Table structure for table `course_enrollments` (Linking students to courses - ManyToMany)
--
CREATE TABLE `course_enrollments` (
    `course_id` int(11) NOT NULL,
    `student_id` int(11) NOT NULL,
    `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`course_id`, `student_id`),
    KEY `student_id` (`student_id`),
    CONSTRAINT `course_enrollments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    -- Ensure student_id refers to a user with 'student' role (Checked via application logic or trigger)
    CONSTRAINT `course_enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `exams`
--
CREATE TABLE `exams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL COMMENT 'User who created/owns the exam',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration` int(11) NOT NULL COMMENT 'Duration in minutes',
  `passing_score` decimal(5,2) NOT NULL DEFAULT 70.00 COMMENT 'Percentage',
  `attempts_allowed` int(11) NOT NULL DEFAULT 1,
  `shuffle_questions` tinyint(1) NOT NULL DEFAULT 1,
  `shuffle_answers` tinyint(1) NOT NULL DEFAULT 1,
  `show_results_immediately` tinyint(1) NOT NULL DEFAULT 0,
  `start_time` datetime DEFAULT NULL COMMENT 'Exam availability start window',
  `end_time` datetime DEFAULT NULL COMMENT 'Exam availability end window',
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `instructor_id` (`instructor_id`),
  CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  -- Ensure instructor_id refers to a user with 'instructor' role (Checked via application logic or trigger)
  CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--
CREATE TABLE `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) NOT NULL,
  `type` enum('mcq','true_false','fill_blank','essay','file_upload','coding','multi_choice') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'multi_choice allows multiple correct answers',
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Question text (can include HTML, MathJax)',
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  `options` json DEFAULT NULL COMMENT 'MCQ/TF/MultiChoice options: [{"text": "...", "is_correct": true/false, "value": "opt1"}, ...]',
  `correct_answer` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'For fill_blank or simple reference',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `exam_id` (`exam_id`),
  CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------


--
-- Table structure for table `exam_results`
--
CREATE TABLE `exam_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'Student who took the exam',
  `exam_id` int(11) NOT NULL,
  `attempt_number` int(11) NOT NULL DEFAULT 1,
  `score` decimal(5,2) DEFAULT NULL COMMENT 'Overall score (may be partial until fully graded)',
  `status` enum('pending','started','completed','grading','published') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `time_spent` int(11) DEFAULT NULL COMMENT 'Seconds spent',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proctoring_log` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Store warnings, events (tab switches, etc.) as JSON or text',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attempt` (`user_id`,`exam_id`,`attempt_number`),
  KEY `exam_id` (`exam_id`),
  CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--
CREATE TABLE `answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `result_id` int(11) NOT NULL COMMENT 'Links to the specific exam attempt',
  `question_id` int(11) NOT NULL,
  `answer_text` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Student''s answer (text, selected option(s) value/JSON, file path, code)',
  `is_correct` tinyint(1) DEFAULT NULL COMMENT 'For auto-graded questions (NULL, 0=false, 1=true)',
  `points_awarded` decimal(5,2) DEFAULT NULL COMMENT 'Points awarded (auto or manual grade)',
  `feedback` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Instructor feedback for this specific answer',
  `flagged` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If the student flagged the question for review',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_answer_per_attempt` (`result_id`,`question_id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`result_id`) REFERENCES `exam_results` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--
CREATE TABLE `feedbacks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL COMMENT 'User providing feedback',
    `exam_id` int(11) DEFAULT NULL COMMENT 'Optional: feedback specific to an exam',
    `course_id` int(11) DEFAULT NULL COMMENT 'Optional: feedback specific to a course',
    `rating` tinyint(1) DEFAULT NULL COMMENT 'Optional: e.g., 1-5 star rating',
    `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
    `type` enum('general','exam','course','bug_report') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
    `status` enum('new','reviewed','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
    `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `exam_id` (`exam_id`),
    KEY `course_id` (`course_id`),
    CONSTRAINT `feedbacks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `feedbacks_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `feedbacks_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------
--                    SAMPLE DATA
-- --------------------------------------------------------

-- Add default admin, instructor, student (Replace password hashes!)
-- Generate hash: echo password_hash('password', PASSWORD_BCRYPT);
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `status`) VALUES
('admin', 'admin@example.com', '$2y$10$YOUR_BCRYPT_HASH_HERE', 'admin', 'active'),
('instructor1', 'instructor@example.com', '$2y$10$YOUR_BCRYPT_HASH_HERE', 'instructor', 'active'),
('student1', 'student@example.com', '$2y$10$YOUR_BCRYPT_HASH_HERE', 'student', 'active'),
('student2', 'student2@example.com', '$2y$10$YOUR_BCRYPT_HASH_HERE', 'student', 'active');

-- Add Departments and Courses
INSERT INTO `departments` (`name`, `description`) VALUES
('Computer Science', 'Department of Computing and Information Technology'),
('Mathematics', 'Department of Mathematical Sciences');

INSERT INTO `courses` (`department_id`, `code`, `title`, `description`) VALUES
(1, 'CS101', 'Introduction to Programming', 'Fundamentals of programming using Python.'),
(1, 'CS201', 'Data Structures', 'Study of fundamental data structures.'),
(2, 'MA101', 'Calculus I', 'Introduction to differential and integral calculus.');

-- Assign instructor1 to CS101 and CS201
INSERT INTO `course_instructors` (`course_id`, `instructor_id`) VALUES
((SELECT id FROM courses WHERE code='CS101'), (SELECT id FROM users WHERE username='instructor1')),
((SELECT id FROM courses WHERE code='CS201'), (SELECT id FROM users WHERE username='instructor1'));

-- Enroll students in courses
INSERT INTO `course_enrollments` (`course_id`, `student_id`) VALUES
((SELECT id FROM courses WHERE code='CS101'), (SELECT id FROM users WHERE username='student1')),
((SELECT id FROM courses WHERE code='CS101'), (SELECT id FROM users WHERE username='student2')),
((SELECT id FROM courses WHERE code='MA101'), (SELECT id FROM users WHERE username='student1'));

-- Add a sample exam
INSERT INTO `exams` (`course_id`, `instructor_id`, `title`, `description`, `duration`, `passing_score`, `attempts_allowed`, `status`) VALUES
((SELECT id FROM courses WHERE code='CS101'), (SELECT id FROM users WHERE username='instructor1'), 'CS101 Midterm Exam', 'Covers first 5 chapters.', 60, 75.00, 1, 'published');

-- Add sample questions for the exam
-- Note: Carefully craft the JSON for options based on the structure defined in the comments
INSERT INTO `questions` (`exam_id`, `type`, `text`, `points`, `options`) VALUES
((SELECT id FROM exams WHERE title='CS101 Midterm Exam'), 'mcq', 'What keyword is used to define a function in Python?', '1.00', '[{\"text\": \"def\", \"is_correct\": true, \"value\": \"def\"}, {\"text\": \"fun\", \"is_correct\": false, \"value\": \"fun\"}, {\"text\": \"define\", \"is_correct\": false, \"value\": \"define\"}, {\"text\": \"function\", \"is_correct\": false, \"value\": \"function\"}]'),
((SELECT id FROM exams WHERE title='CS101 Midterm Exam'), 'true_false', 'Python is a compiled language.', '1.00', '[{\"text\": \"True\", \"is_correct\": false, \"value\": \"true\"}, {\"text\": \"False\", \"is_correct\": true, \"value\": \"false\"}]'),
((SELECT id FROM exams WHERE title='CS101 Midterm Exam'), 'essay', 'Explain the difference between a list and a tuple in Python.', '5.00', NULL);
