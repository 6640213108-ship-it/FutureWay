-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 15, 2026 at 01:43 PM
-- Server version: 5.7.24
-- PHP Version: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `project1`
--

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `faculty` varchar(100) NOT NULL,
  `description` text,
  `mbti_match` json NOT NULL,
  `min_math` decimal(3,2) DEFAULT '0.00',
  `min_sci` decimal(3,2) DEFAULT '0.00',
  `min_eng` decimal(3,2) DEFAULT '0.00',
  `min_thai` decimal(3,2) DEFAULT '0.00',
  `min_social` decimal(3,2) DEFAULT '0.00',
  `min_art` decimal(3,2) DEFAULT '0.00',
  `weight_math` decimal(3,2) DEFAULT '1.00',
  `weight_sci` decimal(3,2) DEFAULT '1.00',
  `weight_eng` decimal(3,2) DEFAULT '1.00',
  `weight_thai` decimal(3,2) DEFAULT '1.00',
  `weight_social` decimal(3,2) DEFAULT '1.00',
  `weight_art` decimal(3,2) DEFAULT '1.00',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `faculty`, `description`, `mbti_match`, `min_math`, `min_sci`, `min_eng`, `min_thai`, `min_social`, `min_art`, `weight_math`, `weight_sci`, `weight_eng`, `weight_thai`, `weight_social`, `weight_art`, `is_active`, `created_at`) VALUES
(1, 'วิศวกรรมคอมพิวเตอร์', 'วิศวกรรมศาสตร์', 'ออกแบบและพัฒนาระบบคอมพิวเตอร์', '[\"INTJ\", \"INTP\", \"ENTJ\", \"ISTJ\", \"ISTP\", \"ENTP\", \"ESTJ\"]', '3.00', '2.50', '2.00', '0.00', '0.00', '0.00', '2.00', '1.50', '1.00', '0.50', '0.50', '0.50', 1, '2026-06-14 16:12:36'),
(2, 'วิทยาศาสตร์คอมพิวเตอร์', 'วิทยาศาสตร์', 'ศึกษาทฤษฎีและการพัฒนาซอฟต์แวร์', '[\"INTJ\", \"INTP\", \"ENTJ\", \"ISTJ\", \"ISTP\", \"ENTP\"]', '3.00', '2.50', '2.00', '0.00', '0.00', '0.00', '2.00', '1.50', '1.00', '0.50', '0.50', '0.50', 1, '2026-06-14 16:12:36'),
(3, 'วิศวกรรมไฟฟ้า', 'วิศวกรรมศาสตร์', 'ระบบไฟฟ้าและอิเล็กทรอนิกส์', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\", \"ESTJ\", \"ENTJ\"]', '3.00', '3.00', '2.00', '0.00', '0.00', '0.00', '2.00', '2.00', '1.00', '0.50', '0.50', '0.50', 1, '2026-06-14 16:12:36'),
(4, 'แพทยศาสตร์', 'แพทยศาสตร์', 'ศึกษาการแพทย์และการรักษาโรค', '[\"ISFJ\", \"INFJ\", \"ESFJ\", \"ENFJ\", \"ISTJ\", \"ESTJ\", \"ESFP\"]', '3.00', '3.50', '2.50', '0.00', '0.00', '0.00', '1.50', '2.50', '1.00', '0.50', '0.50', '0.50', 1, '2026-06-14 16:12:36'),
(5, 'พยาบาลศาสตร์', 'พยาบาลศาสตร์', 'ดูแลและส่งเสริมสุขภาพผู้ป่วย', '[\"ISFJ\", \"INFJ\", \"ESFJ\", \"ENFJ\", \"INFP\", \"ESFP\"]', '2.00', '2.50', '2.00', '0.00', '0.00', '0.00', '1.00', '2.00', '1.00', '0.50', '0.50', '0.50', 1, '2026-06-14 16:12:36'),
(6, 'เภสัชศาสตร์', 'เภสัชศาสตร์', 'ศึกษาเกี่ยวกับยาและการใช้ยา', '[\"ISTJ\", \"INTJ\", \"ESTJ\", \"INTP\", \"ISFJ\"]', '3.00', '3.50', '2.00', '0.00', '0.00', '0.00', '1.50', '2.50', '1.00', '0.50', '0.50', '0.50', 1, '2026-06-14 16:12:36'),
(7, 'นิติศาสตร์', 'นิติศาสตร์', 'ศึกษากฎหมายและกระบวนการยุติธรรม', '[\"INTJ\", \"ENTJ\", \"ISTJ\", \"ESTJ\", \"ENTP\", \"INTP\", \"ESTP\"]', '2.00', '0.00', '2.50', '2.50', '3.00', '0.00', '1.00', '0.50', '1.50', '1.50', '2.00', '0.50', 1, '2026-06-14 16:12:36'),
(8, 'รัฐศาสตร์', 'รัฐศาสตร์', 'การเมืองการปกครองและนโยบายสาธารณะ', '[\"ENTJ\", \"ENFJ\", \"ENTP\", \"ESTJ\", \"ENFP\", \"ESTP\"]', '0.00', '0.00', '2.00', '2.50', '3.00', '0.00', '0.50', '0.50', '1.50', '1.50', '2.50', '0.50', 1, '2026-06-14 16:12:36'),
(9, 'จิตวิทยา', 'มนุษยศาสตร์', 'ศึกษาพฤติกรรมและกระบวนการทางจิตใจ', '[\"INFJ\", \"INFP\", \"ENFJ\", \"ENFP\", \"ISFJ\", \"ESFJ\", \"INTP\"]', '0.00', '2.00', '2.00', '2.00', '2.50', '0.00', '0.50', '1.00', '1.00', '1.00', '2.00', '0.50', 1, '2026-06-14 16:12:36'),
(10, 'ครุศาสตร์', 'ครุศาสตร์', 'ผลิตครูและบุคลากรทางการศึกษา', '[\"ISFJ\", \"INFJ\", \"ESFJ\", \"ENFJ\", \"ISFP\", \"INFP\", \"ESFP\"]', '0.00', '0.00', '2.00', '2.50', '2.50', '0.00', '0.50', '0.50', '1.00', '1.50', '2.00', '0.50', 1, '2026-06-14 16:12:36'),
(11, 'สังคมสงเคราะห์', 'สังคมสงเคราะห์', 'ช่วยเหลือและพัฒนาคุณภาพชีวิต', '[\"INFJ\", \"INFP\", \"ENFJ\", \"ENFP\", \"ISFJ\", \"ESFJ\", \"ESFP\"]', '0.00', '0.00', '1.50', '2.00', '3.00', '0.00', '0.50', '0.50', '1.00', '1.00', '2.50', '0.50', 1, '2026-06-14 16:12:36'),
(12, 'บริหารธุรกิจ', 'บริหารธุรกิจ', 'การจัดการองค์กรและธุรกิจ', '[\"ENTJ\", \"ESTJ\", \"ENFJ\", \"ESTP\", \"ENFP\", \"ESFJ\", \"ENTP\"]', '2.00', '0.00', '2.50', '0.00', '2.00', '0.00', '1.50', '0.50', '1.50', '0.50', '1.50', '0.50', 1, '2026-06-14 16:12:36'),
(13, 'การบัญชี', 'บริหารธุรกิจ', 'บันทึกและวิเคราะห์ข้อมูลทางการเงิน', '[\"ISTJ\", \"ISFJ\", \"ESTJ\", \"INTJ\", \"INTP\"]', '3.00', '0.00', '2.00', '0.00', '0.00', '0.00', '2.50', '0.50', '1.00', '0.50', '0.50', '0.50', 1, '2026-06-14 16:12:36'),
(14, 'การตลาด', 'บริหารธุรกิจ', 'กลยุทธ์การตลาดและพฤติกรรมผู้บริโภค', '[\"ENFP\", \"ESTP\", \"ESFP\", \"ENTP\", \"ENFJ\", \"ESTJ\"]', '1.50', '0.00', '2.50', '0.00', '2.00', '0.00', '1.00', '0.50', '2.00', '0.50', '1.50', '0.50', 1, '2026-06-14 16:12:36'),
(15, 'นิเทศศาสตร์', 'นิเทศศาสตร์', 'การสื่อสารมวลชนและสื่อดิจิทัล', '[\"ENFP\", \"ENTP\", \"ESFP\", \"ESTP\", \"ENFJ\", \"ENTJ\"]', '0.00', '0.00', '3.00', '2.00', '2.00', '0.00', '0.50', '0.50', '2.00', '1.00', '1.50', '0.50', 1, '2026-06-14 16:12:36'),
(16, 'สถาปัตยกรรมศาสตร์', 'สถาปัตยกรรม', 'ออกแบบอาคารและสภาพแวดล้อม', '[\"ISFP\", \"INFP\", \"ISTP\", \"INTP\", \"ISFJ\", \"INTJ\"]', '2.50', '1.50', '1.50', '0.00', '0.00', '3.00', '1.50', '1.00', '1.00', '0.50', '0.50', '2.50', 1, '2026-06-14 16:12:36'),
(17, 'ศิลปกรรม/ออกแบบ', 'ศิลปกรรมศาสตร์', 'ออกแบบกราฟิก แฟชั่น และศิลปะ', '[\"ISFP\", \"INFP\", \"ENFP\", \"ISFJ\", \"ESFP\", \"ISTP\"]', '0.00', '0.00', '1.50', '1.50', '0.00', '3.50', '0.50', '0.50', '1.00', '1.00', '0.50', '3.00', 1, '2026-06-14 16:12:36'),
(18, 'แอนิเมชันและเกม', 'เทคโนโลยีสื่อ', 'สร้างสรรค์แอนิเมชัน เกม และสื่อดิจิทัล', '[\"INFP\", \"ISFP\", \"INTP\", \"ISTP\", \"ENFP\", \"ENTP\"]', '2.00', '1.50', '1.50', '0.00', '0.00', '2.50', '1.50', '1.00', '1.00', '0.50', '0.50', '2.00', 1, '2026-06-14 16:12:36'),
(19, 'การโรงแรมและการท่องเที่ยว', 'การโรงแรม', 'บริการและการจัดการธุรกิจโรงแรม', '[\"ESFJ\", \"ESFP\", \"ENFJ\", \"ENFP\", \"ESTP\", \"ISFJ\"]', '0.00', '0.00', '3.00', '1.50', '1.50', '0.00', '0.50', '0.50', '2.50', '1.00', '1.50', '0.50', 1, '2026-06-14 16:12:36');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_results`
--

CREATE TABLE `quiz_results` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `grade_math` decimal(3,2) NOT NULL,
  `grade_sci` decimal(3,2) NOT NULL,
  `grade_eng` decimal(3,2) NOT NULL,
  `grade_thai` decimal(3,2) NOT NULL,
  `grade_social` decimal(3,2) NOT NULL,
  `grade_art` decimal(3,2) NOT NULL,
  `mbti_type` varchar(4) NOT NULL,
  `mbti_e_i` char(1) NOT NULL,
  `mbti_s_n` char(1) NOT NULL,
  `mbti_t_f` char(1) NOT NULL,
  `mbti_j_p` char(1) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `branch_name` varchar(100) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `quiz_results`
--

INSERT INTO `quiz_results` (`id`, `user_id`, `grade_math`, `grade_sci`, `grade_eng`, `grade_thai`, `grade_social`, `grade_art`, `mbti_type`, `mbti_e_i`, `mbti_s_n`, `mbti_t_f`, `mbti_j_p`, `branch_id`, `branch_name`, `score`, `created_at`) VALUES
(1, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ESTJ', 'E', 'S', 'T', 'J', NULL, NULL, NULL, '2026-07-15 12:21:50'),
(2, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ESFJ', 'E', 'S', 'F', 'J', NULL, NULL, NULL, '2026-07-15 12:24:46'),
(3, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ENTJ', 'E', 'N', 'T', 'J', NULL, NULL, NULL, '2026-07-15 12:29:50'),
(4, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ISFP', 'I', 'S', 'F', 'P', NULL, NULL, NULL, '2026-07-15 12:31:27'),
(5, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ISFP', 'I', 'S', 'F', 'P', NULL, NULL, NULL, '2026-07-15 12:37:00'),
(6, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ESTJ', 'E', 'S', 'T', 'J', NULL, NULL, NULL, '2026-07-15 12:37:54'),
(7, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ESTJ', 'E', 'S', 'T', 'J', NULL, NULL, NULL, '2026-07-15 12:40:30'),
(8, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ISFJ', 'I', 'S', 'F', 'J', NULL, NULL, NULL, '2026-07-15 12:41:51'),
(9, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ISFJ', 'I', 'S', 'F', 'J', NULL, NULL, NULL, '2026-07-15 12:44:16'),
(10, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ISFJ', 'I', 'S', 'F', 'J', NULL, NULL, NULL, '2026-07-15 12:59:46'),
(11, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ISFJ', 'I', 'S', 'F', 'J', NULL, NULL, NULL, '2026-07-15 13:04:15'),
(12, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ISFJ', 'I', 'S', 'F', 'J', NULL, NULL, NULL, '2026-07-15 13:14:06'),
(13, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ISFJ', 'I', 'S', 'F', 'J', NULL, NULL, NULL, '2026-07-15 13:14:12'),
(14, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ISFJ', 'I', 'S', 'F', 'J', NULL, NULL, NULL, '2026-07-15 13:14:33'),
(15, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ISFJ', 'I', 'S', 'F', 'J', NULL, NULL, NULL, '2026-07-15 13:14:41'),
(16, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ISFJ', 'I', 'S', 'F', 'J', NULL, NULL, NULL, '2026-07-15 13:17:30'),
(17, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ISFJ', 'I', 'S', 'F', 'J', NULL, NULL, NULL, '2026-07-15 13:17:33'),
(18, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ISFJ', 'I', 'S', 'F', 'J', NULL, NULL, NULL, '2026-07-15 13:27:42'),
(19, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ISFJ', 'I', 'S', 'F', 'J', NULL, NULL, NULL, '2026-07-15 13:29:20'),
(20, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ISFJ', 'I', 'S', 'F', 'J', NULL, NULL, NULL, '2026-07-15 13:33:33'),
(21, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ISFJ', 'I', 'S', 'F', 'J', NULL, NULL, NULL, '2026-07-15 13:34:14'),
(22, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ISFJ', 'I', 'S', 'F', 'J', NULL, NULL, NULL, '2026-07-15 13:36:03'),
(23, 1, '2.00', '2.00', '2.00', '2.00', '2.00', '2.00', 'ISFJ', 'I', 'S', 'F', 'J', 5, 'พยาบาลศาสตร์', '55.00', '2026-07-15 13:38:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firstname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `firstname`, `lastname`, `gender`, `email`, `password`, `created_at`) VALUES
(1, 'topza', 'คณิต', 'งามพานิชกิจ', 'ชาย', 'topza8644@gmail.com', '$2y$10$KuKl0APskDp7ftmfmMGAOO2LiIH0R5vy0adIO4OohJf9memLadRy2', '2026-04-29 20:40:54'),
(2, 'hee', 'เอ็ม', 'ออนิว', 'ชาย', 'topza5444@gmail.com', '$2y$10$6PyEJjzsERr.JetnAh0D6u9fr3giFv3hRhrrGStVXVppUCOdd76X6', '2026-04-29 22:02:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quiz_results`
--
ALTER TABLE `quiz_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `quiz_results`
--
ALTER TABLE `quiz_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `quiz_results`
--
ALTER TABLE `quiz_results`
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
