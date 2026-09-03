// ========================================
// FutureWay - change-password.js
// หน้าเปลี่ยนรหัสผ่าน: มิเตอร์ความแข็งแรง + ตรวจตรงกัน -> ส่ง JSON ไป php/change_password.php
// ========================================
(function () {
  'use strict';

  const $ = FW.$;

  function showAlert(id, message) {
    const box = $(id);
    box.querySelector('span').textContent = message || '';
    box.hidden = !message;
  }

  // ปุ่มลูกตา: สลับซ่อน/แสดงรหัสผ่าน
  document.querySelectorAll('.pw-toggle').forEach((btn) => {
    btn.addEventListener('click', () => {
      FW.togglePasswordVisibility($(btn.dataset.target), btn.querySelector('i'));
    });
  });

  // ให้คะแนนจากความยาว + ความหลากหลายของตัวอักษร (ตัวช่วยเตือน ฝั่ง server บังคับแค่ 6 ตัวขึ้นไป)
  function scorePassword(pw) {
    if (!pw) return 0;
    let score = 0;
    if (pw.length >= 6)  score++;
    if (pw.length >= 10) score++;
    if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
    if (/\d/.test(pw))   score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    return Math.min(score, 4);
  }

  function updateMeter() {
    const pw    = $('newpw').value;
    const score = scorePassword(pw);
    const meter = $('meter');

    meter.querySelector('span').style.width = (score / 4 * 100) + '%';
    meter.classList.remove('mid', 'good');
    if (score >= 4)      meter.classList.add('good');
    else if (score >= 2) meter.classList.add('mid');

    const labels = ['อย่างน้อย 6 ตัวอักษร', 'อ่อนมาก', 'พอใช้', 'ดี', 'แข็งแรงมาก'];
    $('meter-label').textContent = pw ? labels[score] : labels[0];
  }

  function updateMatch() {
    const pw = $('newpw').value;
    const cf = $('confirm').value;
    const hint = $('match-hint');
    hint.classList.remove('ok', 'bad');
    if (!cf) { hint.textContent = ''; return; }
    const same = pw === cf;
    hint.textContent = same ? 'รหัสผ่านตรงกัน' : 'รหัสผ่านยังไม่ตรงกัน';
    hint.classList.add(same ? 'ok' : 'bad');
  }

  $('newpw').addEventListener('input', () => { updateMeter(); updateMatch(); });
  $('confirm').addEventListener('input', updateMatch);

  $('pw-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    showAlert('alert-err', '');
    showAlert('alert-ok', '');

    const current = $('current').value;
    const newpw   = $('newpw').value;
    const confirm = $('confirm').value;

    if (newpw.length < 6)  { showAlert('alert-err', 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร'); return; }
    if (newpw !== confirm) { showAlert('alert-err', 'รหัสผ่านใหม่กับการยืนยันไม่ตรงกัน'); return; }
    if (newpw === current) { showAlert('alert-err', 'รหัสผ่านใหม่ต้องไม่ซ้ำกับรหัสผ่านเดิม'); return; }

    const btn = $('save-btn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>กำลังเปลี่ยน...';

    try {
      await FW.api('php/change_password.php', {
        method: 'POST',
        body: { current_password: current, new_password: newpw, confirm_password: confirm },
      });
      $('pw-form').reset();
      updateMeter();
      updateMatch();
      showAlert('alert-ok', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว ครั้งต่อไปให้ใช้รหัสผ่านใหม่เข้าสู่ระบบ');
      FW.showToast('เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
      setTimeout(() => { window.location.href = 'profile.html'; }, 1600);
    } catch (err) {
      showAlert('alert-err', err.message || 'เปลี่ยนรหัสผ่านไม่สำเร็จ');
    } finally {
      btn.disabled  = false;
      btn.innerHTML = '<i class="fas fa-key"></i>เปลี่ยนรหัสผ่าน';
    }
  });

  FW.requireLogin();
  updateMeter();
})();
