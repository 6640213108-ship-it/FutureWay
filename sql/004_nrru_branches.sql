-- ========================================
-- FutureWay - 004_nrru_branches.sql
-- เพิ่มสาขาปริญญาตรีของ ม.ราชภัฏนครราชสีมา ปีการศึกษา 2569
-- ที่มา: ระบบตลาดหลักสูตร https://misapro.nrru.ac.th/crm/detail/frmdetailmarket.php
--
-- หมายเหตุ:
-- - ครบทุกสาขาที่ระบบ NRRU แสดง (72 รายการ — ตัวเลข 77 บนเว็บนับรวมรหัสซ้ำ
--   ที่ไม่มีข้อมูลแสดง 5 รายการ)
-- - ภาคเทียบโอนแยกเป็นแถวของตัวเอง ต่อท้ายชื่อด้วย (เทียบโอน)
-- - สาขาวิชาเอกครู (ค.บ.) ที่ชื่อชนกับสายวิชาการ เติมวงเล็บ (ครุศาสตร์) กันสับสน
-- - ทุกคำสั่งมี WHERE NOT EXISTS (name+faculty) กันซ้ำ รันซ้ำได้ปลอดภัย
--   (พยาบาลศาสตร์จะถูกข้ามอัตโนมัติถ้ามีแถวเดิมอยู่แล้ว)
-- - ค่า mbti_match / เกรดขั้นต่ำ / น้ำหนัก / is_active กำหนดครบทุกแถว
--   ปรับละเอียดได้ที่หน้าแอดมิน
--
-- ถ้าไม่อยากให้สาขาชุดเดิม (ข้อมูลตัวอย่าง id 1-19) โผล่ในผลแนะนำซ้ำกับ
-- ของ NRRU ให้รันบรรทัดนี้เพิ่ม (เอา -- ข้างหน้าออกก่อน):
-- UPDATE `branches` SET is_active = 0 WHERE id IN (7, 8, 13, 14) AND created_at < '2026-08-01';
-- ========================================


-- ---- ครุศาสตร์ ----

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'การประถมศึกษา', 'ครุศาสตร์', 'ผลิตครูระดับประถมศึกษา', '[\"ISFJ\", \"ESFJ\", \"ENFJ\", \"INFJ\", \"INFP\"]', '0.00', '0.00', '0.00', '2.00', '2.00', '0.00', '0.50', '0.50', '0.50', '1.50', '2.00', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'การประถมศึกษา' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'การศึกษาปฐมวัย', 'ครุศาสตร์', 'ผลิตครูปฐมวัยและการดูแลเด็กเล็ก', '[\"ISFJ\", \"ESFJ\", \"ENFJ\", \"INFJ\", \"INFP\"]', '0.00', '0.00', '0.00', '2.00', '0.00', '0.00', '0.50', '0.50', '0.50', '1.50', '2.00', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'การศึกษาปฐมวัย' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'การศึกษาพิเศษ', 'ครุศาสตร์', 'ผลิตครูสำหรับผู้เรียนที่มีความต้องการพิเศษ', '[\"ISFJ\", \"ESFJ\", \"ENFJ\", \"INFJ\", \"INFP\"]', '0.00', '0.00', '0.00', '0.00', '2.00', '0.00', '0.50', '0.50', '0.50', '1.50', '2.00', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'การศึกษาพิเศษ' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'คณิตศาสตร์ (ครุศาสตร์)', 'ครุศาสตร์', 'ผลิตครูสอนคณิตศาสตร์', '[\"ISTJ\", \"INTJ\", \"INTP\", \"ESTJ\", \"ISFJ\"]', '2.50', '0.00', '0.00', '0.00', '0.00', '0.00', '2.50', '0.50', '0.50', '0.50', '1.00', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'คณิตศาสตร์ (ครุศาสตร์)' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'คอมพิวเตอร์ศึกษา', 'ครุศาสตร์', 'ผลิตครูสอนคอมพิวเตอร์และเทคโนโลยี', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\", \"ESTJ\"]', '2.00', '2.00', '0.00', '0.00', '0.00', '0.00', '2.00', '1.50', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'คอมพิวเตอร์ศึกษา' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'เคมี (ครุศาสตร์)', 'ครุศาสตร์', 'ผลิตครูสอนเคมี', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\"]', '0.00', '2.50', '0.00', '0.00', '0.00', '0.00', '1.50', '2.50', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'เคมี (ครุศาสตร์)' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'จิตวิทยาการปรึกษาและการแนะแนว', 'ครุศาสตร์', 'การให้คำปรึกษาและแนะแนวในโรงเรียน', '[\"INFJ\", \"INFP\", \"ENFJ\", \"ISFJ\", \"ESFJ\"]', '0.00', '0.00', '0.00', '0.00', '2.00', '0.00', '0.50', '0.50', '0.50', '1.50', '2.00', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'จิตวิทยาการปรึกษาและการแนะแนว' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ชีววิทยา (ครุศาสตร์)', 'ครุศาสตร์', 'ผลิตครูสอนชีววิทยา', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\"]', '0.00', '2.50', '0.00', '0.00', '0.00', '0.00', '1.00', '2.50', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ชีววิทยา (ครุศาสตร์)' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ดนตรีศึกษา', 'ครุศาสตร์', 'ผลิตครูสอนดนตรี', '[\"ISFP\", \"INFP\", \"ESFP\", \"ENFP\", \"ISFJ\"]', '0.00', '0.00', '0.00', '0.00', '0.00', '2.50', '0.50', '0.50', '0.50', '0.50', '1.00', '2.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ดนตรีศึกษา' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'เทคโนโลยีและสื่อสารการศึกษา', 'ครุศาสตร์', 'สื่อการสอนและเทคโนโลยีการศึกษา', '[\"ISTP\", \"INTP\", \"ISFP\", \"ENFP\", \"ISTJ\"]', '0.00', '0.00', '0.00', '0.00', '0.00', '1.50', '1.00', '0.50', '0.50', '0.50', '0.50', '2.00', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'เทคโนโลยีและสื่อสารการศึกษา' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'นาฏศิลป์ไทย', 'ครุศาสตร์', 'ผลิตครูสอนนาฏศิลป์ไทย', '[\"ISFP\", \"INFP\", \"ENFP\", \"ESFP\", \"ISTP\"]', '0.00', '0.00', '0.00', '0.00', '0.00', '2.50', '0.50', '0.50', '0.50', '1.50', '0.50', '2.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'นาฏศิลป์ไทย' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'พลศึกษา', 'ครุศาสตร์', 'ผลิตครูสอนพลศึกษาและกีฬา', '[\"ESTP\", \"ESFP\", \"ESFJ\", \"ISTP\", \"ENFP\"]', '0.00', '1.50', '0.00', '0.00', '0.00', '0.00', '0.50', '1.50', '0.50', '0.50', '1.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'พลศึกษา' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'พระพุทธศาสนา', 'ครุศาสตร์', 'ผลิตครูสอนพระพุทธศาสนาและจริยธรรม', '[\"ISFJ\", \"ESFJ\", \"ENFJ\", \"INFJ\", \"INFP\"]', '0.00', '0.00', '0.00', '2.00', '2.00', '0.00', '0.50', '0.50', '0.50', '1.50', '2.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'พระพุทธศาสนา' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ฟิสิกส์ (ครุศาสตร์)', 'ครุศาสตร์', 'ผลิตครูสอนฟิสิกส์', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\"]', '2.00', '2.50', '0.00', '0.00', '0.00', '0.00', '2.00', '2.50', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ฟิสิกส์ (ครุศาสตร์)' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ภาษาจีน (ครุศาสตร์)', 'ครุศาสตร์', 'ผลิตครูสอนภาษาจีน', '[\"INFP\", \"INFJ\", \"ISFJ\", \"ENFP\", \"ESFJ\"]', '0.00', '0.00', '2.00', '0.00', '0.00', '0.00', '0.50', '0.50', '2.00', '1.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ภาษาจีน (ครุศาสตร์)' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ภาษาไทย (ครุศาสตร์)', 'ครุศาสตร์', 'ผลิตครูสอนภาษาไทย', '[\"INFP\", \"INFJ\", \"ISFJ\", \"ENFP\", \"ESFJ\"]', '0.00', '0.00', '0.00', '2.50', '0.00', '0.00', '0.50', '0.50', '0.50', '2.50', '1.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ภาษาไทย (ครุศาสตร์)' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ภาษาอังกฤษ (ครุศาสตร์)', 'ครุศาสตร์', 'ผลิตครูสอนภาษาอังกฤษ', '[\"INFP\", \"INFJ\", \"ISFJ\", \"ENFP\", \"ESFJ\"]', '0.00', '0.00', '2.50', '0.00', '0.00', '0.00', '0.50', '0.50', '2.50', '1.00', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ภาษาอังกฤษ (ครุศาสตร์)' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'วิทยาศาสตร์ทั่วไป', 'ครุศาสตร์', 'ผลิตครูสอนวิทยาศาสตร์', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\"]', '0.00', '2.00', '0.00', '0.00', '0.00', '0.00', '1.50', '2.50', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'วิทยาศาสตร์ทั่วไป' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ศิลปศึกษา', 'ครุศาสตร์', 'ผลิตครูสอนศิลปะ', '[\"ISFP\", \"INFP\", \"ENFP\", \"ESFP\", \"ISTP\"]', '0.00', '0.00', '0.00', '0.00', '0.00', '2.50', '0.50', '0.50', '0.50', '0.50', '1.00', '2.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ศิลปศึกษา' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'สังคมศึกษา', 'ครุศาสตร์', 'ผลิตครูสอนสังคมศึกษา', '[\"ISFJ\", \"ESFJ\", \"ENFJ\", \"INFJ\", \"INFP\"]', '0.00', '0.00', '0.00', '0.00', '2.00', '0.00', '0.50', '0.50', '0.50', '1.50', '2.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'สังคมศึกษา' AND `faculty` = 'ครุศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'อุตสาหกรรมศิลป์', 'ครุศาสตร์', 'ผลิตครูสายช่างอุตสาหกรรม', '[\"ISTP\", \"ESTP\", \"ISFP\", \"ISTJ\", \"ESTJ\"]', '0.00', '0.00', '0.00', '0.00', '0.00', '1.50', '1.50', '0.50', '0.50', '0.50', '0.50', '2.00', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'อุตสาหกรรมศิลป์' AND `faculty` = 'ครุศาสตร์');


-- ---- มนุษยศาสตร์และสังคมศาสตร์ ----

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'นิติศาสตร์', 'มนุษยศาสตร์และสังคมศาสตร์', 'กฎหมายและกระบวนการยุติธรรม', '[\"INTJ\", \"ENTJ\", \"ISTJ\", \"ESTJ\", \"ENTP\"]', '0.00', '0.00', '0.00', '2.50', '2.50', '0.00', '0.50', '0.50', '1.50', '1.50', '2.00', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'นิติศาสตร์' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'รัฐศาสตร์', 'มนุษยศาสตร์และสังคมศาสตร์', 'การเมืองการปกครองและความสัมพันธ์ระหว่างประเทศ', '[\"ENTJ\", \"ENFJ\", \"ENTP\", \"ESTJ\", \"ENFP\"]', '0.00', '0.00', '0.00', '0.00', '2.50', '0.00', '0.50', '0.50', '0.50', '1.50', '2.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'รัฐศาสตร์' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'รัฐประศาสนศาสตร์', 'มนุษยศาสตร์และสังคมศาสตร์', 'การบริหารงานภาครัฐและนโยบายสาธารณะ', '[\"ESTJ\", \"ENTJ\", \"ENFJ\", \"ISTJ\", \"ESFJ\"]', '0.00', '0.00', '0.00', '2.00', '2.50', '0.00', '0.50', '0.50', '0.50', '1.50', '2.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'รัฐประศาสนศาสตร์' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ทัศนศิลป์', 'มนุษยศาสตร์และสังคมศาสตร์', 'ศิลปะการมองเห็น จิตรกรรม ประติมากรรม', '[\"ISFP\", \"INFP\", \"ENFP\", \"ESFP\", \"ISTP\"]', '0.00', '0.00', '0.00', '0.00', '0.00', '2.50', '0.50', '0.50', '0.50', '0.50', '0.50', '3.00', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ทัศนศิลป์' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ออกแบบนิเทศศิลป์', 'มนุษยศาสตร์และสังคมศาสตร์', 'ออกแบบกราฟิกและสื่อสารด้วยภาพ', '[\"ISFP\", \"INFP\", \"ENFP\", \"ESFP\", \"ISTP\"]', '0.00', '0.00', '0.00', '0.00', '0.00', '2.50', '0.50', '0.50', '1.00', '0.50', '0.50', '2.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ออกแบบนิเทศศิลป์' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ภาษาจีน', 'มนุษยศาสตร์และสังคมศาสตร์', 'ภาษาและวัฒนธรรมจีน', '[\"INFP\", \"INFJ\", \"ISFJ\", \"ENFP\", \"ESFJ\"]', '0.00', '0.00', '2.00', '0.00', '0.00', '0.00', '0.50', '0.50', '2.00', '1.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ภาษาจีน' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ภาษาญี่ปุ่น', 'มนุษยศาสตร์และสังคมศาสตร์', 'ภาษาและวัฒนธรรมญี่ปุ่น', '[\"INFP\", \"INFJ\", \"ISFJ\", \"ENFP\", \"ESFJ\"]', '0.00', '0.00', '2.00', '0.00', '0.00', '0.00', '0.50', '0.50', '2.00', '1.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ภาษาญี่ปุ่น' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ภาษาไทย', 'มนุษยศาสตร์และสังคมศาสตร์', 'ภาษาและวรรณกรรมไทย', '[\"INFP\", \"INFJ\", \"ISFJ\", \"ENFP\", \"ESFJ\"]', '0.00', '0.00', '0.00', '2.50', '0.00', '0.00', '0.50', '0.50', '0.50', '2.50', '1.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ภาษาไทย' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ภาษาไทยเพื่อการสื่อสารสำหรับชาวต่างประเทศ', 'มนุษยศาสตร์และสังคมศาสตร์', 'สอนภาษาไทยให้ชาวต่างชาติ', '[\"INFP\", \"INFJ\", \"ISFJ\", \"ENFP\", \"ESFJ\"]', '0.00', '0.00', '2.00', '2.50', '0.00', '0.00', '0.50', '0.50', '1.50', '2.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ภาษาไทยเพื่อการสื่อสารสำหรับชาวต่างประเทศ' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ภาษาอังกฤษ', 'มนุษยศาสตร์และสังคมศาสตร์', 'ภาษาอังกฤษและวรรณคดี', '[\"INFP\", \"INFJ\", \"ISFJ\", \"ENFP\", \"ESFJ\"]', '0.00', '0.00', '2.50', '0.00', '0.00', '0.00', '0.50', '0.50', '2.50', '1.00', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ภาษาอังกฤษ' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ภาษาอังกฤษธุรกิจ', 'มนุษยศาสตร์และสังคมศาสตร์', 'ภาษาอังกฤษเพื่อการทำงานและธุรกิจ', '[\"ENFP\", \"ENFJ\", \"ESTP\", \"ESFJ\", \"ENTJ\"]', '0.00', '0.00', '2.50', '0.00', '0.00', '0.00', '0.50', '0.50', '2.50', '0.50', '1.00', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ภาษาอังกฤษธุรกิจ' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'สารสนเทศศาสตร์', 'มนุษยศาสตร์และสังคมศาสตร์', 'การจัดการสารสนเทศและห้องสมุดดิจิทัล', '[\"ISTJ\", \"INTP\", \"ISFJ\", \"INTJ\", \"ISFP\"]', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '1.50', '0.50', '1.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'สารสนเทศศาสตร์' AND `faculty` = 'มนุษยศาสตร์และสังคมศาสตร์');


-- ---- วิทยาการจัดการ ----

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'การจัดการ', 'วิทยาการจัดการ', 'การบริหารจัดการองค์กรและธุรกิจ', '[\"ENTJ\", \"ESTJ\", \"ENTP\", \"ESTP\", \"ENFJ\"]', '0.00', '0.00', '0.00', '0.00', '2.00', '0.00', '1.00', '0.50', '1.50', '0.50', '1.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'การจัดการ' AND `faculty` = 'วิทยาการจัดการ');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'การจัดการ (เทียบโอน)', 'วิทยาการจัดการ', 'การบริหารจัดการองค์กรและธุรกิจ (ภาคเทียบโอน ปวส.)', '[\"ENTJ\", \"ESTJ\", \"ENTP\", \"ESTP\", \"ENFJ\"]', '0.00', '0.00', '0.00', '0.00', '2.00', '0.00', '1.00', '0.50', '1.50', '0.50', '1.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'การจัดการ (เทียบโอน)' AND `faculty` = 'วิทยาการจัดการ');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'การบัญชี', 'วิทยาการจัดการ', 'บัญชีการเงินและการสอบบัญชี', '[\"ISTJ\", \"ISFJ\", \"ESTJ\", \"INTJ\", \"INTP\"]', '2.50', '0.00', '0.00', '0.00', '0.00', '0.00', '2.50', '0.50', '1.00', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'การบัญชี' AND `faculty` = 'วิทยาการจัดการ');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'การบัญชี (เทียบโอน)', 'วิทยาการจัดการ', 'บัญชีการเงินและการสอบบัญชี (ภาคเทียบโอน ปวส.)', '[\"ISTJ\", \"ISFJ\", \"ESTJ\", \"INTJ\", \"INTP\"]', '2.50', '0.00', '0.00', '0.00', '0.00', '0.00', '2.50', '0.50', '1.00', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'การบัญชี (เทียบโอน)' AND `faculty` = 'วิทยาการจัดการ');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'การตลาด', 'วิทยาการจัดการ', 'กลยุทธ์การตลาดและการสื่อสารแบรนด์', '[\"ENFP\", \"ESTP\", \"ESFP\", \"ENTP\", \"ENFJ\"]', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '1.00', '0.50', '2.00', '0.50', '1.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'การตลาด' AND `faculty` = 'วิทยาการจัดการ');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'คอมพิวเตอร์ธุรกิจ (เทียบโอน)', 'วิทยาการจัดการ', 'เทคโนโลยีสารสนเทศเพื่องานธุรกิจ (ภาคเทียบโอน ปวส.)', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\", \"ESTJ\"]', '2.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2.00', '0.50', '1.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'คอมพิวเตอร์ธุรกิจ (เทียบโอน)' AND `faculty` = 'วิทยาการจัดการ');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'การจัดการการท่องเที่ยว การจัดประชุมและนิทรรศการ (เทียบโอน)', 'วิทยาการจัดการ', 'ธุรกิจท่องเที่ยวและงาน MICE (ภาคเทียบโอน ปวส.)', '[\"ESFP\", \"ENFP\", \"ESFJ\", \"ESTP\", \"ENFJ\"]', '0.00', '0.00', '2.00', '0.00', '0.00', '0.00', '0.50', '0.50', '2.50', '0.50', '1.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'การจัดการการท่องเที่ยว การจัดประชุมและนิทรรศการ (เทียบโอน)' AND `faculty` = 'วิทยาการจัดการ');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'การจัดการทรัพยากรมนุษย์', 'วิทยาการจัดการ', 'การบริหารคนและพัฒนาองค์กร', '[\"ESFJ\", \"ENFJ\", \"ESTJ\", \"ISFJ\", \"ENFP\"]', '0.00', '0.00', '0.00', '0.00', '2.00', '0.00', '0.50', '0.50', '0.50', '1.50', '2.00', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'การจัดการทรัพยากรมนุษย์' AND `faculty` = 'วิทยาการจัดการ');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'การค้าสมัยใหม่', 'วิทยาการจัดการ', 'ธุรกิจค้าปลีกและการค้ายุคดิจิทัล', '[\"ESTP\", \"ENTP\", \"ESTJ\", \"ENFP\", \"ESFP\"]', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '1.50', '0.50', '1.50', '0.50', '1.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'การค้าสมัยใหม่' AND `faculty` = 'วิทยาการจัดการ');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'การจัดการโลจิสติกส์และโซ่อุปทาน', 'วิทยาการจัดการ', 'การขนส่ง คลังสินค้า และโซ่อุปทาน', '[\"ISTJ\", \"ESTJ\", \"INTJ\", \"ENTJ\", \"ISTP\"]', '2.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2.00', '0.50', '1.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'การจัดการโลจิสติกส์และโซ่อุปทาน' AND `faculty` = 'วิทยาการจัดการ');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'คอมพิวเตอร์ธุรกิจ', 'วิทยาการจัดการ', 'เทคโนโลยีสารสนเทศเพื่องานธุรกิจ', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\", \"ESTJ\"]', '2.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2.00', '0.50', '1.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'คอมพิวเตอร์ธุรกิจ' AND `faculty` = 'วิทยาการจัดการ');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'การจัดการโรงแรมและนวัตกรรมการบริการ', 'วิทยาการจัดการ', 'ธุรกิจโรงแรมและงานบริการ', '[\"ESFJ\", \"ESFP\", \"ENFJ\", \"ENFP\", \"ISFJ\"]', '0.00', '0.00', '2.00', '0.00', '0.00', '0.00', '0.50', '0.50', '2.50', '0.50', '1.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'การจัดการโรงแรมและนวัตกรรมการบริการ' AND `faculty` = 'วิทยาการจัดการ');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'การจัดการการท่องเที่ยว การจัดประชุมและนิทรรศการ', 'วิทยาการจัดการ', 'ธุรกิจท่องเที่ยวและงาน MICE', '[\"ESFP\", \"ENFP\", \"ESFJ\", \"ESTP\", \"ENFJ\"]', '0.00', '0.00', '2.00', '0.00', '0.00', '0.00', '0.50', '0.50', '2.50', '0.50', '1.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'การจัดการการท่องเที่ยว การจัดประชุมและนิทรรศการ' AND `faculty` = 'วิทยาการจัดการ');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'เศรษฐศาสตร์ธุรกิจ', 'วิทยาการจัดการ', 'เศรษฐศาสตร์ประยุกต์เพื่อธุรกิจ', '[\"INTJ\", \"ENTJ\", \"INTP\", \"ISTJ\", \"ESTJ\"]', '2.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2.00', '0.50', '0.50', '0.50', '1.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'เศรษฐศาสตร์ธุรกิจ' AND `faculty` = 'วิทยาการจัดการ');


-- ---- วิทยาศาสตร์และเทคโนโลยี ----

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'เกษตรศาสตร์', 'วิทยาศาสตร์และเทคโนโลยี', 'การผลิตพืช สัตว์ และเทคโนโลยีเกษตร', '[\"ISTP\", \"ISTJ\", \"ISFP\", \"ESTP\", \"ISFJ\"]', '0.00', '2.00', '0.00', '0.00', '0.00', '0.00', '1.00', '2.00', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'เกษตรศาสตร์' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'คหกรรมศาสตร์', 'วิทยาศาสตร์และเทคโนโลยี', 'อาหาร โภชนาการ และงานบ้านงานประดิษฐ์', '[\"ISFP\", \"ESFJ\", \"ISFJ\", \"ESFP\", \"INFP\"]', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.50', '1.50', '0.50', '0.50', '0.50', '2.00', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'คหกรรมศาสตร์' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'เคมี', 'วิทยาศาสตร์และเทคโนโลยี', 'เคมีบริสุทธิ์และเคมีประยุกต์', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\"]', '2.00', '2.50', '0.00', '0.00', '0.00', '0.00', '1.50', '2.50', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'เคมี' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ชีววิทยา', 'วิทยาศาสตร์และเทคโนโลยี', 'สิ่งมีชีวิตและเทคโนโลยีชีวภาพ', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\"]', '0.00', '2.50', '0.00', '0.00', '0.00', '0.00', '1.00', '2.50', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ชีววิทยา' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'เทคนิคการสัตวแพทย์', 'วิทยาศาสตร์และเทคโนโลยี', 'ผู้ช่วยสัตวแพทย์และการดูแลสัตว์', '[\"ISFJ\", \"ISTJ\", \"ISFP\", \"INFJ\", \"ESFJ\"]', '0.00', '2.50', '0.00', '0.00', '0.00', '0.00', '1.00', '2.50', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'เทคนิคการสัตวแพทย์' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'เทคโนโลยีดิจิทัลมีเดีย', 'วิทยาศาสตร์และเทคโนโลยี', 'สื่อดิจิทัล กราฟิก และมัลติมีเดีย', '[\"INTP\", \"ISFP\", \"INFP\", \"ISTP\", \"ENFP\"]', '0.00', '0.00', '0.00', '0.00', '0.00', '1.50', '1.50', '0.50', '0.50', '0.50', '0.50', '2.00', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'เทคโนโลยีดิจิทัลมีเดีย' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'เทคโนโลยีสารสนเทศ', 'วิทยาศาสตร์และเทคโนโลยี', 'ระบบไอที เครือข่าย และซอฟต์แวร์', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\", \"ESTJ\"]', '2.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2.00', '1.50', '1.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'เทคโนโลยีสารสนเทศ' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ฟิสิกส์', 'วิทยาศาสตร์และเทคโนโลยี', 'ฟิสิกส์บริสุทธิ์และประยุกต์', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\"]', '2.50', '2.50', '0.00', '0.00', '0.00', '0.00', '2.50', '2.50', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ฟิสิกส์' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ระบบสารสนเทศเพื่อการจัดการ', 'วิทยาศาสตร์และเทคโนโลยี', 'ระบบสารสนเทศสำหรับงานองค์กร', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\", \"ESTJ\"]', '2.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2.00', '0.50', '1.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ระบบสารสนเทศเพื่อการจัดการ' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'วิทยาการคอมพิวเตอร์', 'วิทยาศาสตร์และเทคโนโลยี', 'ทฤษฎีการคำนวณและการพัฒนาซอฟต์แวร์', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\", \"ESTJ\"]', '2.50', '2.00', '0.00', '0.00', '0.00', '0.00', '2.50', '1.50', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'วิทยาการคอมพิวเตอร์' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'วิทยาศาสตร์การกีฬาและการออกกำลังกาย', 'วิทยาศาสตร์และเทคโนโลยี', 'สรีรวิทยาการกีฬาและการฝึกซ้อม', '[\"ESTP\", \"ESFP\", \"ESFJ\", \"ISTP\", \"ENFP\"]', '0.00', '2.00', '0.00', '0.00', '0.00', '0.00', '0.50', '2.00', '0.50', '0.50', '1.00', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'วิทยาศาสตร์การกีฬาและการออกกำลังกาย' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'เทคโนโลยีอาหาร', 'วิทยาศาสตร์และเทคโนโลยี', 'การแปรรูปและควบคุมคุณภาพอาหาร', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\"]', '0.00', '2.00', '0.00', '0.00', '0.00', '0.00', '1.50', '2.50', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'เทคโนโลยีอาหาร' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'วิทยาศาสตร์และเทคโนโลยีสิ่งแวดล้อม', 'วิทยาศาสตร์และเทคโนโลยี', 'การจัดการสิ่งแวดล้อมและมลพิษ', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\"]', '0.00', '2.00', '0.00', '0.00', '0.00', '0.00', '1.00', '2.50', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'วิทยาศาสตร์และเทคโนโลยีสิ่งแวดล้อม' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'สถิติประยุกต์และวิทยาการข้อมูล', 'วิทยาศาสตร์และเทคโนโลยี', 'สถิติ การวิเคราะห์ข้อมูล และ Data Science', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ENTJ\", \"ESTJ\"]', '2.50', '0.00', '0.00', '0.00', '0.00', '0.00', '3.00', '1.50', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'สถิติประยุกต์และวิทยาการข้อมูล' AND `faculty` = 'วิทยาศาสตร์และเทคโนโลยี');


-- ---- เทคโนโลยีอุตสาหกรรม ----

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'วิศวกรรมยานยนต์ไฟฟ้า', 'เทคโนโลยีอุตสาหกรรม', 'เทคโนโลยียานยนต์ไฟฟ้าและระบบขับเคลื่อน', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\", \"ESTJ\"]', '2.00', '2.00', '0.00', '0.00', '0.00', '0.00', '2.00', '2.00', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'วิศวกรรมยานยนต์ไฟฟ้า' AND `faculty` = 'เทคโนโลยีอุตสาหกรรม');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'วิศวกรรมโลจิสติกส์', 'เทคโนโลยีอุตสาหกรรม', 'วิศวกรรมระบบขนส่งและคลังสินค้า', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\", \"ESTJ\"]', '2.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2.00', '1.50', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'วิศวกรรมโลจิสติกส์' AND `faculty` = 'เทคโนโลยีอุตสาหกรรม');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'วิศวกรรมการก่อสร้าง ขนส่งและโลจิสติกส์', 'เทคโนโลยีอุตสาหกรรม', 'งานก่อสร้างและระบบขนส่ง', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\", \"ESTJ\"]', '2.00', '2.00', '0.00', '0.00', '0.00', '0.00', '2.00', '2.00', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'วิศวกรรมการก่อสร้าง ขนส่งและโลจิสติกส์' AND `faculty` = 'เทคโนโลยีอุตสาหกรรม');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'วิศวกรรมการจัดการอุตสาหกรรม', 'เทคโนโลยีอุตสาหกรรม', 'การจัดการกระบวนการผลิตในโรงงาน', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\", \"ESTJ\"]', '2.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2.00', '1.50', '0.50', '0.50', '1.00', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'วิศวกรรมการจัดการอุตสาหกรรม' AND `faculty` = 'เทคโนโลยีอุตสาหกรรม');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'วิศวกรรมไฟฟ้าอุตสาหกรรม', 'เทคโนโลยีอุตสาหกรรม', 'ระบบไฟฟ้ากำลังในงานอุตสาหกรรม', '[\"INTJ\", \"INTP\", \"ISTJ\", \"ISTP\", \"ESTJ\"]', '2.50', '2.50', '0.00', '0.00', '0.00', '0.00', '2.00', '2.00', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'วิศวกรรมไฟฟ้าอุตสาหกรรม' AND `faculty` = 'เทคโนโลยีอุตสาหกรรม');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'ออกแบบผลิตภัณฑ์อุตสาหกรรม', 'เทคโนโลยีอุตสาหกรรม', 'ออกแบบผลิตภัณฑ์และบรรจุภัณฑ์', '[\"ISFP\", \"INFP\", \"ENFP\", \"ESFP\", \"ISTP\"]', '0.00', '0.00', '0.00', '0.00', '0.00', '2.00', '1.00', '0.50', '0.50', '0.50', '0.50', '2.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'ออกแบบผลิตภัณฑ์อุตสาหกรรม' AND `faculty` = 'เทคโนโลยีอุตสาหกรรม');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'สถาปัตยกรรม', 'เทคโนโลยีอุตสาหกรรม', 'ออกแบบอาคารและงานสถาปัตยกรรม (หลักสูตร 5 ปี)', '[\"ISFP\", \"INFP\", \"ISTP\", \"INTP\", \"INTJ\"]', '2.00', '0.00', '0.00', '0.00', '0.00', '2.50', '1.50', '0.50', '0.50', '0.50', '0.50', '2.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'สถาปัตยกรรม' AND `faculty` = 'เทคโนโลยีอุตสาหกรรม');


-- ---- พยาบาลศาสตร์ ----

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'พยาบาลศาสตร์', 'พยาบาลศาสตร์', 'ผลิตพยาบาลวิชาชีพ ดูแลและส่งเสริมสุขภาพ', '[\"ISFJ\", \"ESFJ\", \"INFJ\", \"ENFJ\", \"ISTJ\"]', '2.00', '2.50', '0.00', '0.00', '0.00', '0.00', '0.50', '2.50', '0.50', '0.50', '1.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'พยาบาลศาสตร์' AND `faculty` = 'พยาบาลศาสตร์');


-- ---- สาธารณสุขศาสตร์ ----

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'อนามัยสิ่งแวดล้อม', 'สาธารณสุขศาสตร์', 'สุขาภิบาลและอนามัยสิ่งแวดล้อมชุมชน', '[\"ISFJ\", \"ESFJ\", \"INFJ\", \"ENFJ\", \"ISTJ\"]', '0.00', '2.00', '0.00', '0.00', '0.00', '0.00', '0.50', '2.00', '0.50', '0.50', '1.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'อนามัยสิ่งแวดล้อม' AND `faculty` = 'สาธารณสุขศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'อาชีวอนามัยและความปลอดภัย', 'สาธารณสุขศาสตร์', 'ความปลอดภัยและสุขภาพในการทำงาน (จป.วิชาชีพ)', '[\"ISFJ\", \"ESFJ\", \"INFJ\", \"ENFJ\", \"ISTJ\"]', '0.00', '2.00', '0.00', '0.00', '0.00', '0.00', '1.50', '2.00', '0.50', '0.50', '0.50', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'อาชีวอนามัยและความปลอดภัย' AND `faculty` = 'สาธารณสุขศาสตร์');

INSERT INTO `branches` (name, faculty, description, mbti_match, min_math, min_sci, min_eng, min_thai, min_social, min_art, weight_math, weight_sci, weight_eng, weight_thai, weight_social, weight_art, is_active)
SELECT 'สาธารณสุขชุมชน', 'สาธารณสุขศาสตร์', 'การส่งเสริมสุขภาพและป้องกันโรคในชุมชน', '[\"ISFJ\", \"ESFJ\", \"INFJ\", \"ENFJ\", \"ISTJ\"]', '0.00', '2.00', '0.00', '0.00', '0.00', '0.00', '0.50', '2.00', '0.50', '0.50', '2.00', '0.50', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `name` = 'สาธารณสุขชุมชน' AND `faculty` = 'สาธารณสุขศาสตร์');
