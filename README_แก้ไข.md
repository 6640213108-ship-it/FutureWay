# สรุปสิ่งที่แก้ไข

## ✅ ไฟล์ใหม่ / แก้ไข (นำไป replace/เพิ่มใน repo)
| ไฟล์ | สิ่งที่ทำ |
|---|---|
| `.htaccess` | เดิมชื่อ `htaccess` (ไม่มีจุด) ทำให้ Apache ไม่อ่าน config เลย แก้ชื่อให้ถูก |
| `php/get_history.php` | **ไฟล์ใหม่** — ดึงประวัติผลลัพธ์ทั้งหมดของ user จากตาราง `quiz_results` |
| `History_Results.html` | เดิมเป็น placeholder ล้วน ตอนนี้ดึงข้อมูลจริงผ่าน `get_history.php` และลิงก์ไปหน้า `result.html?id=X` |
| `php/chat.php` | **ไฟล์ใหม่** — proxy เรียก Claude API จากฝั่ง server (อ่าน key จาก env `ANTHROPIC_API_KEY`) |
| `chat.html` | เลิกเรียก Anthropic API ตรงจาก browser (เดิมมี placeholder `YOUR_API_KEY_HERE` ที่รั่วได้) เปลี่ยนไปเรียก `php/chat.php` แทน + ป้องกัน HTML injection ตอนแสดงคำตอบ AI |

**ต้องทำเพิ่มเอง:** ตั้ง environment variable `ANTHROPIC_API_KEY` บน Railway (หรือ hosting ที่ใช้) ให้ `php/chat.php` เรียกได้

## 🗑️ ไฟล์ที่แนะนำให้ลบออกจาก repo (ไม่ต้อง replace เพราะไม่ควรมีอยู่)
- **`php/quiz.php`** — เวอร์ชันเก่า ใช้งานไม่ได้แล้วเพราะ payload ไม่ตรงกับ `save_quiz.php` ปัจจุบัน (ตัวจริงคือ `quiz.html`)
- **`php/debug_session.php`** — เผย session/cookie ของทุกคนแบบไม่มีการป้องกัน เป็นความเสี่ยงด้านความปลอดภัย
- **`css/profile.css`** — เนื้อหาซ้ำกับส่วนท้ายของ `css/main.css` ทุกตัวอักษร ทำให้ `profile.html` โหลด CSS ซ้ำสองรอบโดยไม่จำเป็น (เก็บไว้แค่ใน `main.css` พอ แล้วลบ `<link href="css/profile.css">` ออกจาก `profile.html`)
- **`git`** — ไฟล์เปล่า ไม่มีนามสกุล ไม่มีประโยชน์

## คำสั่งลบไฟล์ (รันในเครื่อง local ที่ clone repo ไว้)
```bash
git rm php/quiz.php
git rm php/debug_session.php
git rm css/profile.css
git rm git
git mv htaccess .htaccess   # ถ้ายังไม่ได้เปลี่ยนชื่อ
git commit -m "ลบไฟล์ที่ไม่ได้ใช้งาน/มีปัญหาด้านความปลอดภัย และแก้ .htaccess"
git push
```

อย่าลืมแก้ `profile.html` เอา `<link rel="stylesheet" href="css/profile.css">` ออกด้วย เพราะ `css/main.css` มีสไตล์ครบอยู่แล้ว
