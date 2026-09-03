// ========================================
// FutureWay - register.js
// ฟอร์มสมัครสมาชิก: ส่ง JSON ไป php/register.php แล้วพาไปหน้า login เมื่อสำเร็จ
// ========================================
(function () {
  'use strict';

  const form = document.getElementById('register-form');
  const btn  = form.querySelector('button[type="submit"]');

  document.querySelectorAll('.toggle[data-target]').forEach((icon) => {
    icon.addEventListener('click', () => {
      FW.togglePasswordVisibility(document.getElementById(icon.dataset.target), icon);
    });
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = form.elements;

    if (f.password.value !== f.confirm_password.value) {
      Swal.fire({
        title: 'รหัสผ่านไม่ตรงกัน',
        text: 'กรุณากรอกรหัสผ่านให้ตรงกันทั้งสองช่อง',
        icon: 'warning',
        confirmButtonColor: FW.themeColor('primary'),
      });
      return;
    }

    btn.disabled = true;
    try {
      await FW.api('php/register.php', {
        method: 'POST',
        body: {
          phone:            f.phone.value.trim(),
          firstname:        f.firstname.value.trim(),
          lastname:         f.lastname.value.trim(),
          email:            f.email.value.trim(),
          gender:           f.gender.value,
          password:         f.password.value,
          confirm_password: f.confirm_password.value,
        },
      });
      const result = await Swal.fire({
        title: 'สมัครสมาชิกสำเร็จ!',
        text: 'ข้อมูลของคุณถูกบันทึกเรียบร้อยแล้ว',
        icon: 'success',
        confirmButtonText: 'ไปหน้าเข้าสู่ระบบ',
        confirmButtonColor: FW.themeColor('primary'),
        allowOutsideClick: false,
      });
      if (result.isConfirmed) window.location.href = 'login.html';
    } catch (err) {
      Swal.fire({
        title: 'สมัครไม่สำเร็จ',
        text: err.message,
        icon: 'error',
        confirmButtonText: 'ตกลง',
        confirmButtonColor: FW.themeColor('error'),
      });
    } finally {
      btn.disabled = false;
    }
  });
})();
