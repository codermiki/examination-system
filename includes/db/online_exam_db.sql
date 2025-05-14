-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 14, 2025 at 12:23 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `online_exam_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `assigned_instructors`
--

CREATE TABLE `assigned_instructors` (
  `id` int(11) NOT NULL,
  `instructor_id` varchar(50) NOT NULL,
  `course_id` varchar(50) NOT NULL,
  `assigned_on` datetime DEFAULT current_timestamp(),
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assigned_instructors`
--

INSERT INTO `assigned_instructors` (`id`, `instructor_id`, `course_id`, `assigned_on`, `status`) VALUES
(14, 'INST1004', 'CS101', '2025-05-14 08:50:33', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `assigned_students`
--

CREATE TABLE `assigned_students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `course_id` varchar(50) NOT NULL,
  `assigned_on` datetime DEFAULT current_timestamp(),
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assigned_students`
--

INSERT INTO `assigned_students` (`id`, `student_id`, `course_id`, `assigned_on`, `status`) VALUES
(17, 'WCU1501120', 'CS101', '2025-05-14 08:40:00', 'Active'),
(18, 'WCU1501121', 'CS101', '2025-05-14 10:21:59', 'Active'),
(19, 'WCU1501122', 'CS101', '2025-05-14 10:21:59', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` varchar(50) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_name`, `created_at`) VALUES
('CS101', 'Introduction to Programming', '2025-05-13 10:18:05'),
('CS102', 'Data Structures', '2025-05-14 08:42:02'),
('CS103', 'Database Systems', '2025-05-13 10:19:44'),
('CS105', 'Networking', '2025-05-14 08:42:02');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `exam_id` int(11) NOT NULL,
  `course_id` varchar(50) NOT NULL,
  `instructor_id` varchar(50) NOT NULL,
  `exam_title` varchar(100) NOT NULL,
  `exam_description` text DEFAULT NULL,
  `duration_minutes` int(11) NOT NULL,
  `total_marks` int(11) NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Inactive',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`exam_id`, `course_id`, `instructor_id`, `exam_title`, `exam_description`, `duration_minutes`, `total_marks`, `status`, `created_at`) VALUES
(8, 'CS101', 'INST1004', 'C++ Midterm Exam', 'mid examination for software engineering department', 10, 10, 'Active', '2025-05-14 09:52:56');

-- --------------------------------------------------------

--
-- Table structure for table `exam_schedules`
--

CREATE TABLE `exam_schedules` (
  `schedule_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `scheduled_date` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_schedules`
--

INSERT INTO `exam_schedules` (`schedule_id`, `exam_id`, `scheduled_date`, `created_at`) VALUES
(11, 8, '2025-05-14 10:25:00', '2025-05-14 09:57:01');

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `feedback_text` text DEFAULT NULL,
  `rate` int(11) DEFAULT NULL CHECK (`rate` between 1 and 5),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedbacks`
--

INSERT INTO `feedbacks` (`id`, `student_id`, `exam_id`, `feedback_text`, `rate`, `created_at`) VALUES
(8, 'WCU1501120', 8, 'i`m truble to work on this exam', 1, '2025-05-14 10:20:47');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `question_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','fill_blank') NOT NULL,
  `correct_answer` text NOT NULL,
  `marks` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`question_id`, `exam_id`, `question_text`, `question_type`, `correct_answer`, `marks`, `created_at`) VALUES
(13, 8, 'c++ is compiled programing languege', 'true_false', 'true', 2, '2025-05-14 09:52:56'),
(14, 8, 'what is the built in function used to display', 'multiple_choice', 'option_2', 3, '2025-05-14 10:17:19'),
(15, 8, '________ is c++ key word used to out from the swich', 'fill_blank', 'break', 5, '2025-05-14 10:17:19');

-- --------------------------------------------------------

--
-- Table structure for table `question_options`
--

CREATE TABLE `question_options` (
  `option_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_text` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `question_options`
--

INSERT INTO `question_options` (`option_id`, `question_id`, `option_text`) VALUES
(17, 14, 'endl'),
(18, 14, 'cout'),
(19, 14, 'cat'),
(20, 14, 'log');

-- --------------------------------------------------------

--
-- Table structure for table `student_answers`
--

CREATE TABLE `student_answers` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `answered_on` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_answers`
--

INSERT INTO `student_answers` (`id`, `student_id`, `exam_id`, `question_id`, `answer_text`, `is_correct`, `answered_on`) VALUES
(31, 'WCU1501120', 8, 13, 'True', 0, '2025-05-14 10:02:50'),
(32, 'WCU1501121', 8, 14, 'cout', 0, '2025-05-14 10:25:12'),
(33, 'WCU1501121', 8, 15, 'break', 1, '2025-05-14 10:25:27'),
(34, 'WCU1501121', 8, 13, 'True', 0, '2025-05-14 10:25:36');

-- --------------------------------------------------------

--
-- Table structure for table `student_exam_status`
--

CREATE TABLE `student_exam_status` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `has_taken` tinyint(1) DEFAULT 0,
  `score` float DEFAULT NULL,
  `taken_on` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_exam_status`
--

INSERT INTO `student_exam_status` (`id`, `student_id`, `exam_id`, `has_taken`, `score`, `taken_on`) VALUES
(10, 'WCU1501120', 8, 1, 0, '2025-05-14 10:02:50'),
(11, 'WCU1501121', 8, 1, 5, '2025-05-14 10:25:36');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Instructor','Student') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `must_reset_password` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `created_at`, `status`, `must_reset_password`) VALUES
('1', 'System Admin', 'admin@example.com', '$2y$10$MJYyJT.V4S267hNTahN5s.4ixtXwqlBGa.sE7Zb1zsan0BPxkj/66', 'Admin', '2025-05-14 08:36:21', 'Active', 0),
('2', 'Jane Instructor', 'instructor@example.com', '$2y$10$PRdZBDVh2S6et1riN2.lSOJKg76WzpDSvPAiPWWldTzsy5IyzEfya', 'Instructor', '2025-05-14 08:36:21', 'Active', 1),
('3', 'John Student', 'student@example.com', '$2y$10$PRdZBDVh2S6et1riN2.lSOJKg76WzpDSvPAiPWWldTzsy5IyzEfya', 'Student', '2025-05-14 08:36:21', 'Active', 1),
('INST1004', 'Abel Tadesse', 'abel@university.edu', '$2y$10$eM0QsDYFT3C9mwwVdsVwHu0SZ1zGwY/XVT28bRDTXD2nHIymD31J.', 'Instructor', '2025-05-14 08:50:33', 'Active', 0),
('WCU1501120', 'Eva White', 'eva.white@example.com', '$2y$10$Vehkx/VXIXwgPu6t0dl/r.g/3keIB0F5cLg0m6Ikv/FtxodgPwI2m', 'Student', '2025-05-14 08:40:00', 'Active', 0),
('WCU1501121', 'Eva Black', 'eva.black@example.com', '$2y$10$3riQvxb7KEUen4CGTbi.BOPqX4m4EzRKfmqI7bUclG6ZMnWaEZWm.', 'Student', '2025-05-14 10:21:59', 'Active', 0),
('WCU1501122', 'Eva Black', 'eva.red@example.com', '$2y$10$mps9IQiWtKS5.GrQccEy5OtWSErTgJSfd1CnxGg9OO5wrBnKqE53C', 'Student', '2025-05-14 10:21:59', 'Active', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assigned_instructors`
--
ALTER TABLE `assigned_instructors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `assigned_students`
--
ALTER TABLE `assigned_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_course` (`student_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `idx_exam_id` (`exam_id`);

--
-- Indexes for table `question_options`
--
ALTER TABLE `question_options`
  ADD PRIMARY KEY (`option_id`),
  ADD KEY `idx_question_id` (`question_id`);

--
-- Indexes for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`exam_id`,`question_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `student_exam_status`
--
ALTER TABLE `student_exam_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`exam_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `idx_student_exam` (`student_id`,`exam_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assigned_instructors`
--
ALTER TABLE `assigned_instructors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `assigned_students`
--
ALTER TABLE `assigned_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `question_options`
--
ALTER TABLE `question_options`
  MODIFY `option_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `student_answers`
--
ALTER TABLE `student_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `student_exam_status`
--
ALTER TABLE `student_exam_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assigned_instructors`
--
ALTER TABLE `assigned_instructors`
  ADD CONSTRAINT `assigned_instructors_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assigned_instructors_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `assigned_students`
--
ALTER TABLE `assigned_students`
  ADD CONSTRAINT `assigned_students_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assigned_students_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  ADD CONSTRAINT `exam_schedules_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD CONSTRAINT `feedbacks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedbacks_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE;

--
-- Constraints for table `question_options`
--
ALTER TABLE `question_options`
  ADD CONSTRAINT `question_options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD CONSTRAINT `student_answers_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_answers_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_answers_ibfk_3` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_exam_status`
--
ALTER TABLE `student_exam_status`
  ADD CONSTRAINT `student_exam_status_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_exam_status_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
