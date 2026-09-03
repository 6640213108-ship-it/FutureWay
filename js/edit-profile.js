// ========================================
// FutureWay - edit-profile.js
// หน้าแก้ไขโปรไฟล์: เติมข้อมูลเดิม -> ส่ง JSON ไป php/update_profile.php
// ========================================
(function () {
  'use strict';

  const $ = FW.$;

  function showAlert(id, message) {
    const box = $(id);
    box.querySelector('span').textContent = message || '';
    box.hidden = !message;
  }

  // ทาสีปุ่มเพศที่เลือกอยู่ (เผื่อเบราว์เซอร์ที่ยังไม่รองรับ CSS :has)
  function syncGenderStyle() {
    document.querySelectorAll('#gender-row label').forEach((label) => {
      label.classList.toggle('selected', label.querySelector('input').checked);
    });
  }
  $('gender-row').addEventListener('change', syncGenderStyle);

  function setGender(value) {
    const inputs = document.querySelectorAll('#gender-row input');
    let matched = false;
    inputs.forEach((input) => {
      input.checked = input.value === value;
      if (input.checked) matched = true;
    });
    if (!matched) inputs[2].checked = true;   // ค่าเก่าที่ไม่ตรง 3 ตัวเลือก -> "อื่นๆ"
    syncGenderStyle();
  }

  // ---- โหลดข้อมูลเดิมมาเติมในฟอร์ม ----
  FW.requireLogin()
    .then((user) => {
      $('username').value  = user.username  || '';
      $('firstname').value = user.firstname || '';
      $('lastname').value  = user.lastname  || '';
      $('email').value     = user.email     || '';
      $('phone').value     = user.phone     || '';
      $('address').value   = user.address   || '';
      setGender(user.gender || 'อื่นๆ');

      $('loading').hidden      = true;
      $('profile-form').hidden = false;
    })
    .catch(() => { $('loading').textContent = 'โหลดข้อมูลไม่สำเร็จ กรุณาลองใหม่'; });

  // ---- บันทึก ----
  $('profile-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    showAlert('alert-err', '');

    const btn = $('save-btn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>กำลังบันทึก...';

    const checked = document.querySelector('#gender-row input:checked');
    try {
      await FW.api('php/update_profile.php', {
        method: 'POST',
        body: {
          firstname: $('firstname').value.trim(),
          lastname:  $('lastname').value.trim(),
          email:     $('email').value.trim(),
          gender:    checked ? checked.value : 'อื่นๆ',
          phone:     $('phone').value.trim(),
          address:   $('address').value.trim(),
        },
      });
      FW.showToast('บันทึกข้อมูลเรียบร้อยแล้ว');
      setTimeout(() => { window.location.href = 'profile.html'; }, 900);
    } catch (err) {
      showAlert('alert-err', err.message || 'บันทึกไม่สำเร็จ');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } finally {
      btn.disabled  = false;
      btn.innerHTML = '<i class="fas fa-floppy-disk"></i>บันทึก';
    }
  });
})();
