-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-08-26 02:09:18
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `hpds_anne`
--

-- --------------------------------------------------------

--
-- 資料表結構 `score`
--

CREATE TABLE `score` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `score` int(11) NOT NULL,
  `incorrect_questions` text DEFAULT NULL,
  `answer_time` varchar(10) DEFAULT NULL,
  `answer_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `q1` tinyint(1) DEFAULT 1,
  `q2` tinyint(1) DEFAULT 1,
  `q3` tinyint(1) DEFAULT 1,
  `q4` tinyint(1) DEFAULT 1,
  `q5` tinyint(1) DEFAULT 1,
  `q6` tinyint(1) DEFAULT 1,
  `q7` tinyint(1) DEFAULT 1,
  `q8` tinyint(1) DEFAULT 1,
  `q9` tinyint(1) DEFAULT 1,
  `q10` tinyint(1) DEFAULT 1,
  `q11` tinyint(1) DEFAULT 1,
  `q12` tinyint(1) DEFAULT 1,
  `q13` tinyint(1) DEFAULT 1,
  `q14` tinyint(1) DEFAULT 1,
  `q15` tinyint(1) DEFAULT 1,
  `q16` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `score`
--

INSERT INTO `score` (`id`, `student_id`, `score`, `incorrect_questions`, `answer_time`, `answer_date`, `q1`, `q2`, `q3`, `q4`, `q5`, `q6`, `q7`, `q8`, `q9`, `q10`, `q11`, `q12`, `q13`, `q14`, `q15`, `q16`) VALUES
(3, 'c111154240', 90, '1,7,12', '2:10', '2025-08-11 23:36:48', 0, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 0, 1, 1, 1, 1),
(7, 'c111154240', 20, '1,3,5,9,11,13', '3:56', '2025-08-12 04:23:16', 0, 1, 0, 1, 0, 1, 1, 1, 0, 1, 0, 1, 0, 1, 1, 1),
(8, 'S001', 89, '8,9,12', '80:53', '2025-08-12 04:32:51', 1, 1, 1, 1, 1, 1, 1, 0, 0, 1, 1, 0, 1, 1, 1, 1),
(9, 'S001', 130, '2,9,10,', '10:00', '2025-08-18 09:58:02', 1, 0, 1, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- 資料表結構 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `name` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `permission` enum('student','nurse','snurse','teacher','ta','hidden_admin') DEFAULT 'student',
  `gender` enum('Male','Female') DEFAULT 'Male',
  `birth_date` date DEFAULT NULL,
  `graduate_year` int(11) DEFAULT NULL,
  `education_level` enum('1','2','3','4') DEFAULT NULL COMMENT '1=專科,2=大學,3=碩士,4=博士',
  `marital_status` enum('1','2','3','4','5','6') DEFAULT NULL COMMENT '1=未婚,2=同居,3=已婚,4=分居,5=離婚,6=喪偶',
  `hire_date` date DEFAULT NULL,
  `department` enum('1','2','3','4','5','6','7','8','9','10','11','12','13') DEFAULT NULL COMMENT '1=內科,2=外科,3=骨科,4=泌尿科,5=皮膚科,6=眼科,7=耳鼻喉科,8=婦產科,9=兒科,10=ICU,11=ER,12=OR,13=其他',
  `department_other` text DEFAULT NULL,
  `facility_type` enum('1','2','3','4') DEFAULT NULL COMMENT '1=醫學中心,2=區域醫院,3=地區醫院,4=學校',
  `other_hospital_experience` enum('0','1') DEFAULT '0',
  `other_hospital_years` int(11) DEFAULT 0,
  `critical_care_experience` enum('0','1') DEFAULT '0',
  `critical_care_years` int(11) DEFAULT 0,
  `bls_training_date` date DEFAULT NULL,
  `acls_experience` enum('0','1') DEFAULT '0',
  `acls_training_date` date DEFAULT NULL,
  `rescue_count` int(11) DEFAULT 0,
  `last_rescue_date` date DEFAULT NULL,
  `rescue_course_count` int(11) DEFAULT 0,
  `case_record_date` date DEFAULT NULL,
  `group_assignment` enum('1','2','3','4','5','6','7') DEFAULT NULL COMMENT '1-6=實驗組,7=對照組',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `users`
--

INSERT INTO `users` (`id`, `student_id`, `name`, `password`, `permission`, `gender`, `birth_date`, `graduate_year`, `education_level`, `marital_status`, `hire_date`, `department`, `department_other`, `facility_type`, `other_hospital_experience`, `other_hospital_years`, `critical_care_experience`, `critical_care_years`, `bls_training_date`, `acls_experience`, `acls_training_date`, `rescue_count`, `last_rescue_date`, `rescue_course_count`, `case_record_date`, `group_assignment`, `created_at`, `updated_at`) VALUES
(1, 'c111154240', '566h55CG5ZOh', '12345678', 'ta', 'Male', NULL, NULL, '', '', NULL, '', '', '', '0', NULL, '0', NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '', '2025-08-11 07:04:32', '2025-08-17 03:05:04'),
(2, '965018', '5pWZ5bir', '965018', 'teacher', 'Female', NULL, NULL, '', '', NULL, '', '', '', '0', NULL, '0', NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '', '2025-08-11 07:04:32', '2025-08-14 01:13:07'),
(3, 'S001', '5ris6Kmm5a2455Sf77yR', '123456', 'student', 'Female', '2006-07-12', NULL, '', '', NULL, '', '', '', '0', NULL, '0', NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '', '2025-08-11 07:04:32', '2025-08-25 04:57:01'),
(7, 'hpds_anne', '', 'alsdkjfhg', 'ta', 'Male', '2025-08-01', NULL, '', '', NULL, '', '', '', '0', NULL, '0', NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '', '2025-08-13 08:42:49', '2025-08-14 09:09:10'),
(9, 'S002', '5ris6Kmm5a2455SfMg==', '123456', 'student', 'Female', NULL, NULL, '', '1', NULL, '', '', '', '0', NULL, '0', NULL, NULL, '0', NULL, NULL, NULL, NULL, NULL, '', '2025-08-15 07:49:15', '2025-08-15 07:58:59'),
(10, 'N001', '5bCI56eR6K2355CG5bir', '123456', 'snurse', 'Male', NULL, NULL, '', '', NULL, '', '', '', '0', NULL, '1', 30, NULL, '0', NULL, NULL, NULL, NULL, NULL, '', '2025-08-15 08:31:11', '2025-08-25 05:44:07'),
(14, 'S003', '5a2455SfMw==', '111111', 'nurse', 'Male', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', 0, '0', 0, NULL, '0', NULL, 0, NULL, 0, NULL, NULL, '2025-08-26 01:44:26', '2025-08-26 01:44:26');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `score`
--
ALTER TABLE `score`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_score` (`score`),
  ADD KEY `idx_answer_date` (`answer_date`);

--
-- 資料表索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_permission` (`permission`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `score`
--
ALTER TABLE `score`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `score`
--
ALTER TABLE `score`
  ADD CONSTRAINT `score_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`student_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
