// ========================================
// FutureWay - main.js
// หน้าหลัก: ทักทายผู้ใช้ + render เมนูบุคลิกภาพ MBTI ทั้ง 16 แบบจาก data.js
// (ต้องรันก่อน lightbox.js เพราะ lightbox ผูก event กับปุ่ม .mbti-item ที่สร้างตรงนี้)
// ========================================
(function () {
  'use strict';

  // เมนู MBTI — render ตั้งแต่ตอนโหลดสคริปต์ (สคริปต์อยู่ท้าย body, DOM พร้อมแล้ว)
  FW.$('mbti-grid').innerHTML = MBTI_CODES.map((code) => `
    <button type="button" class="mbti-item" data-src="images/${code}.jpg">
      <span class="mbti-code">${code}</span>
      <span class="mbti-desc">${FW.escapeHtml(MBTI_TYPES[code].title)}</span>
    </button>`).join('');

  // ทักทาย
  FW.requireLogin()
    .then((user) => { FW.$('welcome-text').textContent = 'สวัสดี, ' + user.fullname; })
    .catch(() => { FW.$('welcome-text').textContent = 'โหลดข้อมูลผู้ใช้ไม่สำเร็จ'; });
})();
