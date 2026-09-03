// ========================================
// FutureWay - result.js
// หน้าผลลัพธ์: การ์ดบุคลิกภาพ + โปสเตอร์สาขาที่เหมาะสม + สรุปเกรด/RIASEC
// ========================================
(function () {
  'use strict';

  const $   = FW.$;
  const esc = FW.escapeHtml;

  function showError(msg) {
    $('loading-wrap').hidden   = true;
    $('error-wrap').hidden     = false;
    $('error-msg').textContent = msg;
  }

  function renderResult(data) {
    const mbti = String(data.mbti || '').trim().toUpperCase();
    const info = MBTI_TYPES[mbti];
    if (!info) {
      showError('ผลบุคลิกภาพไม่ถูกต้อง (' + (mbti || 'ไม่มีค่า') + ') กรุณาทำแบบทดสอบใหม่');
      return;
    }

    $('mbti-display').textContent  = mbti;
    $('mbti-title').textContent    = info.title;
    $('mbti-subtitle').textContent = info.subtitle;

    // โปสเตอร์ MBTI (images/XXXX.jpg) — ซ่อนถ้าโหลดไม่ได้
    const poster = $('mbti-branch-image');
    poster.hidden  = false;
    poster.onerror = () => { poster.hidden = true; };
    poster.src = `images/${mbti}.jpg`;
    poster.alt = `${mbti} - ${info.title} สาขาที่เหมาะสม`;

    $('mbti-traits-list').innerHTML    = info.traits.map((t) => `<li>${esc(t)}</li>`).join('');
    $('mbti-strengths-list').innerHTML = info.strengths.map((t) => `<li>${esc(t)}</li>`).join('');

    // สรุปเกรด (โหมดกรอกเกรด) หรือโปรไฟล์ RIASEC (โหมดไม่ทราบเกรด)
    const isInterestMode = data.input_mode === 'interest';
    $('avg-grade-display').textContent = isInterestMode
      ? 'วิเคราะห์จากความสนใจ/งานอดิเรก (RIASEC) แทนเกรด'
      : `เกรดเฉลี่ย ${data.avg_grade} | วิเคราะห์จาก ${GRADE_SUBJECTS.length} วิชาหลัก`;

    let chips = [];
    if (isInterestMode && data.riasec_scores) {
      $('summary-title').textContent = '💡 โปรไฟล์ความสนใจของคุณ (RIASEC)';
      chips = Object.entries(data.riasec_scores).map(([letter, val]) =>
        `<div class="grade-chip">${esc(RIASEC_DIMENSIONS[letter]?.name || letter)}: <span>${Math.round(val * 100)}%</span></div>`);
    } else if (data.grades) {
      chips = GRADE_SUBJECTS
        .filter((s) => data.grades[s.key] != null)
        .map((s) => `<div class="grade-chip">${esc(s.short)}: <span>${parseFloat(data.grades[s.key]).toFixed(2)}</span></div>`);
    }
    $('grade-summary').innerHTML = chips.join('');

    $('loading-wrap').hidden = true;
    $('result-wrap').hidden  = false;
  }

  const resultId = new URLSearchParams(window.location.search).get('id');
  if (!resultId) {
    showError('ไม่พบ result_id กรุณาทำแบบทดสอบใหม่');
    return;
  }

  FW.requireLogin()
    .then(() => FW.api(`php/get_result.php?id=${encodeURIComponent(resultId)}`))
    .then(renderResult)
    .catch((err) => showError(err.message));
})();
