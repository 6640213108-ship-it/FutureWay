// ========================================
// FutureWay - notifications.js
// หน้าการแจ้งเตือน: สวิตช์บันทึกทันทีที่กด + ขอสิทธิ์แจ้งเตือนของเบราว์เซอร์
// ========================================
(function () {
  'use strict';

  const $ = FW.$;
  const toggles = document.querySelectorAll('input[data-key]');

  function showPushAlert(message) {
    const box = $('push-alert');
    if (!message) { box.hidden = true; return; }
    box.querySelector('span').textContent = message;
    box.hidden = false;
  }

  // ---- โหลดค่าที่บันทึกไว้ ----
  FW.requireLogin()
    .then((user) => Promise.all([FW.api('php/get_settings.php'), user]))
    .then(([res, user]) => {
      toggles.forEach((t) => { t.checked = !!res.settings[t.dataset.key]; });
      if (user.email) $('email-sub').textContent = 'ส่งไปที่ ' + user.email;

      syncPushState();
      $('loading').hidden = true;
      $('panel').hidden   = false;
    })
    .catch(() => { $('loading').textContent = 'โหลดการตั้งค่าไม่สำเร็จ กรุณาลองใหม่'; });

  // ---- บันทึกทันทีที่กดสลับ (ส่งเฉพาะ key ที่เปลี่ยน) ----
  function saveSetting(key, value, onFail) {
    return FW.api('php/update_settings.php', { method: 'POST', body: { [key]: value } })
      .then(() => FW.showToast('บันทึกแล้ว'))
      .catch((err) => {
        FW.showToast(err.message || 'บันทึกไม่สำเร็จ', true);
        if (onFail) onFail();
      });
  }

  toggles.forEach((toggle) => {
    toggle.addEventListener('change', function () {
      const key    = this.dataset.key;
      const value  = this.checked;
      const revert = () => { this.checked = !value; };

      // เปิดแจ้งเตือนบนเบราว์เซอร์ ต้องได้สิทธิ์จากเบราว์เซอร์ก่อนถึงจะบันทึกว่าเปิด
      if (key === 'notify_push' && value) {
        requestPushPermission().then((granted) => {
          if (!granted) { this.checked = false; return; }
          saveSetting(key, true, revert);
        });
        return;
      }
      saveSetting(key, value, revert);
    });
  });

  // ---- สิทธิ์แจ้งเตือนของเบราว์เซอร์ (เป็นของ "เครื่องนี้" ไม่ใช่ของบัญชี) ----
  const pushSupported = () => typeof window.Notification !== 'undefined';

  function syncPushState() {
    const toggle = $('push-toggle');
    if (!pushSupported()) {
      toggle.checked  = false;
      toggle.disabled = true;
      showPushAlert('เบราว์เซอร์นี้ไม่รองรับการแจ้งเตือน (หรือเปิดผ่าน http ที่ไม่ใช่ localhost)');
      return;
    }
    if (Notification.permission === 'denied') {
      toggle.checked = false;
      showPushAlert('เบราว์เซอร์ถูกตั้งให้บล็อกการแจ้งเตือนของเว็บนี้ไว้ ต้องไปปลดล็อกที่ไอคอนซ้ายของช่อง URL ก่อน');
    } else if (toggle.checked && Notification.permission !== 'granted') {
      showPushAlert('เปิดไว้ในระบบแล้ว แต่ยังไม่ได้กดอนุญาตบนเครื่องนี้ — กดปุ่ม "ทดสอบการแจ้งเตือน" เพื่อขอสิทธิ์');
    } else {
      showPushAlert('');
    }
  }

  function requestPushPermission() {
    if (!pushSupported()) {
      showPushAlert('เบราว์เซอร์นี้ไม่รองรับการแจ้งเตือน');
      return Promise.resolve(false);
    }
    if (Notification.permission === 'granted') { showPushAlert(''); return Promise.resolve(true); }
    if (Notification.permission === 'denied') {
      showPushAlert('เบราว์เซอร์บล็อกการแจ้งเตือนของเว็บนี้ไว้ ต้องปลดล็อกที่การตั้งค่าเบราว์เซอร์ก่อน');
      return Promise.resolve(false);
    }
    return Notification.requestPermission().then((result) => {
      const granted = result === 'granted';
      showPushAlert(granted ? '' : 'ยังไม่ได้รับอนุญาตให้แจ้งเตือนบนเครื่องนี้');
      return granted;
    });
  }

  // ---- ปุ่มทดสอบ: เด้งแจ้งเตือนจริงบนเครื่องนี้ ----
  $('test-btn').addEventListener('click', () => {
    requestPushPermission().then((granted) => {
      if (!granted) { FW.showToast('ยังไม่ได้รับอนุญาตให้แจ้งเตือน', true); return; }

      new Notification('FutureWay', {
        body: 'นี่คือตัวอย่างการแจ้งเตือน — ตั้งค่าเรียบร้อยแล้ว',
        icon: 'images/futureway-logo.png',
      });

      const toggle = $('push-toggle');
      if (!toggle.checked) {
        toggle.checked = true;
        saveSetting('notify_push', true, () => { toggle.checked = false; });
      } else {
        FW.showToast('ส่งการแจ้งเตือนทดสอบแล้ว');
      }
    });
  });
})();
