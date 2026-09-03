# FutureWay

[![CI](https://github.com/6640213108-ship-it/FutureWay/actions/workflows/ci.yml/badge.svg)](https://github.com/6640213108-ship-it/FutureWay/actions/workflows/ci.yml)
![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![Python 3](https://img.shields.io/badge/Python-3.x-3776AB?logo=python&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)
![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)

ระบบแนะนำสาขาวิชาที่เหมาะสมกับผู้เรียน โดยวิเคราะห์จากแบบทดสอบบุคลิกภาพ **MBTI (28 ข้อ)**
ร่วมกับ **เกรดเฉลี่ยรายวิชา** หรือ **ความสนใจ/งานอดิเรกแบบ RIASEC** (สำหรับผู้ที่ยังไม่ทราบเกรด)
แล้วจับคู่กับข้อมูลสาขาปริญญาตรีจริงของมหาวิทยาลัยราชภัฏนครราชสีมา (NRRU) ปีการศึกษา 2569

## ฟีเจอร์หลัก

- **สมัคร / เข้าสู่ระบบ** ด้วยเบอร์มือถือ + รหัสผ่าน หรือบัญชี Google (Google Identity Services)
- **แบบทดสอบ 2 ขั้น** กรอกเกรด 6 วิชา (หรือเลือกความชอบแบบ RIASEC แทน) แล้วตอบคำถาม MBTI 28 ข้อ
- **Decision tree** ฝั่ง Python คำนวณรหัส MBTI จากคำตอบดิบ ให้คะแนนทุกสาขา แล้วจัดกลุ่มเป็น 3 คณะที่เหมาะสมที่สุด
- **หน้าผลลัพธ์** การ์ดบุคลิกภาพ + โปสเตอร์สาขาที่เหมาะสมของแต่ละ MBTI + สรุปเกรด/โปรไฟล์ความสนใจ
- **ประวัติผลลัพธ์** ย้อนหลังของผู้ใช้แต่ละคน (เก็บเป็น snapshot ไม่เปลี่ยนตามข้อมูลสาขาที่แก้ทีหลัง)
- **หน้าผู้ดูแลระบบ** สถิติรวม ค้นหา/กรอง ลบผลรายรอบ และส่งออกเป็น Excel (.xlsx)
- **จัดการบัญชี** แก้ไขโปรไฟล์ เปลี่ยนรหัสผ่าน ตั้งค่าการแจ้งเตือน ดาวน์โหลด/ลบข้อมูลของตัวเอง (PDPA-friendly)

## สถาปัตยกรรม

```
Browser (HTML + CSS + Vanilla JS)
   │  fetch JSON
   ▼
PHP 8.3 API  (php/*.php)  ──── mysqli ────►  MySQL
   │  proc_open (JSON ผ่าน stdin/stdout)
   ▼
Python decision_tree.py  ──── mysql-connector ────►  MySQL
```

| ส่วน | เทคโนโลยี |
|---|---|
| Front-end | HTML, CSS (design tokens), Vanilla JavaScript — ไม่มี build step |
| Back-end API | PHP 8.3, mysqli + prepared statements, session-based auth |
| Decision engine | Python 3 (`decision_tree.py`) มี unit test 55 เคส |
| ฐานข้อมูล | MySQL 8 (migration ใน `sql/`) |
| Deploy | Docker (php:8.3-apache + python3) บน Railway |
| CI | GitHub Actions: `php -l` ทุกไฟล์ + `python -m unittest` |

## โครงสร้างโปรเจกต์

```
├── *.html                  หน้าเว็บ (มาร์กอัปล้วน ไม่มี inline script/style)
├── css/
│   ├── tokens.css          design tokens (สี ระยะ มุมโค้ง) ที่เดียวของทั้งโปรเจกต์
│   ├── auth.css            หน้าก่อนล็อกอิน (index / login / register)
│   ├── app.css             ฐานของหน้าหลังล็อกอิน (header, bottom nav, toast)
│   └── <page>.css          สไตล์เฉพาะหน้า
├── js/
│   ├── common.js           FW.* helpers: api, requireLogin, showToast, bottom nav, back header
│   ├── data.js             MBTI_TYPES, GRADE_SUBJECTS, RIASEC_DIMENSIONS (แหล่งเดียว)
│   └── <page>.js           สคริปต์ของแต่ละหน้า
├── php/
│   ├── bootstrap.php       error config, session, JSON helpers — require เป็นบรรทัดแรกทุก endpoint
│   ├── db_config.php       เชื่อมต่อ MySQL จาก environment variable
│   ├── user_session.php    ผู้ใช้ที่ล็อกอิน + user_settings
│   ├── admin_auth.php      ตรวจสิทธิ์ผู้ดูแลระบบ
│   ├── decision_tree_runner.php  เรียก decision_tree.py ผ่าน proc_open
│   ├── migrate.php         ตัวรัน migration (CLI หรือเว็บ + token)
│   └── *.php               API endpoints (JSON ทั้งหมด)
├── decision_tree.py        ตรรกะคำนวณ MBTI + ให้คะแนนสาขา
├── tests/                  unit tests ของ decision_tree.py (unittest, ไม่ต้องมี DB)
├── sql/                    migration ตามลำดับ (ดู sql/README.md)
├── docs/                   ข้อมูลอ้างอิงสาขา NRRU และตาราง MBTI → สาขา
├── Dockerfile / entrypoint.sh
└── .github/workflows/ci.yml
```

## เริ่มใช้งานในเครื่อง

ต้องมี PHP 8.1+, Python 3.10+, MySQL 8

```bash
git clone https://github.com/6640213108-ship-it/FutureWay.git
cd FutureWay

# 1) ตั้งค่า environment
cp .env.example .env            # แล้วเติม MYSQLPASSWORD ฯลฯ

# 2) Python dependency
python -m venv venv
venv/bin/pip install -r requirements.txt      # Windows: venv\Scripts\pip

# 3) สร้างฐานข้อมูลแล้วรัน migration
mysql -u root -p -e "CREATE DATABASE futureway CHARACTER SET utf8mb4"
set -a; source .env; set +a                   # โหลด .env เข้า shell (Windows: ตั้งใน System Properties)
php php/migrate.php

# 4) รันเว็บ
php -S localhost:8000
```

เปิด <http://localhost:8000>

หรือรันด้วย Docker ทั้งชุด:

```bash
docker build -t futureway .
docker run -p 8080:80 --env-file .env futureway
```

### Environment variables

| ตัวแปร | จำเป็น | คำอธิบาย |
|---|---|---|
| `MYSQLHOST` `MYSQLPORT` `MYSQLUSER` `MYSQLPASSWORD` `MYSQLDATABASE` | ✅ | การเชื่อมต่อฐานข้อมูล (Railway inject ให้อัตโนมัติ) |
| `GOOGLE_CLIENT_ID` | สำหรับปุ่ม Google | OAuth Client ID จาก Google Cloud Console |
| `ADMIN_USERS` | สำหรับหน้าแอดมิน | username ที่เป็นผู้ดูแลระบบ คั่นด้วยลูกน้ำ |
| `MIGRATE_TOKEN` | ถ้ารัน migrate ผ่านเว็บ | `curl -X POST -H "Authorization: Bearer $MIGRATE_TOKEN" https://<app>/php/migrate.php` |
| `PYTHON_BIN` | ไม่บังคับ | path ของ Python ถ้าไม่ได้อยู่ใน `venv/` หรือ PATH |

ไม่มีความลับใด ๆ hardcode อยู่ในโค้ด ทุกค่าอ่านจาก environment เท่านั้น

## การทดสอบ

```bash
python -m unittest discover -s tests -v      # 55 tests, ไม่ต้องต่อฐานข้อมูล
composer lint                                 # php -l ทุกไฟล์ใน php/
```

CI บน GitHub Actions รันทั้งสองอย่างนี้ทุกครั้งที่ push

## API (สรุป)

ทุก endpoint ตอบ JSON รูปแบบ `{"success": true, ...}` หรือ `{"success": false, "error": "..."}`
พร้อม HTTP status ที่สอดคล้อง (400 / 401 / 403 / 404 / 409 / 500) endpoint ที่แก้ไขข้อมูลรับเฉพาะ `POST` + `Content-Type: application/json`

| Endpoint | หน้าที่ |
|---|---|
| `login.php` `register.php` `logout.php` `google_login.php` | ยืนยันตัวตน |
| `get_user.php` `update_profile.php` `change_password.php` `set_gender.php` | บัญชีผู้ใช้ |
| `get_settings.php` `update_settings.php` `export_my_data.php` `delete_my_data.php` | การตั้งค่าและข้อมูลส่วนตัว |
| `get_questions.php` `get_riasec_questions.php` `save_quiz.php` `get_result.php` `get_history.php` | แบบทดสอบและผลลัพธ์ |
| `get_admin_results.php` `export_admin_results.php` `delete_result.php` | ผู้ดูแลระบบ |

## License

[MIT](LICENSE)
