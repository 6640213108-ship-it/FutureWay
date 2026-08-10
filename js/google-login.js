// ========================================
// FutureWay - google-login.js
// ทำให้ปุ่ม "Google" (class .btn-social.google) เข้าสู่ระบบได้จริง
// ใช้ Google Identity Services (popup ขอ access token)
//
// หน้าที่ใช้ต้องมี 2 อย่าง:
//   1. <script src="https://accounts.google.com/gsi/client" async defer></script>
//   2. <script src="js/google-login.js"></script>
// ========================================

(function () {
  let tokenClient = null;

  // แจ้ง error: ใช้ SweetAlert ถ้าหน้านั้นโหลดไว้ ไม่งั้น alert ธรรมดา
  function showError(message) {
    if (window.Swal) {
      Swal.fire({ title: 'เข้าสู่ระบบด้วย Google ไม่สำเร็จ', text: message, icon: 'error', confirmButtonColor: '#d63031' });
    } else {
      alert('เข้าสู่ระบบด้วย Google ไม่สำเร็จ: ' + message);
    }
  }

  // ได้ access token จาก popup แล้ว → ส่งให้เซิร์ฟเวอร์ตรวจสอบและล็อกอิน
  async function onToken(response) {
    if (!response || !response.access_token) {
      showError('ไม่ได้รับสิทธิ์จาก Google');
      return;
    }
    try {
      const res = await fetch('php/google_login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ access_token: response.access_token }),
      });
      const data = await res.json();
      if (data.success) {
        // บัญชีที่ยังไม่เคยเลือกเพศ (เช่น เพิ่งสมัครผ่าน Google) → ถามก่อนเข้าระบบ
        if (data.need_gender && window.Swal) {
          await askGender();
        }
        // ไปหน้า login.html?status=success เพื่อใช้ป็อปอัป "เข้าสู่ระบบสำเร็จ" เดิม
        window.location.href = 'login.html?status=success';
      } else {
        showError(data.error || 'เกิดข้อผิดพลาด กรุณาลองใหม่');
      }
    } catch (err) {
      showError('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาลองใหม่');
    }
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
      confirmButtonColor: '#7b2ff7',
      showCancelButton: true,
      cancelButtonText: 'ข้ามไปก่อน',
      allowOutsideClick: false,
    });

    if (!result.isConfirmed || !result.value) return;

    try {
      await fetch('php/set_gender.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ gender: result.value }),
      });
    } catch (err) {
      /* บันทึกไม่ได้ก็ไม่ขวางการเข้าระบบ — ไปแก้ทีหลังได้ที่หน้าโปรไฟล์ */
    }
  }

  // เตรียม token client ล่วงหน้าตอนโหลดหน้า เพื่อให้ตอนคลิกเปิด popup ได้ทันที
  // (ถ้าไปรอ fetch ตอนคลิก เบราว์เซอร์บางตัวจะบล็อก popup)
  async function init() {
    const btn = document.querySelector('.btn-social.google');
    if (!btn) return;

    let clientId = '';
    try {
      const res = await fetch('php/get_google_client_id.php');
      clientId = (await res.json()).client_id || '';
    } catch (err) {
      /* ปล่อยให้ไปแจ้งตอนคลิกแทน */
    }

    btn.addEventListener('click', function (e) {
      e.preventDefault();
      if (!clientId || clientId.startsWith('YOUR_GOOGLE_CLIENT_ID')) {
        showError('ยังไม่ได้ตั้งค่า Google Client ID (ดูวิธีใน php/google_config.php)');
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
