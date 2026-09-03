# FutureWay

ระบบแนะนำสาขาวิชาและมหาวิทยาลัยที่เหมาะสมกับผู้เรียน โดยวิเคราะห์จากผลแบบทดสอบบุคลิกภาพ MBTI (28 ข้อ) ร่วมกับเกรดเฉลี่ยรายวิชา แล้วจับคู่กับข้อมูลสาขาจริงของมหาวิทยาลัยราชภัฏนครราชสีมา (NRRU)

## ฟีเจอร์หลัก

- **สมัคร/เข้าสู่ระบบ** ด้วยบัญชีในระบบ หรือ Google OAuth (Google Identity Services)
- **แบบทดสอบ MBTI** 28 ข้อ พร้อมกรอกเกรดเฉลี่ยรายวิชา
- **เครื่องมือแนะนำสาขา (decision tree)** เขียนด้วย Python วิเคราะห์จากบุคลิกภาพ + เกรด แล้วให้คะแนนเทียบกับเกณฑ์ของแต่ละสาขา คืนผล 3 อันดับแรกที่เหมาะสมที่สุด
- **ประวัติผลการทดสอบ** ย้อนหลังของผู้ใช้แต่ละคน พร้อมส่งออกเป็นไฟล์ Excel
- **หน้าแอดมิน** ดูภาพรวมผลลัพธ์ผู้ใช้ทั้งหมด กรอง/ส่งออกข้อมูล
- **จัดการโปรไฟล์** แก้ไขข้อมูลส่วนตัว เปลี่ยนรหัสผ่าน ตั้งค่าบัญชี

## สถาปัตยกรรม / เทคโนโลยีที่ใช้

| ส่วน | เทคโนโลยี |
|---|---|
| Frontend | HTML, CSS, Vanilla JavaScript |
| Backend | PHP 8.3 (mysqli, prepared statements) |
| Decision engine | Python (`decision_tree.py`) เรียกผ่าน `proc_open` จากฝั่ง PHP |
| Database | MySQL |
| Deploy | Docker (Apache + PHP) บน Railway |

โครงสร้างเว็บเป็นแบบ multi-page (server-rendered ผ่าน static HTML + fetch เรียก PHP API เป็น JSON) ไม่ได้ใช้ framework ฝั่งหน้าบ้าน เพื่อให้ deploy และแก้ไขได้ง่ายบน shared hosting/Railway

## โครงสร้างโปรเจกต์

```
├── *.html            หน้าเว็บแต่ละหน้า (index, login, quiz, result, admin, ...)
├── css/               สไตล์แยกตามหน้า
├── js/                สคริปต์ฝั่งหน้าบ้าน (เช่น Google login)
├── php/               REST-like API endpoints + การเชื่อมต่อฐานข้อมูล
├── decision_tree.py   ตรรกะแนะนำสาขาจาก MBTI + เกรด
├── sql/               ไฟล์ schema/migration ตามลำดับ (001 = โครงสร้างเริ่มต้น)
├── Dockerfile / entrypoint.sh   ตั้งค่า container สำหรับ deploy
```

## เริ่มใช้งานในเครื่อง (Local setup)

1. ติดตั้ง MySQL แล้วรัน migration ตามลำดับ: `sql/001_schema.sql`, `002_...`, `003_...` ไปเรื่อยๆ
2. ติดตั้ง Python dependency: `pip install -r requirements.txt`
3. ตั้ง environment variable (ดูรายละเอียดด้านล่าง) แล้วรันผ่าน Apache/PHP built-in server:
   ```bash
   php -S localhost:8000
   ```
4. เปิด `http://localhost:8000`

หรือรันด้วย Docker ทั้งชุด:
```bash
docker build -t futureway .
docker run -p 8080:80 --env-file .env futureway
```

### Environment variables ที่ต้องตั้ง

| ตัวแปร | คำอธิบาย |
|---|---|
| `MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`, `MYSQLPORT` | การเชื่อมต่อฐานข้อมูล |
| `GOOGLE_CLIENT_ID` | Google OAuth Client ID (สมัครที่ [Google Cloud Console](https://console.cloud.google.com)) |
| `ADMIN_USERS` | รายชื่อ username ที่เป็นแอดมิน คั่นด้วยลูกน้ำ เช่น `topza,hee` |

ไม่มีค่า default ที่เป็นความลับ (secret) hardcode อยู่ในโค้ด — ต้องตั้งค่าตัวแปรเหล่านี้เองเสมอ
