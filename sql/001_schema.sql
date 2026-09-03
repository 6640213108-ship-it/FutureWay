-- FutureWay - Base schema
-- โครงสร้างฐานข้อมูลเริ่มต้น (สาขา, ผลแบบทดสอบ, ผู้ใช้) — รันไฟล์นี้ก่อน
-- แล้วค่อยรัน migration อื่นๆ ใน sql/ ตามลำดับเลขไฟล์ (002, 003, ...)
--
-- หมายเหตุ: ไฟล์นี้มีเฉพาะโครงสร้างตารางและข้อมูลสาขา (ไม่มีข้อมูลผู้ใช้จริง)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

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

INSERT INTO `branches` (`id`, `name`, `faculty`, `description`, `mbti_match`, `min_math`, `min_sci`, `min_eng`, `min_thai`, `min_social`, `min_art`, `weight_math`, `weight_sci`, `weight_eng`, `weight_thai`, `weight_social`, `weight_art`, `is_active`, `created_at`) VALUES
(1, 'วิศวกรรมคอมพิวเตอร์', 'วิศวกรรมศาสตร์', 'ออกแบบและพัฒนาระบบคอมพิวเตอร์', '[\"INTJ\", \"INTP\", \"ENTJ\", \"ISTJ\", \"ISTP\", \"ENTP\", \"ESTJ\"]', '3.00', '2.50', '2.00', '0.00', '0.00', '0.00', '2.00', '1.50', '1.00', '0.50', '0.50', '0.50', 1, CURRENT_TIMESTAMP),
(2, 'วิทยาศาสตร์คอมพิวเตอร์', 'วิทยาศาสตร์', 'ศึกษาทฤษฎีและการพัฒนาซอฟต์แวร์', '[\"INTJ\", \"INTP\", \"ENTJ\", \"ISTJ\", \"ISTP\", \"ENTP\"]', '3.00', '2.50', '2.00', '0.00', '0.00', '0.00', '2.00', '1.50', '1.00', '0.50', '0.50', '0.50', 1, CURRENT_TIMESTAMP),
(3, 'วิศวกรรมไฟฟ้า', 'วิศวกรรมศาสตร์', 'ระบบไฟฟ้าและอิเล็กทรอนิกส์', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\", \"ESTJ\", \"ENTJ\"]', '3.00', '3.00', '2.00', '0.00', '0.00', '0.00', '2.00', '2.00', '1.00', '0.50', '0.50', '0.50', 1, CURRENT_TIMESTAMP),
(4, 'แพทยศาสตร์', 'แพทยศาสตร์', 'ศึกษาการแพทย์และการรักษาโรค', '[\"ISFJ\", \"INFJ\", \"ESFJ\", \"ENFJ\", \"ISTJ\", \"ESTJ\", \"ESFP\"]', '3.00', '3.50', '2.50', '0.00', '0.00', '0.00', '1.50', '2.50', '1.00', '0.50', '0.50', '0.50', 1, CURRENT_TIMESTAMP),
(5, 'พยาบาลศาสตร์', 'พยาบาลศาสตร์', 'ดูแลและส่งเสริมสุขภาพผู้ป่วย', '[\"ISFJ\", \"INFJ\", \"ESFJ\", \"ENFJ\", \"INFP\", \"ESFP\"]', '2.00', '2.50', '2.00', '0.00', '0.00', '0.00', '1.00', '2.00', '1.00', '0.50', '0.50', '0.50', 1, CURRENT_TIMESTAMP),
(6, 'เภสัชศาสตร์', 'เภสัชศาสตร์', 'ศึกษาเกี่ยวกับยาและการใช้ยา', '[\"ISTJ\", \"INTJ\", \"ESTJ\", \"INTP\", \"ISFJ\"]', '3.00', '3.50', '2.00', '0.00', '0.00', '0.00', '1.50', '2.50', '1.00', '0.50', '0.50', '0.50', 1, CURRENT_TIMESTAMP),
(7, 'นิติศาสตร์', 'นิติศาสตร์', 'ศึกษากฎหมายและกระบวนการยุติธรรม', '[\"INTJ\", \"ENTJ\", \"ISTJ\", \"ESTJ\", \"ENTP\", \"INTP\", \"ESTP\"]', '2.00', '0.00', '2.50', '2.50', '3.00', '0.00', '1.00', '0.50', '1.50', '1.50', '2.00', '0.50', 1, CURRENT_TIMESTAMP),
(8, 'รัฐศาสตร์', 'รัฐศาสตร์', 'การเมืองการปกครองและนโยบายสาธารณะ', '[\"ENTJ\", \"ENFJ\", \"ENTP\", \"ESTJ\", \"ENFP\", \"ESTP\"]', '0.00', '0.00', '2.00', '2.50', '3.00', '0.00', '0.50', '0.50', '1.50', '1.50', '2.50', '0.50', 1, CURRENT_TIMESTAMP),
(9, 'จิตวิทยา', 'มนุษยศาสตร์', 'ศึกษาพฤติกรรมและกระบวนการทางจิตใจ', '[\"INFJ\", \"INFP\", \"ENFJ\", \"ENFP\", \"ISFJ\", \"ESFJ\", \"INTP\"]', '0.00', '2.00', '2.00', '2.00', '2.50', '0.00', '0.50', '1.00', '1.00', '1.00', '2.00', '0.50', 1, CURRENT_TIMESTAMP),
(10, 'ครุศาสตร์', 'ครุศาสตร์', 'ผลิตครูและบุคลากรทางการศึกษา', '[\"ISFJ\", \"INFJ\", \"ESFJ\", \"ENFJ\", \"ISFP\", \"INFP\", \"ESFP\"]', '0.00', '0.00', '2.00', '2.50', '2.50', '0.00', '0.50', '0.50', '1.00', '1.50', '2.00', '0.50', 1, CURRENT_TIMESTAMP),
(11, 'สังคมสงเคราะห์', 'สังคมสงเคราะห์', 'ช่วยเหลือและพัฒนาคุณภาพชีวิต', '[\"INFJ\", \"INFP\", \"ENFJ\", \"ENFP\", \"ISFJ\", \"ESFJ\", \"ESFP\"]', '0.00', '0.00', '1.50', '2.00', '3.00', '0.00', '0.50', '0.50', '1.00', '1.00', '2.50', '0.50', 1, CURRENT_TIMESTAMP),
(12, 'บริหารธุรกิจ', 'บริหารธุรกิจ', 'การจัดการองค์กรและธุรกิจ', '[\"ENTJ\", \"ESTJ\", \"ENFJ\", \"ESTP\", \"ENFP\", \"ESFJ\", \"ENTP\"]', '2.00', '0.00', '2.50', '0.00', '2.00', '0.00', '1.50', '0.50', '1.50', '0.50', '1.50', '0.50', 1, CURRENT_TIMESTAMP),
(13, 'การบัญชี', 'บริหารธุรกิจ', 'บันทึกและวิเคราะห์ข้อมูลทางการเงิน', '[\"ISTJ\", \"ISFJ\", \"ESTJ\", \"INTJ\", \"INTP\"]', '3.00', '0.00', '2.00', '0.00', '0.00', '0.00', '2.50', '0.50', '1.00', '0.50', '0.50', '0.50', 1, CURRENT_TIMESTAMP),
(14, 'การตลาด', 'บริหารธุรกิจ', 'กลยุทธ์การตลาดและพฤติกรรมผู้บริโภค', '[\"ENFP\", \"ESTP\", \"ESFP\", \"ENTP\", \"ENFJ\", \"ESTJ\"]', '1.50', '0.00', '2.50', '0.00', '2.00', '0.00', '1.00', '0.50', '2.00', '0.50', '1.50', '0.50', 1, CURRENT_TIMESTAMP),
(15, 'นิเทศศาสตร์', 'นิเทศศาสตร์', 'การสื่อสารมวลชนและสื่อดิจิทัล', '[\"ENFP\", \"ENTP\", \"ESFP\", \"ESTP\", \"ENFJ\", \"ENTJ\"]', '0.00', '0.00', '3.00', '2.00', '2.00', '0.00', '0.50', '0.50', '2.00', '1.00', '1.50', '0.50', 1, CURRENT_TIMESTAMP),
(16, 'สถาปัตยกรรมศาสตร์', 'สถาปัตยกรรม', 'ออกแบบอาคารและสภาพแวดล้อม', '[\"ISFP\", \"INFP\", \"ISTP\", \"INTP\", \"ISFJ\", \"INTJ\"]', '2.50', '1.50', '1.50', '0.00', '0.00', '3.00', '1.50', '1.00', '1.00', '0.50', '0.50', '2.50', 1, CURRENT_TIMESTAMP),
(17, 'ศิลปกรรม/ออกแบบ', 'ศิลปกรรมศาสตร์', 'ออกแบบกราฟิก แฟชั่น และศิลปะ', '[\"ISFP\", \"INFP\", \"ENFP\", \"ISFJ\", \"ESFP\", \"ISTP\"]', '0.00', '0.00', '1.50', '1.50', '0.00', '3.50', '0.50', '0.50', '1.00', '1.00', '0.50', '3.00', 1, CURRENT_TIMESTAMP),
(18, 'แอนิเมชันและเกม', 'เทคโนโลยีสื่อ', 'สร้างสรรค์แอนิเมชัน เกม และสื่อดิจิทัล', '[\"INFP\", \"ISFP\", \"INTP\", \"ISTP\", \"ENFP\", \"ENTP\"]', '2.00', '1.50', '1.50', '0.00', '0.00', '2.50', '1.50', '1.00', '1.00', '0.50', '0.50', '2.00', 1, CURRENT_TIMESTAMP),
(19, 'การโรงแรมและการท่องเที่ยว', 'การโรงแรม', 'บริการและการจัดการธุรกิจโรงแรม', '[\"ESFJ\", \"ESFP\", \"ENFJ\", \"ENFP\", \"ESTP\", \"ISFJ\"]', '0.00', '0.00', '3.00', '1.50', '1.50', '0.00', '0.50', '0.50', '2.50', '1.00', '1.50', '0.50', 1, CURRENT_TIMESTAMP);

-- --------------------------------------------------------

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

-- --------------------------------------------------------

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
-- Indexes
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `quiz_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

ALTER TABLE `quiz_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Constraints
--
ALTER TABLE `quiz_results`
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

COMMIT;
