// ========================================
// FutureWay - admin.js
// หน้าผู้ดูแลระบบ: สถิติรวม, ตารางผลการทำแบบทดสอบ (ค้นหา/กรอง/แบ่งหน้า),
// ลบผลรายรอบ, ส่งออก Excel
// ========================================
(function () {
  'use strict';

  const $   = FW.$;
  const esc = FW.escapeHtml;
  const COLS = 9;   // จำนวนคอลัมน์ในตาราง ใช้กับ colspan

  let page = 1;
  let totalPages = 1;
  let filtersLoaded = false;

  const currentFilters = () => ({
    q:      $('f-q').value.trim(),
    gender: $('f-gender').value,
    mbti:   $('f-mbti').value,
  });

  const fmtDate = (s) => FW.formatThaiDateTime(s, { suffix: false });

  const showState = (html, isError = false) => {
    $('tbody').innerHTML = `<tr><td colspan="${COLS}"><div class="state-box${isError ? ' error' : ''}">${html}</div></td></tr>`;
    $('pager').hidden = true;
  };

  /** ช่องหมวดหมู่ (คณะ) อันดับที่ n พร้อมสาขาเด่นในหมวดนั้น */
  const rankCell = (r, n) => {
    const cat = (r.top_categories || [])[n - 1];
    if (cat && cat.branches && cat.branches[0]) {
      return `<div class="branch-cell">${esc(cat.faculty) || 'ไม่ระบุคณะ'}<small>${esc(cat.branches[0].name)} · ${cat.best_score}%</small></div>`;
    }
    if (n === 1 && r.top_branch) {
      return `<div class="branch-cell">${esc(r.top_branch)}<small>${r.top_score !== null ? r.top_score + '%' : ''}</small></div>`;
    }
    return '<span class="muted">-</span>';
  };

  /** ไม่ใช่แอดมิน -> ซ่อนทั้งหน้า เหลือแค่การ์ดแจ้งเตือน */
  function showDenied(msg, needLogin = false) {
    ['stat-grid', 'filter-form', 'export-bar'].forEach((id) => { $(id).hidden = true; });
    document.querySelector('.table-card').hidden = true;
    $('who').textContent = '';

    $('denied-title').textContent = needLogin ? 'กรุณาเข้าสู่ระบบ' : 'ไม่มีสิทธิ์เข้าถึง';
    $('denied-msg').textContent   = msg;

    const btn = document.querySelector('#denied .denied-btn');
    btn.setAttribute('href', needLogin ? 'login.html' : 'main.html');
    btn.innerHTML = needLogin
      ? '<i class="fas fa-right-to-bracket"></i> ไปหน้าเข้าสู่ระบบ'
      : '<i class="fas fa-arrow-left"></i> กลับหน้าหลัก';
    $('denied').hidden = false;
  }

  async function load() {
    showState('<i class="fas fa-spinner fa-spin"></i>กำลังโหลด...');
    try {
      const data = await FW.api('php/get_admin_results.php?' + new URLSearchParams({ page, ...currentFilters() }));
      render(data);
    } catch (err) {
      if (err.status === 401)      showDenied('ต้องเข้าสู่ระบบก่อนจึงจะดูหน้านี้ได้', true);
      else if (err.status === 403) showDenied(err.message || 'หน้านี้สำหรับผู้ดูแลระบบเท่านั้น');
      else showState('<i class="fas fa-triangle-exclamation"></i>' + esc(err.message), true);
    }
  }

  function render(data) {
    renderStats(data.stats);
    if (!filtersLoaded) { renderFilters(data.filters); filtersLoaded = true; }

    totalPages = Math.max(1, data.total_pages);
    if (!data.results.length) {
      showState('<i class="fas fa-inbox"></i>ยังไม่มีผลการทำแบบทดสอบที่ตรงกับเงื่อนไข');
      return;
    }

    const tbody = $('tbody');
    tbody.innerHTML = data.results.map((r) => `
      <tr class="row-main" data-id="${r.result_id}">
        <td class="name-cell">
          ${esc(r.fullname) || '<span class="muted">ไม่ระบุชื่อ</span>'}
          <small>${esc(r.username)}${r.email ? ' · ' + esc(r.email) : ''}</small>
        </td>
        <td><span class="badge gender">${esc(r.gender) || '-'}</span></td>
        <td><span class="badge">${esc(r.mbti)}</span></td>
        <td>${rankCell(r, 1)}</td>
        <td>${rankCell(r, 2)}</td>
        <td>${rankCell(r, 3)}</td>
        <td>${r.avg_grade != null ? Number(r.avg_grade).toFixed(2) : '-'}</td>
        <td class="muted">${fmtDate(r.created_at)}</td>
        <td class="action-cell">
          <button type="button" class="del-btn" title="ลบผลลัพธ์รอบนี้"
                  data-id="${r.result_id}"
                  data-who="${esc(r.fullname) || esc(r.username)}"
                  data-when="${fmtDate(r.created_at)}">
            <i class="fas fa-trash-can"></i>
          </button>
          <i class="fas fa-chevron-down muted chev"></i>
        </td>
      </tr>
      <tr class="detail-row" id="d-${r.result_id}" hidden>
        <td colspan="${COLS}">
          <div class="detail-grid">
            <div class="detail-block">
              <h4>หมวดหมู่ (คณะ) ที่แนะนำ</h4>
              ${(r.top_categories || []).length ? r.top_categories.map((cat) => `
                <p class="cat-heading"><strong>${esc(cat.faculty) || 'ไม่ระบุคณะ'}</strong> (${cat.best_score}%)</p>
                ${cat.branches.map((b, bi) => `
                  <div class="rank-line">
                    <span class="rank-no n${bi + 1}">${bi + 1}</span>
                    <span class="grow">${esc(b.name)}</span>
                    <span class="score-pill">${b.score}%</span>
                  </div>`).join('')}`).join('')
              : '<p class="muted note-old">ผลลัพธ์รอบนี้บันทึกไว้ก่อนระบบเก็บเป็นหมวดหมู่</p>'}
            </div>
            <div class="detail-block">
              <h4>${r.grades ? 'เกรดที่กรอก' : 'โปรไฟล์ความสนใจ (RIASEC)'}</h4>
              <div class="grade-chips">
                ${r.grades
                  ? GRADE_SUBJECTS.map((s) => `<span>${esc(s.short)}: ${Number(r.grades[s.key]).toFixed(2)}</span>`).join('')
                  : r.riasec_scores
                    ? Object.entries(r.riasec_scores).map(([letter, val]) => `<span>${esc(letter)}: ${Math.round(val * 100)}%</span>`).join('')
                    : '<span class="muted">ไม่มีข้อมูล</span>'}
              </div>
            </div>
          </div>
        </td>
      </tr>`).join('');

    tbody.querySelectorAll('tr.row-main').forEach((tr) => {
      tr.addEventListener('click', () => {
        const d    = $('d-' + tr.dataset.id);
        const icon = tr.querySelector('.chev');
        d.hidden = !d.hidden;
        icon.className = d.hidden ? 'fas fa-chevron-down muted chev' : 'fas fa-chevron-up muted chev';
      });
    });

    // ปุ่มลบอยู่ในแถวที่คลิกแล้วกางรายละเอียด ต้อง stopPropagation
    tbody.querySelectorAll('.del-btn').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        deleteResult(btn, data.results.length);
      });
    });

    $('pager').hidden = false;
    $('pager-info').textContent = `หน้า ${data.page} / ${totalPages} · ทั้งหมด ${data.total} รายการ`;
    $('prev').disabled = data.page <= 1;
    $('next').disabled = data.page >= totalPages;
  }

  async function deleteResult(btn, rowsOnPage) {
    const { id, who, when } = btn.dataset;
    if (!confirm(`ลบผลการทำแบบทดสอบของ "${who}"\nเมื่อ ${when} ใช่หรือไม่?\n\nข้อมูลคำตอบและสาขาที่แนะนำของรอบนี้จะถูกลบไปด้วย และกู้คืนไม่ได้`)) {
      return;
    }

    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    try {
      await FW.api('php/delete_result.php', { method: 'POST', body: { result_id: Number(id) } });
      // ลบรายการสุดท้ายของหน้า -> ถอยไปหน้าก่อนหน้า จะได้ไม่ค้างอยู่หน้าว่าง
      if (rowsOnPage === 1 && page > 1) page--;
      load();
    } catch (err) {
      alert('ลบไม่สำเร็จ: ' + err.message);
      btn.disabled  = false;
      btn.innerHTML = '<i class="fas fa-trash-can"></i>';
    }
  }

  function renderStats(s) {
    const genderText = s.by_gender.length
      ? s.by_gender.map((g) => `${esc(g.gender) || 'ไม่ระบุ'} ${g.count}`).join(' · ')
      : '-';
    const topMbti   = s.top_mbti[0];
    const topBranch = s.top_branch[0];

    $('stat-grid').innerHTML = `
      <div class="stat-tile">
        <div class="label">จำนวนครั้งที่ทำแบบทดสอบ</div>
        <div class="value">${s.total_attempts}</div>
        <div class="sub">${genderText}</div>
      </div>
      <div class="stat-tile">
        <div class="label">ผู้ใช้ที่เคยทำแบบทดสอบ</div>
        <div class="value">${s.total_users}</div>
        <div class="sub">คน</div>
      </div>
      <div class="stat-tile">
        <div class="label">MBTI ที่พบบ่อยที่สุด</div>
        <div class="value">${topMbti ? esc(topMbti.mbti) : '-'}</div>
        <div class="sub">${topMbti ? topMbti.count + ' ครั้ง' : 'ยังไม่มีข้อมูล'}</div>
      </div>
      <div class="stat-tile">
        <div class="label">สาขาที่ถูกแนะนำบ่อยที่สุด</div>
        <div class="value value-text">${topBranch ? esc(topBranch.name) : '-'}</div>
        <div class="sub">${topBranch ? topBranch.count + ' ครั้ง' : 'ยังไม่มีข้อมูล'}</div>
      </div>`;
  }

  function renderFilters(f) {
    f.genders.forEach((g) => $('f-gender').insertAdjacentHTML('beforeend', `<option value="${esc(g)}">${esc(g)}</option>`));
    f.mbti.forEach((m) => $('f-mbti').insertAdjacentHTML('beforeend', `<option value="${esc(m)}">${esc(m)}</option>`));
  }

  $('filter-form').addEventListener('submit', (e) => { e.preventDefault(); page = 1; load(); });
  $('f-gender').addEventListener('change', () => { page = 1; load(); });
  $('f-mbti').addEventListener('change',   () => { page = 1; load(); });
  $('prev').addEventListener('click', () => { if (page > 1)          { page--; load(); window.scrollTo(0, 0); } });
  $('next').addEventListener('click', () => { if (page < totalPages) { page++; load(); window.scrollTo(0, 0); } });

  // ---------- ส่งออก Excel ----------
  // ปุ่ม "เฉพาะที่กรองอยู่" ไม่มีประโยชน์ถ้ายังไม่ได้กรองอะไร -> ซ่อนไว้
  function syncExportButtons() {
    const f = currentFilters();
    $('export-filtered').hidden = !(f.q || f.gender || f.mbti);
  }

  /**
   * ใช้ fetch แล้วแปลงเป็น blob แทน window.location เพราะถ้า session หมดอายุ
   * ฝั่ง PHP จะตอบ JSON กลับมา ต้องอ่านข้อความบอกผู้ใช้ได้ ไม่ใช่เปิดหน้า JSON ดิบ
   */
  async function exportExcel(scope, btn) {
    const params = new URLSearchParams({ scope });
    if (scope === 'filtered') {
      Object.entries(currentFilters()).forEach(([k, v]) => { if (v) params.set(k, v); });
    }

    const original = btn.innerHTML;
    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังสร้างไฟล์...';

    try {
      const r    = await fetch('php/export_admin_results.php?' + params, { credentials: 'same-origin', cache: 'no-store' });
      const type = r.headers.get('Content-Type') || '';
      if (type.includes('application/json')) {
        const data = await r.json().catch(() => null);
        throw new Error(data?.error || 'ส่งออกไม่สำเร็จ');
      }
      if (!r.ok) throw new Error('ส่งออกไม่สำเร็จ (HTTP ' + r.status + ')');

      const blob  = await r.blob();
      const now   = new Date();
      const pad   = (n) => String(n).padStart(2, '0');
      const stamp = now.getFullYear() + pad(now.getMonth() + 1) + pad(now.getDate()) + '-' + pad(now.getHours()) + pad(now.getMinutes());

      const url = URL.createObjectURL(blob);
      const a   = document.createElement('a');
      a.href     = url;
      a.download = 'FutureWay-ผลแบบทดสอบ-' + stamp + '.xlsx';
      document.body.appendChild(a);
      a.click();
      a.remove();
      setTimeout(() => URL.revokeObjectURL(url), 1000);
    } catch (err) {
      alert(err.message || 'ส่งออกไม่สำเร็จ');
    } finally {
      btn.disabled  = false;
      btn.innerHTML = original;
    }
  }

  $('export-all').addEventListener('click', function () { exportExcel('all', this); });
  $('export-filtered').addEventListener('click', function () { exportExcel('filtered', this); });
  ['f-q', 'f-gender', 'f-mbti'].forEach((id) => {
    $(id).addEventListener('input',  syncExportButtons);
    $(id).addEventListener('change', syncExportButtons);
  });

  // ชื่อผู้ใช้บน header (ไม่บังคับ — ถ้าไม่ได้ล็อกอิน load() จะแสดงการ์ดแจ้งเตือนเอง)
  FW.loadCurrentUser()
    .then((u) => { $('who').textContent = u.fullname; })
    .catch(() => {});

  load();
})();
