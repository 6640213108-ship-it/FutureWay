// ========================================
// FutureWay - history.js
// ประวัติผลลัพธ์: แสดง 3 รอบล่าสุด (ตัดตั้งแต่ฝั่ง DB ผ่าน ?limit=)
// ========================================
(function () {
  'use strict';

  const ROUNDS = 3;
  const esc    = FW.escapeHtml;
  const listEl = FW.$('history-list');

  const setState = (html, cls = '') => {
    listEl.innerHTML = `<div class="history-state ${cls}">${html}</div>`;
  };

  function render(data) {
    const total = data.total_rounds;
    FW.$('count-text').textContent = total > data.history.length
      ? `แสดง ${data.history.length} รอบล่าสุด จากทั้งหมด ${total} รอบ`
      : `ทำมาแล้ว ${total} รอบ`;

    listEl.innerHTML = data.history.map((h, i) => {
      // ผลรอบเก่าที่บันทึกก่อนระบบเก็บเป็นหมวดหมู่ ยังพอแสดงอันดับ 1 จาก branch_name ได้
      const categories = h.top_categories.length
        ? h.top_categories
        : (h.branch_name
          ? [{ faculty: h.faculty, best_score: h.score ?? 0, branches: [{ name: h.branch_name, score: h.score ?? 0 }] }]
          : []);

      const bestBranchName = categories.length ? categories[0].branches[0].name : null;
      const roundNo = total - i;
      const typeInfo = MBTI_TYPES[h.mbti_type];

      return `
        <div class="round-card">
          <div class="round-head">
            <span class="round-label">รอบที่ ${roundNo}</span>
            ${i === 0 ? '<span class="latest-tag">ล่าสุด</span>' : ''}
            <span class="round-date">${FW.formatThaiDateTime(h.created_at, { fallback: '' })}</span>
          </div>
          <div class="round-body">
            <div class="round-summary">
              <div class="mbti-box">
                <div class="code">${esc(h.mbti_type)}</div>
                <div class="cap">${esc(typeInfo ? typeInfo.title : 'บุคลิกภาพ')}</div>
              </div>
              <div class="summary-text">
                ${h.input_mode === 'interest'
                  ? 'วิเคราะห์จาก <strong>ความสนใจ/งานอดิเรก</strong> (ไม่ได้กรอกเกรด)'
                  : `เกรดเฉลี่ยที่กรอก <strong>${Number(h.avg_grade).toFixed(2)}</strong>`}<br>
                ${bestBranchName
                  ? `สาขาที่เหมาะที่สุดคือ <strong>${esc(bestBranchName)}</strong>`
                  : '<span class="no-data">ไม่มีข้อมูลสาขาที่แนะนำ</span>'}
              </div>
            </div>

            ${categories.length ? `
              <h4>หมวดหมู่ที่แนะนำ${h.top_categories.length ? ` ${categories.length} หมวด` : ''}</h4>
              ${categories.map((cat) => `
                <div class="category-block">
                  <p class="cat-faculty">${esc(cat.faculty) || 'ไม่ระบุคณะ'}</p>
                  <p class="cat-branches">${cat.branches.map((b) => esc(b.name)).join(', ')}</p>
                </div>`).join('')}
              ${!h.top_categories.length ? '<p class="rfac note-old">ผลรอบนี้บันทึกไว้ก่อนระบบเก็บเป็นหมวดหมู่</p>' : ''}
            ` : ''}

            <a href="result.html?id=${h.id}" class="full-link">
              ดูผลลัพธ์เต็มของรอบนี้ <i class="fas fa-chevron-right arrow"></i>
            </a>
          </div>
        </div>`;
    }).join('');
  }

  FW.requireLogin()
    .then(() => FW.api(`php/get_history.php?limit=${ROUNDS}`))
    .then((data) => {
      if (!data.history.length) {
        setState('<i class="fas fa-clipboard-list"></i>ยังไม่เคยทำแบบทดสอบ'
               + '<br><a href="quiz.html" class="go-quiz">เริ่มทำแบบทดสอบ</a>');
        FW.$('count-text').textContent = '';
        return;
      }
      render(data);
    })
    .catch((err) => setState('<i class="fas fa-triangle-exclamation"></i>' + esc(err.message), 'error'));
})();
