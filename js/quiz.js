// ========================================
// FutureWay - quiz.js
// แบบทดสอบ 2 ขั้น: (1) กรอกเกรด หรือเลือกความชอบ/งานอดิเรก (2) คำถาม MBTI
// คำตอบดิบถูกส่งไปให้ backend คำนวณ MBTI เอง (กันผู้ใช้แก้ค่าจาก dev console)
// ========================================
(function () {
  'use strict';

  const $   = FW.$;
  const esc = FW.escapeHtml;

  const answers     = {};   // 'q1' -> 'A' | 'B'
  let questionIds   = [];   // id จริงของคำถามเรียงตามลำดับที่แสดง
  let totalQuestions = 0;

  let inputMode = 'grade';  // 'grade' | 'interest'
  let interestQuestionsLoaded = false;
  const selectedInterests = new Set();

  // ---------- ขั้นที่ 1: ฟอร์มเกรด (render จาก GRADE_SUBJECTS) ----------
  $('grade-card').innerHTML = GRADE_SUBJECTS.map((s) => `
    <div class="grade-row">
      <div class="grade-subject">
        <div class="subject-icon ${s.key}"><i class="fas ${s.icon}"></i></div>
        <span class="subject-name">${esc(s.label)}</span>
      </div>
      <input class="grade-input" type="number" id="g-${s.key}" min="0" max="4" step="0.01" placeholder="0.00">
    </div>`).join('');

  function setInputMode(mode) {
    inputMode = mode;
    $('mode-grade-btn').classList.toggle('active', mode === 'grade');
    $('mode-interest-btn').classList.toggle('active', mode === 'interest');
    $('grade-mode-wrap').hidden    = mode !== 'grade';
    $('interest-mode-wrap').hidden = mode !== 'interest';
    $('step1-label').textContent   = mode === 'grade' ? 'กรอกเกรด' : 'ความชอบ';
    $('grade-error').classList.remove('show');
    $('interest-error').classList.remove('show');

    if (mode === 'interest' && !interestQuestionsLoaded) {
      loadInterestQuestions();
    }
  }
  $('mode-grade-btn').addEventListener('click', () => setInputMode('grade'));
  $('mode-interest-btn').addEventListener('click', () => setInputMode('interest'));

  // ---------- ความชอบ/งานอดิเรก (RIASEC) ----------
  async function loadInterestQuestions() {
    const container = $('interest-questions-container');
    try {
      const data = await FW.api('php/get_riasec_questions.php');

      const groups = {};
      data.questions.forEach((q) => { (groups[q.letter] ??= []).push(q); });

      container.innerHTML = Object.keys(groups).map((letter) => `
        <div class="dimension-label">
          <span class="dimension-badge">${esc(letter)}</span>
          <span class="dimension-title">${esc(RIASEC_DIMENSIONS[letter]?.title || letter)}</span>
        </div>
        <div class="interest-grid">
          ${groups[letter].map((q) => `
            <button type="button" class="interest-chip" data-id="${q.id}">
              <div class="chip-check"><i class="fas fa-check"></i></div>
              <span class="chip-text">${esc(q.text)}</span>
            </button>`).join('')}
        </div>`).join('');
      interestQuestionsLoaded = true;
    } catch (err) {
      container.innerHTML = '<p class="error-msg show">⚠️ ' + esc(err.message) + '</p>';
    }
  }

  // ติ๊ก/ยกเลิกความชอบ (เลือกได้หลายข้อ) — ใช้ event delegation เพราะ chip ถูกสร้างทีหลัง
  $('interest-questions-container').addEventListener('click', (e) => {
    const chip = e.target.closest('.interest-chip');
    if (!chip) return;
    const id = Number(chip.dataset.id);
    if (selectedInterests.has(id)) {
      selectedInterests.delete(id);
      chip.classList.remove('selected');
    } else {
      selectedInterests.add(id);
      chip.classList.add('selected');
    }
  });

  // ---------- ขั้นที่ 2: คำถาม MBTI ----------
  async function loadQuestions() {
    const container = $('mbti-questions-container');
    try {
      const data = await FW.api('php/get_questions.php');

      totalQuestions = data.questions.length;
      questionIds    = [];
      let lastCategory = null;
      let html = '';

      data.questions.forEach((q, index) => {
        const qNum = index + 1;
        questionIds.push(Number(q.id));

        if (q.category !== lastCategory) {
          const cat = q.category || '';
          html += `
            <div class="dimension-label">
              <span class="dimension-badge">${esc(cat.charAt(0))} / ${esc(cat.charAt(1))}</span>
              <span class="dimension-title">${esc(MBTI_DIMENSIONS[cat] || cat)}</span>
            </div>`;
          lastCategory = q.category;
        }

        html += `
          <div class="mbti-card">
            <span class="mbti-q-number">ข้อที่ ${qNum} / ${totalQuestions}</span>
            <p class="mbti-q-text">${esc(q.question_text)}</p>
            <div class="mbti-options">
              <label class="mbti-option">
                <input type="radio" name="q${qNum}" value="A">
                <div class="option-dot"></div>
                <span class="option-text">${esc(q.option_a_text)}</span>
              </label>
              <label class="mbti-option">
                <input type="radio" name="q${qNum}" value="B">
                <div class="option-dot"></div>
                <span class="option-text">${esc(q.option_b_text)}</span>
              </label>
            </div>
          </div>`;
      });

      container.innerHTML = html;
      $('mbti-error').textContent = `⚠️ กรุณาตอบทุกข้อให้ครบ ${totalQuestions} ข้อ`;
    } catch (err) {
      container.innerHTML = '<p class="error-msg show">⚠️ ' + esc(err.message) + '</p>';
    }
  }

  // เลือกตัวเลือก MBTI — ฟัง change ของ radio ผ่าน container (ไม่ต้องผูกทีละ label)
  $('mbti-questions-container').addEventListener('change', (e) => {
    const input = e.target;
    if (input.type !== 'radio') return;

    answers[input.name] = input.value;
    document.querySelectorAll(`input[name="${input.name}"]`).forEach((r) => {
      r.closest('.mbti-option').classList.remove('selected');
    });
    input.closest('.mbti-option').classList.add('selected');
    input.closest('.mbti-card').classList.remove('missing');
  });

  // ---------- ไปขั้นที่ 2 ----------
  function goToMBTI() {
    if (inputMode === 'grade') {
      let valid = true;
      GRADE_SUBJECTS.forEach((s) => {
        const el  = $(`g-${s.key}`);
        const val = parseFloat(el.value);
        const ok  = el.value !== '' && !isNaN(val) && val >= 0 && val <= 4;
        el.classList.toggle('error', !ok);
        if (!ok) valid = false;
      });
      $('grade-error').classList.toggle('show', !valid);
      if (!valid) return;
    } else {
      const ok = selectedInterests.size > 0;
      $('interest-error').classList.toggle('show', !ok);
      if (!ok) return;
    }

    $('section-grade').hidden = true;
    $('section-mbti').hidden  = false;

    $('step1').classList.replace('active', 'done');
    $('step1').querySelector('.step-circle').innerHTML = '<i class="fas fa-check step-check"></i>';
    $('line1').classList.add('done');
    $('step2').classList.add('active');
    window.scrollTo(0, 0);
  }
  $('next-btn').addEventListener('click', goToMBTI);

  // ---------- ส่งคำตอบ ----------
  const submitBtn = $('submit-btn');
  const SUBMIT_LABEL = '<i class="fas fa-paper-plane"></i> ดูผลลัพธ์';

  async function submitQuiz() {
    if (totalQuestions === 0) {
      FW.showToast('คำถามยังโหลดไม่เสร็จ กรุณารอสักครู่', true);
      return;
    }

    const cards   = document.querySelectorAll('.mbti-card');
    const missing = [];
    for (let i = 1; i <= totalQuestions; i++) {
      const card = cards[i - 1];
      if (!card) continue;
      const answered = Boolean(answers[`q${i}`]);
      card.classList.toggle('missing', !answered);
      if (!answered) missing.push(i);
    }

    const errEl = $('mbti-error');
    if (missing.length) {
      errEl.textContent = `⚠️ ยังไม่ได้ตอบข้อที่: ${missing.join(', ')}`;
      errEl.classList.add('show');
      cards[missing[0] - 1]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }
    errEl.classList.remove('show');

    const answersPayload = questionIds.map((id, i) => ({ question_id: id, selected: answers[`q${i + 1}`] }));

    const payload = inputMode === 'grade'
      ? { grades: Object.fromEntries(GRADE_SUBJECTS.map((s) => [s.key, parseFloat($(`g-${s.key}`).value)])), answers: answersPayload }
      : { interests: Array.from(selectedInterests), answers: answersPayload };

    submitBtn.disabled  = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังประมวลผล...';

    try {
      const data = await FW.api('php/save_quiz.php', { method: 'POST', body: payload });
      window.location.href = 'result.html?id=' + data.result_id;
    } catch (err) {
      FW.showToast(err.message, true);
      submitBtn.disabled  = false;
      submitBtn.innerHTML = SUBMIT_LABEL;
    }
  }
  submitBtn.addEventListener('click', submitQuiz);

  // ---------- เริ่มต้น ----------
  FW.requireLogin().then(loadQuestions);
})();
