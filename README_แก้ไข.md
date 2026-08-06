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

---

# เก็บผลลัพธ์ของแต่ละรอบที่ทำแบบทดสอบ (migration 002)

## ปัญหาเดิม
ตาราง `quiz_results` เก็บแค่ **MBTI + สาขาอันดับ 1** ส่วนอันดับ 2-3 ถูกคำนวณใหม่ทุกครั้งที่เปิดหน้า `result.html`
ถ้าไปแก้ข้อมูลตาราง `branches` ทีหลัง **ประวัติเก่าจะแสดงผลเปลี่ยนไป** ไม่ตรงกับที่ผู้ใช้เคยเห็น
และคำตอบรายข้อ (ตอบ A/B ข้อไหนบ้าง) ไม่ได้ถูกเก็บเลย

## สิ่งที่เพิ่ม
| ไฟล์ | สิ่งที่ทำ |
|---|---|
| `sql/002_result_details.sql` | **ไฟล์ใหม่** — เพิ่มคอลัมน์ `avg_grade`, `mbti_detail`, `answers_total` ใน `quiz_results` + สร้างตาราง `quiz_result_branches` และ `quiz_answers` |
| `php/migrate.php` | **ไฟล์ใหม่** — ตัวรัน migration ผ่านเว็บ (เพราะ `mysql.railway.internal` เรียกได้จากใน container เท่านั้น) |
| `php/save_quiz.php` | บันทึกผลลัพธ์เป็น snapshot ครบทั้ง 3 ตารางในหนึ่ง transaction |
| `php/get_result.php` | อ่านสาขาที่แนะนำจาก snapshot แทนการรัน `decision_tree.py` ซ้ำ (แถวเก่าก่อน migration ยัง fallback ไปรัน python เหมือนเดิม) |
| `entrypoint.sh` | เปิด `AllowOverride All` — ของเดิม image ตั้ง `AllowOverride None` ทำให้ `.htaccess` ถูกเมินทั้งไฟล์ |
| `.htaccess` | ห้ามเปิดไฟล์ `.sql` ผ่านเว็บ |

## โครงสร้างที่ได้
```
quiz_results            1 แถว = 1 รอบที่ทำแบบทดสอบ (เกรด, MBTI, เกรดเฉลี่ย, คะแนนราย 4 มิติ)
  └─ quiz_result_branches   3 แถวต่อรอบ = สาขาที่แนะนำอันดับ 1-3 พร้อมคะแนน
  └─ quiz_answers           N แถวต่อรอบ = ตอบข้อไหน เลือก A/B ได้ตัวอักษรอะไร
```
ทั้งสองตารางลูกผูก FK `ON DELETE CASCADE` — ลบผลลัพธ์รอบไหน ข้อมูลลูกหายตามอัตโนมัติ

## วิธีรัน migration บน Railway (เลือกทางใดทางหนึ่ง)

**ทาง A — Railway Data tab (ง่ายสุด ไม่ต้อง deploy)**
เปิด service **MySQL** → แท็บ **Data** → **Query** → วางคำสั่งจาก `sql/002_result_details.sql` ทีละคำสั่ง

**ทาง B — `php/migrate.php`**
1. Railway → service เว็บ → **Variables** → เพิ่ม `MIGRATE_TOKEN` = ข้อความลับที่เดายาก
2. `git push` แล้วรอ deploy เสร็จ
3. เปิด `https://<app>.up.railway.app/php/migrate.php?token=<MIGRATE_TOKEN>`
4. ได้ JSON `"success": true` แปลว่าผ่าน — **แล้วลบ `MIGRATE_TOKEN` ทิ้ง** (หรือลบไฟล์ออกจาก repo)

**ทาง C — mysql client ที่เครื่องตัวเอง**
ใช้ค่าจาก `MYSQL_PUBLIC_URL` ในแท็บ Variables (เป็น public proxy host คนละตัวกับ `mysql.railway.internal` ที่ต่อได้เฉพาะจากใน Railway)
```bash
mysql -h <proxy-host> -P <proxy-port> -u root -p railway < sql/002_result_details.sql
```

รันซ้ำได้ปลอดภัยทุกทาง — คำสั่งที่ทำไปแล้วจะถูกข้าม ไม่ทับข้อมูลเดิม

---

# หน้าผู้ดูแลระบบ (admin.html)

ดูผลการทำแบบทดสอบของผู้ใช้ทุกคน — ชื่อ-นามสกุล, เพศ, ผล MBTI, สาขาที่แนะนำ, เกรดเฉลี่ย, วันที่ทำ

| ไฟล์ | สิ่งที่ทำ |
|---|---|
| `admin.html` | **ไฟล์ใหม่** — ตารางผลลัพธ์ + สถิติรวม + ค้นหา/กรอง + แบ่งหน้า คลิกแถวเพื่อกางดูสาขาครบ 3 อันดับและเกรดรายวิชา |
| `css/admin.css` | **ไฟล์ใหม่** — สไตล์ของหน้านี้ (standalone ไม่ต้องโหลด main.css) |
| `php/admin_config.php` | **ไฟล์ใหม่** — กำหนดว่าใครเป็นแอดมิน + ด่านตรวจสิทธิ์ |
| `php/get_admin_results.php` | **ไฟล์ใหม่** — API ดึงข้อมูลให้ `admin.html` |
| `php/delete_result.php` | **ไฟล์ใหม่** — ลบผลการทำแบบทดสอบรายรอบ (แอดมินเท่านั้น) |
| `php/get_user.php` | เพิ่ม `is_admin` ใน response |
| `profile.html` | เพิ่มเมนู "ผู้ดูแลระบบ" ที่โผล่เฉพาะบัญชีแอดมิน |

## ตั้งค่าว่าใครเป็นแอดมิน
Railway → service เว็บ → **Variables** → เพิ่ม
```
ADMIN_USERS = topza
```
ใส่หลายคนได้ คั่นด้วยลูกน้ำ เช่น `topza,hee` — แก้แล้วมีผลทันที ไม่ต้อง deploy ใหม่

เลือกใช้ env แทนการเพิ่มคอลัมน์ `is_admin` ในตาราง `users` เพราะไม่ต้องรัน migration เพิ่ม
ถ้าอยากย้ายไปเก็บใน DB ทีหลัง แก้แค่ฟังก์ชัน `isAdmin()` ใน `php/admin_config.php` ตัวเดียว

**ถ้าไม่ตั้ง `ADMIN_USERS` จะไม่มีใครเข้าหน้านี้ได้เลย** (ปลอดภัยไว้ก่อน) — endpoint จะตอบ 403

## การตรวจสิทธิ์
`get_admin_results.php` เรียก `requireAdminJson()` ก่อนแตะ DB ทุกครั้ง
- ยังไม่ login → **401**
- login แล้วแต่ไม่ใช่แอดมิน → **403**

เมนูผู้ดูแลระบบใน `profile.html` **ไม่ได้เขียนไว้ใน HTML** แต่ถูกสร้างด้วย JS เฉพาะตอน `is_admin === true`
ที่ไม่ใช้วิธีวางไว้แล้วซ่อนด้วย CSS เพราะ `.menu-item { display: flex }` ใน `main.css` ทับ attribute `hidden`
และถ้า CSS ถูก browser cache ไว้ เมนูจะโผล่ให้ทุกคนเห็นทันที — การไม่สร้าง element เลยจึงชัวร์กว่า

ถึงอย่างนั้นด่านจริงยังอยู่ฝั่ง server เสมอ ต่อให้พิมพ์ `admin.html` ตรงๆ หรือยิง API เองก็ได้แค่ 403

## การลบผลลัพธ์
ปุ่มถังขยะท้ายแถวในตาราง → ยืนยันก่อน 1 ครั้ง (บอกชื่อผู้ใช้ + วันที่ที่จะลบ) → ลบถาวร

`php/delete_result.php` รับเฉพาะ **POST + body เป็น JSON** ไม่รับ GET เพราะถ้าเปิดให้ลบผ่าน GET
แค่หลอกให้แอดมินโหลดหน้าที่ฝัง `<img src=".../delete_result.php?id=1">` ข้อมูลก็หายแล้ว
ส่วนการบังคับ `Content-Type: application/json` กันฟอร์มจากเว็บอื่นยิงข้ามโดเมนเข้ามา

ลบแถวใน `quiz_results` แถวเดียว — คำตอบรายข้อและสาขาที่แนะนำของรอบนั้นหายตามเอง
เพราะตารางลูกผูก FK `ON DELETE CASCADE` ไว้ และทุกครั้งที่ลบจะเขียน log ไว้ใน Railway ว่าแอดมินคนไหนลบผลของใคร

## ⚠️ เรื่องที่ควรแก้ต่อ (คนละเรื่องกับ migration นี้)
`decision_tree.py:25` และ `php/db_config.php:13` ยัง hardcode รหัสผ่าน MySQL ไว้ในโค้ด ซึ่งอยู่ใน git history ไปแล้ว
ควร **rotate รหัสผ่านบน Railway** แล้วให้ทั้งสองไฟล์อ่านจาก environment variable อย่างเดียว ไม่ต้องมี fallback
