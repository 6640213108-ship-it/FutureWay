// ========================================
// FutureWay - privacy.js
// หน้าความเป็นส่วนตัว: ข้อมูลที่ระบบเก็บ, สวิตช์ตั้งค่า, ลบประวัติ/ลบบัญชี (ยืนยันด้วยรหัสผ่าน)
// ========================================
(function () {
  'use strict';

  const $ = FW.$;
  let settingsLoaded = false;

  // ---- โหลดข้อมูล + การตั้งค่า ----
  FW.requireLogin()
    .then((user) => Promise.all([FW.api('php/get_settings.php'), user]))
    .then(([res, user]) => {
      document.querySelectorAll('input[data-key]').forEach((t) => {
        t.checked = !!res.settings[t.dataset.key];
      });
      settingsLoaded = true;

      $('i-name').textContent   = user.fullname || '-';
      $('i-email').textContent  = user.email    || '-';
      $('i-phone').textContent  = user.phone    || 'ยังไม่ได้กรอก';
      $('i-joined').textContent = FW.formatThaiDateTime(res.stats.joined);
      $('i-rounds').textContent = res.stats.rounds + ' รอบ';
      $('i-last').textContent   = res.stats.rounds ? FW.formatThaiDateTime(res.stats.last_quiz) : 'ยังไม่เคยทำ';

      $('loading').hidden = true;
      $('panel').hidden   = false;
    })
    .catch(() => { $('loading').textContent = 'โหลดข้อมูลไม่สำเร็จ กรุณาลองใหม่'; });

  // ---- สวิตช์: บันทึกทันทีที่กด ----
  document.querySelectorAll('input[data-key]').forEach((toggle) => {
    toggle.addEventListener('change', function () {
      if (!settingsLoaded) return;
      const value = this.checked;
      FW.api('php/update_settings.php', { method: 'POST', body: { [this.dataset.key]: value } })
        .then(() => FW.showToast('บันทึกแล้ว'))
        .catch((err) => {
          this.checked = !value;   // บันทึกไม่ผ่าน ต้องดีดสวิตช์กลับ ไม่งั้นหน้าจอโกหก
          FW.showToast(err.message || 'บันทึกไม่สำเร็จ', true);
        });
    });
  });

  // ---- กล่องยืนยันก่อนลบ ----
  let pendingScope = null;

  function setModalError(message) {
    const box = $('modal-err');
    box.querySelector('span').textContent = message;
    box.hidden = !message;
  }

  function openModal(scope) {
    pendingScope = scope;
    $('modal-pw').value = '';
    setModalError('');

    if (scope === 'history') {
      $('modal-title').textContent = 'ลบประวัติผลลัพธ์ทั้งหมด';
      $('modal-text').textContent  = 'ผลการทำแบบทดสอบทุกรอบของคุณจะถูกลบถาวร รวมถึงสาขาที่แนะนำและคำตอบรายข้อ บัญชีของคุณยังใช้งานได้ตามปกติ';
    } else {
      $('modal-title').textContent = 'ลบบัญชีผู้ใช้';
      $('modal-text').textContent  = 'บัญชี ประวัติผลลัพธ์ และการตั้งค่าทั้งหมดจะถูกลบถาวร และจะเข้าสู่ระบบด้วยบัญชีนี้ไม่ได้อีก';
    }
    $('modal').hidden = false;
    $('modal-pw').focus();
  }

  function closeModal() {
    $('modal').hidden = true;
    pendingScope = null;
  }

  $('del-history-btn').addEventListener('click', () => openModal('history'));
  $('del-account-btn').addEventListener('click', () => openModal('account'));
  $('modal-cancel').addEventListener('click', closeModal);
  $('modal').addEventListener('click', (e) => { if (e.target === $('modal')) closeModal(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !$('modal').hidden) closeModal(); });
  $('modal-pw').addEventListener('keydown', (e) => { if (e.key === 'Enter') $('modal-confirm').click(); });

  $('modal-confirm').addEventListener('click', async function () {
    const password = $('modal-pw').value;
    const scope    = pendingScope;
    if (!password) { setModalError('กรุณากรอกรหัสผ่านเพื่อยืนยัน'); return; }

    this.disabled  = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>กำลังลบ...';
    try {
      const data = await FW.api('php/delete_my_data.php', { method: 'POST', body: { scope, password } });
      closeModal();

      if (scope === 'account') {
        FW.showToast('ลบบัญชีเรียบร้อยแล้ว');
        setTimeout(() => { window.location.href = 'login.html'; }, 1200);
        return;
      }
      FW.showToast(data.message || 'ลบประวัติเรียบร้อยแล้ว');
      $('i-rounds').textContent = '0 รอบ';
      $('i-last').textContent   = 'ยังไม่เคยทำ';
    } catch (err) {
      setModalError(err.message || 'ลบไม่สำเร็จ');
    } finally {
      this.disabled  = false;
      this.innerHTML = 'ยืนยันลบ';
    }
  });
})();
