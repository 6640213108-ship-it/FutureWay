-- ========================================
-- FutureWay - 009_category_recommendations.sql
-- เปลี่ยนผลแนะนำจาก "3 สาขาที่ดีที่สุด" (อาจกระจุกอยู่คณะเดียวกัน)
-- เป็น "3 หมวดหมู่ (คณะ) ที่เหมาะที่สุด แต่ละหมวดโชว์หลายสาขาข้างใน"
-- เพิ่มโอกาสให้เห็นตัวเลือกที่หลากหลายขึ้น แทนที่จะเห็นแค่ 3 สาขาเดิมๆ
-- ที่อาจอยู่คณะเดียวกันหมด
-- ========================================

ALTER TABLE `quiz_result_branches`
  ADD COLUMN `category_rank` tinyint(4) NOT NULL DEFAULT 1
  COMMENT 'อันดับหมวดหมู่ (คณะ) ที่แนะนำ 1-3' AFTER `result_id`;

-- unique key เดิมคุมแค่ (result_id, rank_no) ไม่พอแล้วเพราะตอนนี้ rank_no
-- ซ้ำกันได้ข้ามหมวดหมู่ (เช่น หมวด 1 อันดับ 1 กับหมวด 2 อันดับ 1)
ALTER TABLE `quiz_result_branches` DROP INDEX `uq_result_rank`;
ALTER TABLE `quiz_result_branches`
  ADD UNIQUE KEY `uq_result_cat_rank` (`result_id`, `category_rank`, `rank_no`);

ALTER TABLE `quiz_result_branches`
  MODIFY COLUMN `rank_no` tinyint(4) NOT NULL
  COMMENT 'อันดับสาขาภายในหมวดหมู่นั้น (1 = เข้ากันที่สุดในหมวดหมู่)';
