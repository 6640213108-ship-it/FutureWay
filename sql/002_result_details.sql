-- ========================================
-- FutureWay - Migration 002
-- เก็บ "ผลลัพธ์ของแต่ละรอบที่ทำแบบทดสอบ" ให้ครบถ้วน
--
-- เดิม: quiz_results เก็บแค่ MBTI + สาขาอันดับ 1
--       ส่วนอันดับ 2-3 ถูกคำนวณใหม่ทุกครั้งที่เปิดหน้า result
--       -> ถ้าแก้ข้อมูลตาราง branches ทีหลัง ประวัติเก่าจะแสดงผลเปลี่ยนไป
--
-- ใหม่: บันทึกผลลัพธ์เป็น snapshot ตอนทำแบบทดสอบ
--       - quiz_results        : เพิ่ม avg_grade, mbti_detail (คะแนนราย 4 มิติ), answers_total
--       - quiz_result_branches: สาขาที่แนะนำครบทั้ง 3 อันดับของรอบนั้น
--       - quiz_answers        : คำตอบรายข้อของรอบนั้น (ตอบ A/B ข้อไหนบ้าง)
--
-- รันไฟล์นี้ 1 ครั้งบน MySQL ของ Railway (ดูวิธีรันใน php/migrate.php)
-- ========================================

-- ---- 1) คอลัมน์เพิ่มใน quiz_results ----
-- แยกเป็นคำสั่งละคอลัมน์ตั้งใจ ไม่ใช่รวมเป็น ALTER เดียว
-- เพราะ migrate.php ข้าม error 1060 (คอลัมน์มีอยู่แล้ว) เป็นรายคำสั่ง
-- ถ้ารวมเป็นก้อนเดียวแล้วคอลัมน์แรกมีอยู่แล้ว จะ fail ทั้งก้อน คอลัมน์ที่เหลือไม่ถูกเพิ่ม
--
-- หมายเหตุ: คอลัมน์ชนิด json/text/blob ใส่ DEFAULT ไม่ได้ (MySQL error 1101)
-- ปล่อยว่างไว้ = nullable อยู่แล้ว
ALTER TABLE `quiz_results` ADD COLUMN `avg_grade`     decimal(4,2) DEFAULT NULL AFTER `grade_art`;
ALTER TABLE `quiz_results` ADD COLUMN `mbti_detail`   json                      AFTER `mbti_j_p`;
ALTER TABLE `quiz_results` ADD COLUMN `answers_total` int(11)      DEFAULT NULL AFTER `mbti_detail`;

-- ---- 2) สาขาที่แนะนำของแต่ละรอบ (อันดับ 1-3) ----
CREATE TABLE IF NOT EXISTS `quiz_result_branches` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `result_id`   int(11)      NOT NULL,
  `rank_no`     tinyint(4)   NOT NULL COMMENT 'อันดับ 1, 2, 3',
  `branch_id`   int(11)      DEFAULT NULL,
  `branch_name` varchar(100) NOT NULL,
  `faculty`     varchar(100) DEFAULT NULL,
  `description` text,
  `score`       decimal(5,2) NOT NULL COMMENT 'คะแนนความเหมาะสม 0-100',
  `note`        varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_result_rank` (`result_id`, `rank_no`),
  CONSTRAINT `fk_qrb_result` FOREIGN KEY (`result_id`)
      REFERENCES `quiz_results` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- 3) คำตอบรายข้อของแต่ละรอบ ----
CREATE TABLE IF NOT EXISTS `quiz_answers` (
  `id`          int(11)  NOT NULL AUTO_INCREMENT,
  `result_id`   int(11)  NOT NULL,
  `question_id` int(11)  NOT NULL,
  `question_no` int(11)  DEFAULT NULL,
  `category`    char(2)  DEFAULT NULL COMMENT 'EI / SN / TF / JP',
  `selected`    char(1)  NOT NULL     COMMENT 'A หรือ B',
  `trait`       char(1)  DEFAULT NULL COMMENT 'ตัวอักษรที่ได้จากตัวเลือกนี้',
  PRIMARY KEY (`id`),
  KEY `idx_qa_result` (`result_id`),
  CONSTRAINT `fk_qa_result` FOREIGN KEY (`result_id`)
      REFERENCES `quiz_results` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
