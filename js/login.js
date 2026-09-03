// ========================================
// FutureWay - login.js
// ฟอร์มเข้าสู่ระบบ: ส่ง JSON ไป php/login.php แล้วพาไปหน้าหลักเมื่อสำเร็จ
// ========================================
(function () {
  'use strict';

  const form = document.getElementById('login-form');
  const btn  = form.querySelector('button[type="submit"]');

  document.querySelectorAll('.toggle[data-target]').forEach((icon) => {
    icon.addEventListener('click', () => {
      FW.togglePasswordVisibility(document.getElementById(icon.dataset.target), icon);
    });
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const username = form.elements.username.value.trim();
    const password = form.elements.password.value;
    if (!username || !password) {
      Swal.fire({ title: 'กรุณากรอกข้อมูลให้ครบ', icon: 'warning', confirmButtonColor: FW.themeColor('primary') });
      return;
    }

    btn.disabled = true;
    try {
      await FW.api('php/login.php', { method: 'POST', body: { username, password } });
      await Swal.fire({
        title: 'เข้าสู่ระบบสำเร็จ!',
        text: 'กำลังเข้าสู่หน้าหลัก...',
        icon: 'success',
        timer: 1200,
        showConfirmButton: false,
      });
      window.location.href = 'main.html';
    } catch (err) {
      Swal.fire({
        title: 'เข้าสู่ระบบไม่สำเร็จ',
        text: err.message,
        icon: 'error',
        confirmButtonColor: FW.themeColor('error'),
      });
    } finally {
      btn.disabled = false;
    }
  });
})();
