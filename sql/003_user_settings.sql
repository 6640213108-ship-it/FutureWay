-- ========================================
-- FutureWay - Migration 003
-- รองรับเมนูในหน้า profile.html ให้ใช้งานได้จริง
--   - Edit Profile      -> ต้องมีที่เก็บเบอร์โทร / ที่อยู่ (เดิม users มีแค่ชื่อ-อีเมล)
--   - การแจ้งเตือน       -> ตาราง user_settings เก็บว่าผู้ใช้เปิด/ปิดอะไรไว้บ้าง
--   - ความเป็นส่วนตัว    -> เก็บในตารางเดียวกัน (แถวละ 1 ผู้ใช้)
--
-- รันไฟล์นี้ 1 ครั้งบน MySQL ของ Railway (ดูวิธีรันใน php/migrate.php)
--
-- ไม่รันก็ได้: หน้าเว็บสร้าง user_settings และเพิ่มคอลัมน์ phone/address ให้เองอัตโนมัติ
-- ตอนเปิดหน้าตั้งค่าครั้งแรก ไฟล์นี้มีไว้ให้ schema เป็นลายลักษณ์อักษรและมี FK ครบ
--
-- ถ้าจะ copy ไปวางรันเองในเครื่องมือ SQL: ต้องรัน "ทีละคำสั่ง" (คั่นด้วย ;)
-- เครื่องมือบางตัวยิงได้ครั้งละคำสั่งเดียว วางสอง ALTER พร้อมกันจะฟ้อง
-- "You have an error in your SQL syntax ... near 'ALTER TABLE' at line 2"
-- ========================================

-- ---- 1) คอลัมน์เพิ่มใน users ----
-- แยกทีละคำสั่งเหมือน migration 002 เพราะ migrate.php ข้าม error 1060
-- (คอลัมน์มีอยู่แล้ว) เป็นรายคำสั่ง ถ้ารวมเป็น ALTER เดียวแล้วตัวแรกซ้ำ จะ fail ทั้งก้อน
ALTER TABLE `users` ADD COLUMN `phone`   varchar(20)  DEFAULT NULL AFTER `email`;
ALTER TABLE `users` ADD COLUMN `address` varchar(255) DEFAULT NULL AFTER `phone`;

-- ---- 2) การตั้งค่าของผู้ใช้แต่ละคน ----
-- 1 ผู้ใช้ = 1 แถว (user_id เป็น PRIMARY KEY ไม่ใช่ AUTO_INCREMENT)
-- ผู้ใช้ที่ยังไม่เคยเข้าหน้าตั้งค่า จะยังไม่มีแถวในตารางนี้ -> ฝั่ง PHP ถือว่าใช้ค่าเริ่มต้น
CREATE TABLE IF NOT EXISTS `user_settings` (
  `user_id`         int(11)    NOT NULL,
  `notify_result`   tinyint(1) NOT NULL DEFAULT 1 COMMENT 'ผลการทำแบบทดสอบพร้อมแล้ว',
  `notify_news`     tinyint(1) NOT NULL DEFAULT 1 COMMENT 'ข่าวสาร/สาขาใหม่',
  `notify_reminder` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'เตือนให้กลับมาทำแบบทดสอบ',
  `notify_email`    tinyint(1) NOT NULL DEFAULT 1 COMMENT 'ส่งเข้าอีเมลด้วย',
  `notify_push`     tinyint(1) NOT NULL DEFAULT 0 COMMENT 'แจ้งเตือนบนเบราว์เซอร์',
  `show_email`      tinyint(1) NOT NULL DEFAULT 1 COMMENT 'แสดงอีเมลบนหน้าโปรไฟล์',
  `allow_stats`     tinyint(1) NOT NULL DEFAULT 1 COMMENT 'ยินยอมให้ใช้ข้อมูลแบบไม่ระบุตัวตนทำสถิติ',
  `updated_at`      timestamp  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_us_user` FOREIGN KEY (`user_id`)
      REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
