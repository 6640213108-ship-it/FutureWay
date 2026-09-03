// ========================================
// FutureWay - common.js
// ของกลางที่ทุกหน้าใช้ร่วมกัน (โหลดก่อนสคริปต์ของแต่ละหน้าเสมอ)
//
//   FW.$              หา element จาก id
//   FW.escapeHtml     กัน HTML injection ก่อนใส่ข้อความลง innerHTML
//   FW.formatThaiDateTime  'YYYY-MM-DD HH:MM:SS' -> '15 ก.ค. 2569 13:38 น.'
//   FW.api            fetch JSON จาก php/ แล้วโยน Error (มี .status) ถ้าไม่สำเร็จ
//   FW.loadCurrentUser / FW.requireLogin   ข้อมูลผู้ใช้ที่ล็อกอินอยู่
//   FW.logout         ออกจากระบบแล้วไปหน้า login
//   FW.showToast      แถบแจ้งเตือนเล็ก ๆ ด้านล่างจอ
//   FW.themeColor     อ่านสีจาก CSS token (ใช้กับ SweetAlert)
//   FW.togglePasswordVisibility
//
// มาร์กอัปที่ซ้ำทุกหน้าถูก render จากที่นี่ (ไม่ต้องก๊อปวางในแต่ละหน้า):
//   <header class="back-header" data-title="..." data-back="main.html"></header>
//   <nav class="bottom-nav" data-active="home|history|settings"></nav>
// ========================================

const FW = (() => {
  'use strict';

  const $ = (id) => document.getElementById(id);

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  })[c]);

  // แปลงเองแทน new Date() เพราะ Safari บนมือถือ parse รูปแบบที่มีเว้นวรรคไม่ได้
  const TH_MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
                     'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

  function formatThaiDateTime(raw, { suffix = true, fallback = '-' } = {}) {
    if (!raw) return fallback;
    const m = String(raw).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
    if (!m) return escapeHtml(raw);
    const [, y, mo, d, hh, mm] = m;
    return `${+d} ${TH_MONTHS[+mo - 1]} ${+y + 543} ${hh}:${mm}${suffix ? ' น.' : ''}`;
  }

  /**
   * เรียก API ฝั่ง PHP แล้วคืน JSON ที่ decode แล้ว
   * ถ้า HTTP ไม่ ok หรือ success === false จะโยน Error ที่มี .status และ .data
   */
  async function api(url, { method = 'GET', body } = {}) {
    const options = { method, credentials: 'same-origin', cache: 'no-store' };
    if (body !== undefined) {
      options.headers = { 'Content-Type': 'application/json' };
      options.body    = JSON.stringify(body);
    }

    let response;
    try {
      response = await fetch(url, options);
    } catch (networkError) {
      const err = new Error('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาลองใหม่');
      err.status = 0;
      throw err;
    }

    const data = await response.json().catch(() => null);
    if (!response.ok || !data || data.success === false) {
      const err = new Error((data && data.error) || `เกิดข้อผิดพลาด (HTTP ${response.status})`);
      err.status = response.status;
      err.data   = data;
      throw err;
    }
    return data;
  }

  const loadCurrentUser = () => api('php/get_user.php');

  /**
   * ใช้ในหน้าที่ต้องล็อกอิน: ถ้ายังไม่ล็อกอิน (401) จะพาไปหน้า login ทันที
   * และคืน Promise ที่ไม่ resolve เพื่อไม่ให้โค้ดที่เหลือของหน้ารันต่อ
   */
  async function requireLogin() {
    try {
      return await loadCurrentUser();
    } catch (err) {
      if (err.status === 401) {
        window.location.replace('login.html');
        return new Promise(() => {});
      }
      throw err;
    }
  }

  async function logout() {
    try {
      await api('php/logout.php', { method: 'POST' });
    } finally {
      window.location.href = 'login.html';
    }
  }

  // ---- Toast ----
  let toastEl = null;
  let toastTimer = null;
  function showToast(message, isError = false) {
    if (!toastEl) {
      toastEl = document.createElement('div');
      toastEl.className = 'toast';
      toastEl.innerHTML = '<i></i><span></span>';
      document.body.appendChild(toastEl);
    }
    toastEl.querySelector('span').textContent = message;
    toastEl.querySelector('i').className = isError ? 'fas fa-circle-exclamation' : 'fas fa-circle-check';
    toastEl.classList.toggle('err', isError);
    toastEl.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toastEl.classList.remove('show'), 2600);
  }

  // สีจาก tokens.css สำหรับ library ที่รับค่าเป็น string เช่น SweetAlert
  const themeColor = (name) =>
    getComputedStyle(document.documentElement).getPropertyValue(`--${name}`).trim();

  function togglePasswordVisibility(input, icon) {
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    if (icon) {
      icon.classList.toggle('fa-eye', !show);
      icon.classList.toggle('fa-eye-slash', show);
    }
  }

  // ---- มาร์กอัปที่ใช้ซ้ำทุกหน้า ----
  const NAV_ITEMS = [
    { key: 'home',     href: 'main.html',    icon: 'fa-home',      label: 'หน้าหลัก' },
    { key: 'history',  href: 'history.html', icon: 'fa-chart-bar', label: 'ประวัติผลลัพธ์' },
    { key: 'settings', href: 'profile.html', icon: 'fa-user-cog',  label: 'การตั้งค่า' },
  ];

  function renderBottomNav() {
    const nav = document.querySelector('nav.bottom-nav[data-active]');
    if (!nav) return;
    const active = nav.dataset.active;
    nav.innerHTML = NAV_ITEMS.map((item) => {
      const isActive = item.key === active;
      const icon     = `<i class="fas ${item.icon}"></i>`;
      return `
        <a href="${item.href}" class="nav-item${isActive ? ' active' : ''}"${isActive ? ' aria-current="page"' : ''}>
          ${isActive ? `<div class="icon-circle">${icon}</div>` : icon}
          <span>${item.label}</span>
        </a>`;
    }).join('');
  }

  function renderBackHeader() {
    const header = document.querySelector('header.back-header[data-title]');
    if (!header) return;
    const back = header.dataset.back || 'main.html';
    header.innerHTML = `
      <a href="${escapeHtml(back)}" class="back-btn" aria-label="ย้อนกลับ"><i class="fas fa-arrow-left"></i></a>
      <span class="page-title">${escapeHtml(header.dataset.title)}</span>`;
  }

  function init() {
    renderBackHeader();
    renderBottomNav();
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  return {
    $, escapeHtml, formatThaiDateTime, api,
    loadCurrentUser, requireLogin, logout,
    showToast, themeColor, togglePasswordVisibility,
  };
})();
