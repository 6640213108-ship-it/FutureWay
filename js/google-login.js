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
        // ไปหน้า login.html?status=success เพื่อใช้ป็อปอัป "เข้าสู่ระบบสำเร็จ" เดิม
        window.location.href = 'login.html?status=success';
      } else {
        showError(data.error || 'เกิดข้อผิดพลาด กรุณาลองใหม่');
      }
    } catch (err) {
      showError('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาลองใหม่');
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
