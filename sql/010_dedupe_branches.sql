-- ========================================
-- FutureWay - 010_dedupe_branches.sql
-- ล้างแถวสาขาที่ชื่อ+คณะซ้ำกันในตาราง branches (ของเก่าที่ตกค้างมาจากการรัน
-- migration ซ้ำก่อนจะมีการกันซ้ำแบบ WHERE NOT EXISTS) แล้วค่อยเพิ่มกฎห้ามซ้ำ
-- อีกครั้งให้แน่ใจว่าติดจริง (005_branch_tuning.sql เคยพยายามแล้วแต่ชนข้อมูล
-- ซ้ำเดิม เลยไม่เคยติดกฎนี้จริงๆ)
--
-- เก็บแถวที่ id น้อยที่สุดไว้ (แถวแรกสุดที่เคยถูกสร้าง) ลบแถวซ้ำที่เหลือทิ้ง
-- ไม่มี foreign key อ้างถึง branches.id ตรงๆ (quiz_results/quiz_result_branches
-- เก็บชื่อ/คณะ/คะแนนไว้เป็น snapshot แยกอยู่แล้ว) จึงลบได้อย่างปลอดภัย
-- ========================================

DELETE b1 FROM `branches` b1
INNER JOIN `branches` b2
  ON b1.name = b2.name AND b1.faculty = b2.faculty AND b1.id > b2.id;

-- กันไม่ให้สาขาชื่อ+คณะเดียวกันถูกเพิ่มซ้ำได้อีกในอนาคต
ALTER TABLE `branches` ADD UNIQUE KEY `uq_branch_name_faculty` (`name`, `faculty`);
