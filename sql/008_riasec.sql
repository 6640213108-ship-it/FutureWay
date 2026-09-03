-- ========================================
-- FutureWay - 008_riasec.sql
-- เพิ่มระบบ 'ไม่ทราบเกรด -> กรอกความสนใจ/งานอดิเรกแทน' อิงทฤษฎี
-- RIASEC (Holland Code) แทนเกรด
--
-- ค่า riasec_* ของสาขาจริง NRRU (72 แถวจาก 004_nrru_branches.sql) ที่ UPDATE
-- ท้ายไฟล์นี้ เป็นค่าประมาณจากตารางเทียบ Holland Code ของสาขาวิชาที่เผยแพร่
-- ทั่วไป (career-counseling / O*NET) จับคู่ด้วยชื่อสาขา+คณะ ไม่ใช่ผลวิจัย
-- เจาะจงของ NRRU เอง ปรับละเอียดเพิ่มได้ทีหลังจากหน้าแอดมิน
-- ========================================

-- ---- 1) คอลัมน์ RIASEC ใหม่ใน branches (มาตราส่วน 0.00-3.00 เหมือน weight_*) ----
ALTER TABLE `branches` ADD COLUMN `riasec_r` decimal(3,2) NOT NULL DEFAULT 0.30 COMMENT 'Realistic';
ALTER TABLE `branches` ADD COLUMN `riasec_i` decimal(3,2) NOT NULL DEFAULT 0.30 COMMENT 'Investigative';
ALTER TABLE `branches` ADD COLUMN `riasec_a` decimal(3,2) NOT NULL DEFAULT 0.30 COMMENT 'Artistic';
ALTER TABLE `branches` ADD COLUMN `riasec_s` decimal(3,2) NOT NULL DEFAULT 0.30 COMMENT 'Social';
ALTER TABLE `branches` ADD COLUMN `riasec_e` decimal(3,2) NOT NULL DEFAULT 0.30 COMMENT 'Enterprising';
ALTER TABLE `branches` ADD COLUMN `riasec_c` decimal(3,2) NOT NULL DEFAULT 0.30 COMMENT 'Conventional';

-- ---- 2) ตารางคำถามความสนใจ/งานอดิเรก (โหมดไม่ทราบเกรด) ----
CREATE TABLE IF NOT EXISTS `riasec_questions` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `letter`      char(1)      NOT NULL COMMENT 'R/I/A/S/E/C',
  `question_no` int(11)      NOT NULL,
  `text`        varchar(255) NOT NULL COMMENT 'ข้อความกิจกรรม/ความสนใจ ให้ผู้ใช้ติ๊กว่าใช่/ชอบ',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_letter_no` (`letter`, `question_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- 3) quiz_results: รองรับโหมดไม่กรอกเกรด ----
ALTER TABLE `quiz_results` MODIFY COLUMN `grade_math`   decimal(3,2) DEFAULT NULL;
ALTER TABLE `quiz_results` MODIFY COLUMN `grade_sci`    decimal(3,2) DEFAULT NULL;
ALTER TABLE `quiz_results` MODIFY COLUMN `grade_eng`    decimal(3,2) DEFAULT NULL;
ALTER TABLE `quiz_results` MODIFY COLUMN `grade_thai`   decimal(3,2) DEFAULT NULL;
ALTER TABLE `quiz_results` MODIFY COLUMN `grade_social` decimal(3,2) DEFAULT NULL;
ALTER TABLE `quiz_results` MODIFY COLUMN `grade_art`    decimal(3,2) DEFAULT NULL;
ALTER TABLE `quiz_results` ADD COLUMN `input_mode` varchar(10) NOT NULL DEFAULT 'grade' COMMENT "'grade' หรือ 'interest'" AFTER `user_id`;
ALTER TABLE `quiz_results` ADD COLUMN `riasec_scores` json DEFAULT NULL AFTER `mbti_detail`;

-- ---- 4) ค่า RIASEC เริ่มต้นของสาขาตัวอย่าง 19 สาขา (001_schema.sql) ----
UPDATE `branches` SET `riasec_r` = 2.50, `riasec_i` = 3.00, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 1.00 WHERE `name` = 'วิศวกรรมคอมพิวเตอร์' AND `faculty` IS NOT NULL AND `id` <= 19;
UPDATE `branches` SET `riasec_r` = 2.00, `riasec_i` = 3.00, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 1.00 WHERE `name` = 'วิทยาศาสตร์คอมพิวเตอร์' AND `faculty` IS NOT NULL AND `id` <= 19;
UPDATE `branches` SET `riasec_r` = 3.00, `riasec_i` = 2.00, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'วิศวกรรมไฟฟ้า' AND `faculty` IS NOT NULL AND `id` <= 19;
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 3.00, `riasec_a` = 0.30, `riasec_s` = 2.00, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'แพทยศาสตร์' AND `faculty` IS NOT NULL AND `id` <= 19;
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 1.50, `riasec_a` = 0.30, `riasec_s` = 3.00, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'พยาบาลศาสตร์' AND `faculty` IS NOT NULL AND `id` <= 19;
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 3.00, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 1.50 WHERE `name` = 'เภสัชศาสตร์' AND `faculty` IS NOT NULL AND `id` <= 19;
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 0.30, `riasec_s` = 1.00, `riasec_e` = 2.00, `riasec_c` = 2.00 WHERE `name` = 'นิติศาสตร์' AND `faculty` IS NOT NULL AND `id` <= 19;
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 0.30, `riasec_s` = 1.50, `riasec_e` = 2.50, `riasec_c` = 0.30 WHERE `name` = 'รัฐศาสตร์' AND `faculty` IS NOT NULL AND `id` <= 19;
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 1.00, `riasec_a` = 0.30, `riasec_s` = 3.00, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'จิตวิทยา' AND `faculty` IS NOT NULL AND `id` <= 19;
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.00, `riasec_s` = 2.50, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'ครุศาสตร์' AND `faculty` IS NOT NULL AND `id` <= 19;
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 0.30, `riasec_s` = 3.00, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'สังคมสงเคราะห์' AND `faculty` IS NOT NULL AND `id` <= 19;
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 3.00, `riasec_c` = 1.50 WHERE `name` = 'บริหารธุรกิจ' AND `faculty` IS NOT NULL AND `id` <= 19;
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 1.00, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 3.00 WHERE `name` = 'การบัญชี' AND `faculty` IS NOT NULL AND `id` <= 19;
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.00, `riasec_s` = 0.30, `riasec_e` = 3.00, `riasec_c` = 0.30 WHERE `name` = 'การตลาด' AND `faculty` IS NOT NULL AND `id` <= 19;
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 2.00, `riasec_s` = 1.00, `riasec_e` = 2.00, `riasec_c` = 0.30 WHERE `name` = 'นิเทศศาสตร์' AND `faculty` IS NOT NULL AND `id` <= 19;
UPDATE `branches` SET `riasec_r` = 2.00, `riasec_i` = 0.30, `riasec_a` = 2.50, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'สถาปัตยกรรมศาสตร์' AND `faculty` IS NOT NULL AND `id` <= 19;
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 3.00, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'ศิลปกรรม/ออกแบบ' AND `faculty` IS NOT NULL AND `id` <= 19;
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 1.50, `riasec_a` = 2.50, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'แอนิเมชันและเกม' AND `faculty` IS NOT NULL AND `id` <= 19;
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 0.30, `riasec_s` = 2.50, `riasec_e` = 2.00, `riasec_c` = 0.30 WHERE `name` = 'การโรงแรมและการท่องเที่ยว' AND `faculty` IS NOT NULL AND `id` <= 19;

-- ---- 5) ค่า RIASEC ของสาขาจริง NRRU 72 สาขา (004_nrru_branches.sql) ----
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.00, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'การประถมศึกษา' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.00, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'การศึกษาปฐมวัย' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.00, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'การศึกษาพิเศษ' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.00, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'คณิตศาสตร์ (ครุศาสตร์)' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 2.00, `riasec_i` = 2.50, `riasec_a` = 1.00, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 1.00 WHERE `name` = 'คอมพิวเตอร์ศึกษา' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 1.00, `riasec_i` = 3.00, `riasec_a` = 1.00, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'เคมี (ครุศาสตร์)' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 1.00, `riasec_a` = 1.00, `riasec_s` = 3.00, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'จิตวิทยาการปรึกษาและการแนะแนว' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 1.00, `riasec_i` = 3.00, `riasec_a` = 1.00, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'ชีววิทยา (ครุศาสตร์)' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 3.00, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'ดนตรีศึกษา' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.00, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'เทคโนโลยีและสื่อสารการศึกษา' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 3.00, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'นาฏศิลป์ไทย' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 2.00, `riasec_i` = 0.30, `riasec_a` = 1.00, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'พลศึกษา' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.00, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'พระพุทธศาสนา' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 1.00, `riasec_i` = 3.00, `riasec_a` = 1.00, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'ฟิสิกส์ (ครุศาสตร์)' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.50, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'ภาษาจีน (ครุศาสตร์)' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.50, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'ภาษาไทย (ครุศาสตร์)' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.50, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'ภาษาอังกฤษ (ครุศาสตร์)' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 1.00, `riasec_i` = 3.00, `riasec_a` = 1.00, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'วิทยาศาสตร์ทั่วไป' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 3.00, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'ศิลปศึกษา' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.00, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'สังคมศึกษา' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 2.00, `riasec_i` = 0.30, `riasec_a` = 1.50, `riasec_s` = 2.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'อุตสาหกรรมศิลป์' AND `faculty` = 'ครุศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.50, `riasec_s` = 1.50, `riasec_e` = 2.00, `riasec_c` = 2.00 WHERE `name` = 'นิติศาสตร์' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.50, `riasec_s` = 1.50, `riasec_e` = 2.50, `riasec_c` = 0.30 WHERE `name` = 'รัฐศาสตร์' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.50, `riasec_s` = 1.50, `riasec_e` = 2.50, `riasec_c` = 0.30 WHERE `name` = 'รัฐประศาสนศาสตร์' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 3.00, `riasec_s` = 1.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'ทัศนศิลป์' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 3.00, `riasec_s` = 1.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'ออกแบบนิเทศศิลป์' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.50, `riasec_s` = 1.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'ภาษาจีน' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.50, `riasec_s` = 1.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'ภาษาญี่ปุ่น' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.50, `riasec_s` = 1.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'ภาษาไทย' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.50, `riasec_s` = 1.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'ภาษาไทยเพื่อการสื่อสารสำหรับชาวต่างประเทศ' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.50, `riasec_s` = 1.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'ภาษาอังกฤษ' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.50, `riasec_s` = 1.50, `riasec_e` = 1.00, `riasec_c` = 0.30 WHERE `name` = 'ภาษาอังกฤษธุรกิจ' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์';
UPDATE `branches` SET `riasec_r` = 2.00, `riasec_i` = 2.50, `riasec_a` = 1.50, `riasec_s` = 1.50, `riasec_e` = 1.00, `riasec_c` = 1.00 WHERE `name` = 'สารสนเทศศาสตร์' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 0.30, `riasec_s` = 1.00, `riasec_e` = 2.50, `riasec_c` = 2.00 WHERE `name` = 'การจัดการ' AND `faculty` = 'วิทยาการจัดการ';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 0.30, `riasec_s` = 1.00, `riasec_e` = 2.50, `riasec_c` = 2.00 WHERE `name` = 'การจัดการ (เทียบโอน)' AND `faculty` = 'วิทยาการจัดการ';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 1.00, `riasec_a` = 0.30, `riasec_s` = 1.00, `riasec_e` = 2.50, `riasec_c` = 3.00 WHERE `name` = 'การบัญชี' AND `faculty` = 'วิทยาการจัดการ';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 1.00, `riasec_a` = 0.30, `riasec_s` = 1.00, `riasec_e` = 2.50, `riasec_c` = 3.00 WHERE `name` = 'การบัญชี (เทียบโอน)' AND `faculty` = 'วิทยาการจัดการ';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.00, `riasec_s` = 1.00, `riasec_e` = 3.00, `riasec_c` = 2.00 WHERE `name` = 'การตลาด' AND `faculty` = 'วิทยาการจัดการ';
UPDATE `branches` SET `riasec_r` = 2.00, `riasec_i` = 2.50, `riasec_a` = 0.30, `riasec_s` = 1.00, `riasec_e` = 2.50, `riasec_c` = 2.00 WHERE `name` = 'คอมพิวเตอร์ธุรกิจ (เทียบโอน)' AND `faculty` = 'วิทยาการจัดการ';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 0.30, `riasec_s` = 2.50, `riasec_e` = 2.50, `riasec_c` = 2.00 WHERE `name` = 'การจัดการการท่องเที่ยว การจัดประชุมและนิทรรศการ (เทียบโอน)' AND `faculty` = 'วิทยาการจัดการ';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 0.30, `riasec_s` = 1.00, `riasec_e` = 2.50, `riasec_c` = 2.00 WHERE `name` = 'การจัดการทรัพยากรมนุษย์' AND `faculty` = 'วิทยาการจัดการ';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 1.00, `riasec_s` = 1.00, `riasec_e` = 3.00, `riasec_c` = 2.00 WHERE `name` = 'การค้าสมัยใหม่' AND `faculty` = 'วิทยาการจัดการ';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 0.30, `riasec_s` = 1.00, `riasec_e` = 2.50, `riasec_c` = 2.00 WHERE `name` = 'การจัดการโลจิสติกส์และโซ่อุปทาน' AND `faculty` = 'วิทยาการจัดการ';
UPDATE `branches` SET `riasec_r` = 2.00, `riasec_i` = 2.50, `riasec_a` = 0.30, `riasec_s` = 1.00, `riasec_e` = 2.50, `riasec_c` = 2.00 WHERE `name` = 'คอมพิวเตอร์ธุรกิจ' AND `faculty` = 'วิทยาการจัดการ';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 0.30, `riasec_s` = 2.50, `riasec_e` = 2.50, `riasec_c` = 2.00 WHERE `name` = 'การจัดการโรงแรมและนวัตกรรมการบริการ' AND `faculty` = 'วิทยาการจัดการ';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 0.30, `riasec_s` = 2.50, `riasec_e` = 2.50, `riasec_c` = 2.00 WHERE `name` = 'การจัดการการท่องเที่ยว การจัดประชุมและนิทรรศการ' AND `faculty` = 'วิทยาการจัดการ';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 0.30, `riasec_a` = 0.30, `riasec_s` = 1.00, `riasec_e` = 2.50, `riasec_c` = 2.00 WHERE `name` = 'เศรษฐศาสตร์ธุรกิจ' AND `faculty` = 'วิทยาการจัดการ';
UPDATE `branches` SET `riasec_r` = 2.50, `riasec_i` = 2.50, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'เกษตรศาสตร์' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี';
UPDATE `branches` SET `riasec_r` = 2.50, `riasec_i` = 2.50, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'คหกรรมศาสตร์' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี';
UPDATE `branches` SET `riasec_r` = 1.50, `riasec_i` = 3.00, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'เคมี' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี';
UPDATE `branches` SET `riasec_r` = 1.50, `riasec_i` = 3.00, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'ชีววิทยา' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี';
UPDATE `branches` SET `riasec_r` = 2.50, `riasec_i` = 2.50, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'เทคนิคการสัตวแพทย์' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี';
UPDATE `branches` SET `riasec_r` = 2.00, `riasec_i` = 2.50, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 1.00 WHERE `name` = 'เทคโนโลยีดิจิทัลมีเดีย' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี';
UPDATE `branches` SET `riasec_r` = 2.00, `riasec_i` = 2.50, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 1.00 WHERE `name` = 'เทคโนโลยีสารสนเทศ' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี';
UPDATE `branches` SET `riasec_r` = 1.50, `riasec_i` = 3.00, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'ฟิสิกส์' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี';
UPDATE `branches` SET `riasec_r` = 2.00, `riasec_i` = 2.50, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 2.50, `riasec_c` = 2.00 WHERE `name` = 'ระบบสารสนเทศเพื่อการจัดการ' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี';
UPDATE `branches` SET `riasec_r` = 2.00, `riasec_i` = 2.50, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 1.00 WHERE `name` = 'วิทยาการคอมพิวเตอร์' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี';
UPDATE `branches` SET `riasec_r` = 2.00, `riasec_i` = 3.00, `riasec_a` = 0.30, `riasec_s` = 2.00, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'วิทยาศาสตร์การกีฬาและการออกกำลังกาย' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี';
UPDATE `branches` SET `riasec_r` = 1.50, `riasec_i` = 3.00, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'เทคโนโลยีอาหาร' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี';
UPDATE `branches` SET `riasec_r` = 1.50, `riasec_i` = 3.00, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'วิทยาศาสตร์และเทคโนโลยีสิ่งแวดล้อม' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี';
UPDATE `branches` SET `riasec_r` = 2.00, `riasec_i` = 3.00, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 1.00 WHERE `name` = 'สถิติประยุกต์และวิทยาการข้อมูล' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี';
UPDATE `branches` SET `riasec_r` = 3.00, `riasec_i` = 2.00, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'วิศวกรรมยานยนต์ไฟฟ้า' AND `faculty` = 'เทคโนโลยีอุตสาหกรรม';
UPDATE `branches` SET `riasec_r` = 3.00, `riasec_i` = 2.00, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 2.50, `riasec_c` = 2.00 WHERE `name` = 'วิศวกรรมโลจิสติกส์' AND `faculty` = 'เทคโนโลยีอุตสาหกรรม';
UPDATE `branches` SET `riasec_r` = 3.00, `riasec_i` = 2.00, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 2.50, `riasec_c` = 2.00 WHERE `name` = 'วิศวกรรมการก่อสร้าง ขนส่งและโลจิสติกส์' AND `faculty` = 'เทคโนโลยีอุตสาหกรรม';
UPDATE `branches` SET `riasec_r` = 3.00, `riasec_i` = 2.00, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 2.50, `riasec_c` = 2.00 WHERE `name` = 'วิศวกรรมการจัดการอุตสาหกรรม' AND `faculty` = 'เทคโนโลยีอุตสาหกรรม';
UPDATE `branches` SET `riasec_r` = 3.00, `riasec_i` = 2.00, `riasec_a` = 0.30, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'วิศวกรรมไฟฟ้าอุตสาหกรรม' AND `faculty` = 'เทคโนโลยีอุตสาหกรรม';
UPDATE `branches` SET `riasec_r` = 2.50, `riasec_i` = 1.50, `riasec_a` = 2.50, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'ออกแบบผลิตภัณฑ์อุตสาหกรรม' AND `faculty` = 'เทคโนโลยีอุตสาหกรรม';
UPDATE `branches` SET `riasec_r` = 2.50, `riasec_i` = 1.50, `riasec_a` = 2.50, `riasec_s` = 0.30, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'สถาปัตยกรรม' AND `faculty` = 'เทคโนโลยีอุตสาหกรรม';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 1.50, `riasec_a` = 0.30, `riasec_s` = 2.50, `riasec_e` = 0.30, `riasec_c` = 0.30 WHERE `name` = 'พยาบาลศาสตร์' AND `faculty` = 'พยาบาลศาสตร์';
UPDATE `branches` SET `riasec_r` = 1.00, `riasec_i` = 3.00, `riasec_a` = 0.30, `riasec_s` = 2.50, `riasec_e` = 0.30, `riasec_c` = 1.00 WHERE `name` = 'อนามัยสิ่งแวดล้อม' AND `faculty` = 'สาธารณสุขศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 1.50, `riasec_a` = 0.30, `riasec_s` = 2.50, `riasec_e` = 0.30, `riasec_c` = 1.00 WHERE `name` = 'อาชีวอนามัยและความปลอดภัย' AND `faculty` = 'สาธารณสุขศาสตร์';
UPDATE `branches` SET `riasec_r` = 0.30, `riasec_i` = 1.50, `riasec_a` = 0.30, `riasec_s` = 2.50, `riasec_e` = 0.30, `riasec_c` = 1.00 WHERE `name` = 'สาธารณสุขชุมชน' AND `faculty` = 'สาธารณสุขศาสตร์';

-- ---- 6) คำถามความสนใจ/งานอดิเรก 36 ข้อ (6 มิติ x 6 ข้อ) ----
-- ผู้ใช้ติ๊กข้อที่ตรงกับตัวเอง (เลือกได้หลายข้อ ไม่ใช่ A/B แบบ MBTI)
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'R', 1, 'ชอบซ่อมแซมหรือประกอบเครื่องใช้ไฟฟ้า/เครื่องจักรด้วยมือตัวเอง' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'R' AND `question_no` = 1);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'R', 2, 'ชอบทำงานฝีมือ งานช่าง หรืองานที่ได้ลงมือทำจริง' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'R' AND `question_no` = 2);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'R', 3, 'สนใจกีฬาหรือกิจกรรมกลางแจ้งที่ใช้แรงกาย' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'R' AND `question_no` = 3);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'R', 4, 'ชอบปลูกต้นไม้ เลี้ยงสัตว์ หรือทำงานเกษตร' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'R' AND `question_no` = 4);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'R', 5, 'ชอบขับ/ควบคุมเครื่องจักรหรือยานพาหนะ' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'R' AND `question_no` = 5);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'R', 6, 'ชอบประกอบโมเดล DIY หรือประดิษฐ์สิ่งของจากวัสดุต่างๆ' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'R' AND `question_no` = 6);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'I', 1, 'ชอบตั้งคำถามและหาคำตอบด้วยการค้นคว้า ทดลอง หรือวิเคราะห์' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'I' AND `question_no` = 1);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'I', 2, 'ชอบอ่านบทความวิทยาศาสตร์ สารคดี หรือข่าวเทคโนโลยี' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'I' AND `question_no` = 2);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'I', 3, 'ชอบเล่นเกมแก้ปริศนา ปัญหาตรรกะ หรือ Sudoku' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'I' AND `question_no` = 3);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'I', 4, 'สนใจว่าสิ่งต่างๆ ทำงานอย่างไร (how things work)' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'I' AND `question_no` = 4);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'I', 5, 'ชอบเขียนโปรแกรมหรือลองผิดลองถูกกับซอฟต์แวร์ใหม่ๆ' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'I' AND `question_no` = 5);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'I', 6, 'ชอบวิเคราะห์ข้อมูล ตัวเลข หรือสถิติเพื่อหาข้อสรุป' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'I' AND `question_no` = 6);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'A', 1, 'ชอบวาดรูป ถ่ายภาพ หรือออกแบบสิ่งต่างๆ' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'A' AND `question_no` = 1);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'A', 2, 'ชอบเล่นดนตรี ร้องเพลง หรือแต่งเพลง' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'A' AND `question_no` = 2);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'A', 3, 'ชอบเขียนเรื่องราว บทกวี หรือคอนเทนต์สร้างสรรค์' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'A' AND `question_no` = 3);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'A', 4, 'ชอบแต่งตัว จัดของ หรือตกแต่งพื้นที่ให้สวยงาม' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'A' AND `question_no` = 4);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'A', 5, 'ชอบดูหนัง ละคร หรือการแสดงศิลปะ' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'A' AND `question_no` = 5);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'A', 6, 'ไม่ชอบทำตามกรอบเดิม ชอบคิดอะไรใหม่ๆ แปลกไปจากคนอื่น' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'A' AND `question_no` = 6);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'S', 1, 'ชอบพูดคุย ให้คำปรึกษา หรือรับฟังปัญหาของเพื่อน' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'S' AND `question_no` = 1);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'S', 2, 'ชอบทำกิจกรรมอาสาสมัครหรือช่วยเหลือชุมชน' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'S' AND `question_no` = 2);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'S', 3, 'ชอบสอนหรืออธิบายให้คนอื่นเข้าใจ' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'S' AND `question_no` = 3);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'S', 4, 'เข้ากับคนง่าย ชอบทำงานเป็นทีมมากกว่าทำคนเดียว' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'S' AND `question_no` = 4);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'S', 5, 'สนใจดูแลสุขภาพหรือความเป็นอยู่ที่ดีของผู้อื่น' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'S' AND `question_no` = 5);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'S', 6, 'ชอบจัดกิจกรรมกลุ่มหรือรวมกลุ่มเพื่อนทำสิ่งต่างๆ' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'S' AND `question_no` = 6);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'E', 1, 'ชอบเป็นผู้นำหรือรับหน้าที่ตัดสินใจแทนกลุ่ม' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'E' AND `question_no` = 1);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'E', 2, 'ชอบเจรจา โน้มน้าว หรือขายของ/ไอเดีย' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'E' AND `question_no` = 2);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'E', 3, 'สนใจอยากมีธุรกิจหรือกิจการเป็นของตัวเอง' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'E' AND `question_no` = 3);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'E', 4, 'ชอบวางแผนกิจกรรมหรือจัดงานอีเวนต์' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'E' AND `question_no` = 4);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'E', 5, 'กล้าพูดต่อหน้าคนหมู่มาก ชอบเป็นจุดสนใจ' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'E' AND `question_no` = 5);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'E', 6, 'ชอบแข่งขันและตั้งเป้าหมายให้ตัวเองไปให้ถึง' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'E' AND `question_no` = 6);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'C', 1, 'ชอบจัดระเบียบข้อมูล ตาราง หรือเอกสารให้เป็นหมวดหมู่' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'C' AND `question_no` = 1);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'C', 2, 'ชอบทำบัญชีรายรับ-รายจ่ายหรือวางแผนการเงิน' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'C' AND `question_no` = 2);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'C', 3, 'ทำงานตามขั้นตอนที่ชัดเจนได้ดี ไม่ชอบความสับสน' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'C' AND `question_no` = 3);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'C', 4, 'ใส่ใจรายละเอียดเล็กๆ น้อยๆ และความถูกต้องแม่นยำ' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'C' AND `question_no` = 4);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'C', 5, 'ชอบใช้โปรแกรมตาราง (เช่น Excel) จัดการข้อมูล' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'C' AND `question_no` = 5);
INSERT INTO `riasec_questions` (letter, question_no, text) SELECT 'C', 6, 'ชอบทำงานที่มีกฎเกณฑ์และมาตรฐานแน่นอน' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `riasec_questions` WHERE `letter` = 'C' AND `question_no` = 6);
