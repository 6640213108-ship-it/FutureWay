// ========================================
// FutureWay - profile.js
// หน้าการตั้งค่า: แสดงชื่อ/อีเมล, เมนูผู้ดูแลระบบ (เฉพาะแอดมิน), ปุ่มออกจากระบบ
// ========================================
(function () {
  'use strict';

  FW.requireLogin()
    .then((user) => {
      FW.$('profile-name').textContent = user.fullname;

      // ซ่อนอีเมลได้จากหน้า "ความเป็นส่วนตัว" (settings.show_email)
      const showEmail = !user.settings || user.settings.show_email !== false;
      FW.$('profile-email').textContent = showEmail ? (user.email || '') : '';

      // สร้างเมนูผู้ดูแลระบบเฉพาะตอนเป็นแอดมิน — ไม่วางไว้ใน HTML แล้วซ่อนด้วย CSS
      if (user.is_admin === true) {
        FW.$('logout-item').insertAdjacentHTML('beforebegin', `
          <a href="admin.html" class="menu-item">
            <div class="menu-icon purple"><i class="fas fa-user-shield"></i></div>
            <div class="menu-text">
              <span class="menu-title">ผู้ดูแลระบบ</span>
              <span class="menu-sub">ดูผลการทำแบบทดสอบของผู้ใช้ทุกคน</span>
            </div>
            <i class="fas fa-chevron-right menu-arrow"></i>
          </a>`);
      }
    })
    .catch(() => { FW.$('profile-name').textContent = 'โหลดข้อมูลไม่สำเร็จ'; });

  FW.$('logout-item').addEventListener('click', (e) => {
    e.preventDefault();
    FW.logout();
  });
})();
