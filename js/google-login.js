// ========================================
// FutureWay - google-login.js
// ทำให้ปุ่ม "Google" (class .btn-social.google) เข้าสู่ระบบได้จริง
// ใช้ Google Identity Services (popup ขอ access token)
//
// หน้าที่ใช้ต้องโหลด: SweetAlert2, https://accounts.google.com/gsi/client,
// js/common.js แล้วตามด้วยไฟล์นี้
// ========================================
(function () {
  'use strict';

  let tokenClient = null;

  function showError(message) {
    Swal.fire({
      title: 'เข้าสู่ระบบด้วย Google ไม่สำเร็จ',
      text: message,
      icon: 'error',
      confirmButtonColor: FW.themeColor('error'),
    });
  }

  // ป็อปอัปให้เลือกเพศหลังล็อกอิน Google ครั้งแรก
  // กด "ข้ามไปก่อน" ได้ (ค่าจะเป็น "ไม่ระบุ" และไปแก้ทีหลังได้ที่หน้าแก้ไขโปรไฟล์)
  async function askGender() {
    const result = await Swal.fire({
      title: 'อีกนิดเดียว!',
      text: 'กรุณาเลือกเพศของคุณ',
      icon: 'question',
      input: 'radio',
      inputOptions: { 'ชาย': 'ชาย', 'หญิง': 'หญิง', 'อื่นๆ': 'อื่นๆ' },
      inputValidator: (v) => (!v ? 'กรุณาเลือกเพศ หรือกด "ข้ามไปก่อน"' : undefined),
      confirmButtonText: 'บันทึก',
      confirmButtonColor: FW.themeColor('primary'),
      showCancelButton: true,
      cancelButtonText: 'ข้ามไปก่อน',
      allowOutsideClick: false,
    });

    if (!result.isConfirmed || !result.value) return;
    try {
      await FW.api('php/set_gender.php', { method: 'POST', body: { gender: result.value } });
    } catch (err) {
      /* บันทึกไม่ได้ก็ไม่ขวางการเข้าระบบ — ไปแก้ทีหลังได้ที่หน้าโปรไฟล์ */
    }
  }

  // ได้ access token จาก popup แล้ว -> ส่งให้เซิร์ฟเวอร์ตรวจสอบและล็อกอิน
  async function onToken(response) {
    if (!response || !response.access_token) {
      showError('ไม่ได้รับสิทธิ์จาก Google');
      return;
    }
    try {
      const data = await FW.api('php/google_login.php', {
        method: 'POST',
        body: { access_token: response.access_token },
      });
      if (data.need_gender) {
        await askGender();
      }
      await Swal.fire({
        title: 'เข้าสู่ระบบสำเร็จ!',
        text: 'กำลังเข้าสู่หน้าหลัก...',
        icon: 'success',
        timer: 1200,
        showConfirmButton: false,
      });
      window.location.href = 'main.html';
    } catch (err) {
      showError(err.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่');
    }
  }

  // เตรียม Client ID ล่วงหน้าตอนโหลดหน้า เพื่อให้ตอนคลิกเปิด popup ได้ทันที
  // (ถ้าไปรอ fetch ตอนคลิก เบราว์เซอร์บางตัวจะบล็อก popup)
  async function init() {
    const btn = document.querySelector('.btn-social.google');
    if (!btn) return;

    let clientId = '';
    try {
      clientId = (await FW.api('php/get_google_client_id.php')).client_id || '';
    } catch (err) {
      /* ปล่อยให้ไปแจ้งตอนคลิกแทน */
    }

    btn.addEventListener('click', (e) => {
      e.preventDefault();
      if (!clientId || clientId.startsWith('YOUR_GOOGLE_CLIENT_ID')) {
        showError('ระบบยังไม่ได้ตั้งค่า Google Client ID');
        return;
      }
      if (!window.google || !google.accounts || !google.accounts.oauth2) {
        showError('โหลดสคริปต์ของ Google ไม่สำเร็จ กรุณารีเฟรชหน้า');
        return;
      }
      if (!tokenClient) {
        tokenClient = google.accounts.oauth2.initTokenClient({
          client_id: clientId,
          scope: 'openid email profile',
          callback: onToken,
        });
      }
      tokenClient.requestAccessToken();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
