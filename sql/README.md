# Database migrations

ไฟล์ในโฟลเดอร์นี้คือ schema และข้อมูลตั้งต้นของ FutureWay รันตามลำดับเลขหน้าไฟล์ด้วย
`php php/migrate.php` (หรือผ่านเว็บด้วย `MIGRATE_TOKEN` ดูคอมเมนต์ในไฟล์นั้น)

| ไฟล์ | เนื้อหา |
|---|---|
| `001_schema.sql` | ตาราง users, quiz_results, mbti_questions, branches |
| `002_result_details.sql` | quiz_result_branches, quiz_answers (snapshot ผลรายรอบ) |
| `003_user_settings.sql` | ตาราง user_settings + คอลัมน์ phone/address |
| `004_nrru_branches.sql` | สาขาปริญญาตรีของ NRRU ปีการศึกษา 2569 (72 สาขา) |
| `005_branch_tuning.sql` | ปรับ mbti_match / เกรดขั้นต่ำ / น้ำหนักวิชาของแต่ละสาขา |
| `007_mbti_questions_28.sql` | คำถาม MBTI 28 ข้อ (4 มิติ x 7 ข้อ) |
| `008_riasec.sql` | คำถามและน้ำหนัก RIASEC สำหรับโหมด "ไม่ทราบเกรด" |
| `009_category_recommendations.sql` | เก็บผลแนะนำเป็นหมวดหมู่ (คณะ) สูงสุด 3 หมวด |
| `010_dedupe_branches.sql` | ล้างสาขาซ้ำ + เพิ่ม unique key |

## กติกา

- ชื่อไฟล์ต้องเป็น `NNN_ชื่อ.sql` — `migrate.php` จะรันเฉพาะไฟล์ที่ตรงรูปแบบนี้
  และบันทึกชื่อไฟล์ที่รันแล้วไว้ในตาราง `schema_migrations` (รันซ้ำจะถูกข้าม)
- **ห้ามแก้หรือเปลี่ยนชื่อไฟล์ที่ deploy ไปแล้ว** ให้เพิ่มไฟล์ใหม่เลขถัดไปแทน
  (เลข `006` ว่างอยู่โดยตั้งใจ — การเลื่อนเลขจะทำให้ไฟล์เดิมถูกนับเป็นไฟล์ใหม่และรันซ้ำ)
- ทุกคำสั่งควร idempotent (`CREATE TABLE IF NOT EXISTS`, `INSERT ... WHERE NOT EXISTS`)
  เพราะ migrate.php รันได้หลายครั้งอย่างปลอดภัย
