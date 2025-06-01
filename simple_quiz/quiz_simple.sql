-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 29, 2025 at 06:35 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `quiz_simple`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_settings`
--

CREATE TABLE `admin_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_settings`
--

INSERT INTO `admin_settings` (`id`, `setting_key`, `setting_value`, `is_enabled`, `updated_at`, `updated_by`) VALUES
(1, 'admin_passkey', 'quiz_admin_2025', 1, '2025-05-29 16:10:53', 4);

-- --------------------------------------------------------

--
-- Table structure for table `choices`
--

CREATE TABLE `choices` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `choice_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `choices`
--

INSERT INTO `choices` (`id`, `question_id`, `choice_text`, `is_correct`) VALUES
(81, 39, '&lt;!-- php --&gt;', 0),
(82, 39, '&lt;php&gt;', 0),
(83, 39, '&lt;?php ?&gt;', 1),
(84, 39, '&lt;script php&gt;', 0),
(85, 40, '@', 0),
(86, 40, '$', 1),
(87, 40, '#', 0),
(88, 40, '%', 0),
(89, 41, '510 little pigs&nbsp;', 0),
(90, 41, '5', 0),
(91, 41, '15', 1),
(92, 41, 'Error', 0);

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','identification') DEFAULT 'multiple_choice',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `correct_answer` text DEFAULT NULL,
  `type` enum('multiple_choice','true_false','text_input') NOT NULL DEFAULT 'multiple_choice'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `quiz_id`, `question_text`, `question_type`, `created_at`, `correct_answer`, `type`) VALUES
(39, 25, 'Which of the following is the correct way to start a PHP block of code?', 'multiple_choice', '2025-05-29 02:51:43', '3', 'multiple_choice'),
(40, 25, 'Which symbol is used to declare a variable in PHP?', 'multiple_choice', '2025-05-29 02:51:43', '2', 'multiple_choice'),
(41, 25, 'What is the output of the following PHP code?<div><br></div><div>&lt;?php</div><div>echo 5 + \"10 little pigs\";</div><div>?&gt;</div>', 'multiple_choice', '2025-05-29 02:51:43', '3', 'multiple_choice');

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_limit` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`id`, `title`, `description`, `instructions`, `created_by`, `created_at`, `time_limit`) VALUES
(25, 'Basic PHP Topic', NULL, 'Choose the best answer from the choices.', 4, '2025-05-29 02:51:43', 300);

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempts`
--

CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `time_spent` int(11) DEFAULT NULL COMMENT 'Time spent in seconds',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quiz_attempts`
--

INSERT INTO `quiz_attempts` (`id`, `user_id`, `quiz_id`, `start_time`, `end_time`, `is_completed`, `time_spent`, `created_at`) VALUES
(98, 13, 25, '0000-00-00 00:00:00', NULL, 0, NULL, '2025-05-29 02:54:23'),
(99, 12, 25, '0000-00-00 00:00:00', NULL, 0, NULL, '2025-05-29 02:56:00'),
(100, 14, 25, '0000-00-00 00:00:00', NULL, 0, NULL, '2025-05-29 08:56:12'),
(101, 10, 25, '0000-00-00 00:00:00', NULL, 0, NULL, '2025-05-29 13:30:05'),
(102, 10, 25, '0000-00-00 00:00:00', NULL, 0, NULL, '2025-05-29 13:30:59'),
(103, 10, 25, '0000-00-00 00:00:00', NULL, 0, NULL, '2025-05-29 13:31:30'),
(104, 10, 25, '0000-00-00 00:00:00', NULL, 0, NULL, '2025-05-29 13:34:31'),
(105, 10, 25, '0000-00-00 00:00:00', NULL, 0, NULL, '2025-05-29 13:40:00'),
(106, 10, 25, '0000-00-00 00:00:00', NULL, 0, NULL, '2025-05-29 13:46:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `email`, `last_login`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'admin', NULL, NULL, 'active', '2025-05-14 23:15:01', '2025-05-14 23:15:01'),
(4, 'admins', '$2y$10$ok6PGjGoDuBZfk5WI83jpO9.1J0xYt8/bYwBzmNNDumXbFgrBlw3.', 'admin', 'admin@gmail.com', '2025-05-30 00:07:04', 'active', '2025-05-14 23:15:01', '2025-05-29 16:07:04'),
(6, 'mads', '$2y$10$CrN3bVc2YDqJa56IHwGClOPQNrHfXJDiqUVCdAxqo6FaNPnK/M2HK', 'admin', 'mads@gmail.com', '2025-05-15 23:31:28', 'active', '2025-05-15 14:30:06', '2025-05-15 15:31:28'),
(8, 'Jhosim', '$2y$10$XDx6EEhoVg5rFjBWb3peaeylh5Qh605t5LlG.kMGs1p.PwTFURP.e', 'user', 'jhosim@gmail.com', '2025-05-28 21:34:03', 'active', '2025-05-16 04:20:15', '2025-05-28 13:34:03'),
(10, 'Simlee', '$2y$10$1yQR6Qk/4WeHHtKAFy2dv.UMElmi7WvafHuAztWne1dNUsXpPQ/L6', 'user', 'Simlee@gmail.com', '2025-05-29 21:30:00', 'active', '2025-05-17 05:30:35', '2025-05-29 13:30:00'),
(11, 'Dissa', '$2y$10$Q8xMjljEXovCEQ8npXm0pO1nodoIMqQ3dKpLnVGMEcf81zhjSEcYm', 'admin', 'dissa@gmail.com', '2025-05-28 14:47:29', 'active', '2025-05-28 06:46:56', '2025-05-28 06:47:29'),
(12, 'Jhovan', '$2y$10$YlZ59Vw2G2gpy.8cNC7Y4.4q0Cv3dwsHAf4XXjIFOKHlT50CuEcq2', 'user', 'jhovanbalbuena2@gmail.com', '2025-05-29 10:55:54', 'active', '2025-05-28 14:00:43', '2025-05-29 02:55:54'),
(13, 'Madelyn', '$2y$10$yYxjE5LqnIBgwlrqjFOGo.BEGYcFPV1lV8f2qQtYzPshktEtkQTS2', 'user', 'lyndema@gmail.com', '2025-05-29 10:54:13', 'active', '2025-05-29 02:53:56', '2025-05-29 02:54:13'),
(14, 'Aiyah', '$2y$10$07oW430NTZADje2qVnFXKegK2KeYM9z4sNNZz1fF6sYU5S12K9kni', 'user', 'lyndemayawe@gmail.com', '2025-05-29 16:53:05', 'active', '2025-05-29 08:52:46', '2025-05-29 08:53:05');

-- --------------------------------------------------------

--
-- Table structure for table `user_answers`
--

CREATE TABLE `user_answers` (
  `id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `choice_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_answers`
--

INSERT INTO `user_answers` (`id`, `submission_id`, `question_id`, `choice_id`) VALUES
(53, 36, 39, 83),
(54, 36, 40, 86),
(55, 36, 41, 92),
(56, 37, 39, 83),
(57, 37, 40, 86),
(58, 37, 41, 91),
(59, 38, 39, 83),
(60, 38, 40, 86),
(61, 38, 41, 91);

-- --------------------------------------------------------

--
-- Table structure for table `user_submissions`
--

CREATE TABLE `user_submissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_submissions`
--

INSERT INTO `user_submissions` (`id`, `user_id`, `quiz_id`, `score`, `submitted_at`) VALUES
(36, 13, 25, 2, '2025-05-29 02:55:10'),
(37, 12, 25, 3, '2025-05-29 02:56:27'),
(38, 14, 25, 3, '2025-05-29 08:56:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `choices`
--
ALTER TABLE `choices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_quiz` (`user_id`,`quiz_id`),
  ADD KEY `idx_quiz_completion` (`quiz_id`,`is_completed`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `user_answers`
--
ALTER TABLE `user_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submission_id` (`submission_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `choice_id` (`choice_id`);

--
-- Indexes for table `user_submissions`
--
ALTER TABLE `user_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_settings`
--
ALTER TABLE `admin_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `choices`
--
ALTER TABLE `choices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `user_answers`
--
ALTER TABLE `user_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `user_submissions`
--
ALTER TABLE `user_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD CONSTRAINT `admin_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `choices`
--
ALTER TABLE `choices`
  ADD CONSTRAINT `choices_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD CONSTRAINT `quiz_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_attempts_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_answers`
--
ALTER TABLE `user_answers`
  ADD CONSTRAINT `user_answers_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `user_submissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_answers_ibfk_3` FOREIGN KEY (`choice_id`) REFERENCES `choices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_submissions`
--
ALTER TABLE `user_submissions`
  ADD CONSTRAINT `user_submissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_submissions_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
