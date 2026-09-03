// ========================================
// FutureWay - lightbox.js
// กดที่รูปในกล่องสไลด์ (.info-img) หรือปุ่มเมนู MBTI (.mbti-item) แล้วเปิดดูเต็มจอ
// เลื่อนดูรูปถัดไปได้ด้วยปุ่มลูกศร, คีย์บอร์ด, หรือสไวป์บนมือถือ
//
// ต้องมีมาร์กอัป #lightbox (ดู main.html) และโหลดหลังจากที่ปุ่ม/รูปถูก render แล้ว
// ========================================
(function () {
  'use strict';

  // รูปแบ่งเป็น 2 ชุด: รูปในกล่องสไลด์ กับรูป MBTI จากเมนู
  // เปิดจากชุดไหน ปุ่มก่อนหน้า/ถัดไปก็วนอยู่ในชุดนั้น ไม่ปนกัน
  const infoImgs = Array.from(document.querySelectorAll('.info-scroll .info-img'))
    .map((el) => ({ el, src: el.src, alt: el.alt || '' }));
  const mbtiItems = Array.from(document.querySelectorAll('.mbti-item'))
    .map((el) => ({
      el,
      src: el.dataset.src,
      alt: el.querySelector('.mbti-code').textContent + ' — ' + el.querySelector('.mbti-desc').textContent,
    }));

  const box      = document.getElementById('lightbox');
  const stage    = document.getElementById('lb-stage');
  const bigImg   = document.getElementById('lightbox-img');
  const btnPrev  = document.getElementById('lb-prev');
  const btnNext  = document.getElementById('lb-next');
  const btnClose = document.getElementById('lb-close');
  const btnZoom  = document.getElementById('lb-zoom');
  const counter  = document.getElementById('lb-count');

  if ((!infoImgs.length && !mbtiItems.length) || !box || !stage || !bigImg || !btnZoom) {
    return;
  }

  let list = infoImgs;      // ชุดที่กำลังเปิดดูอยู่
  let index = 0;
  let zoomed = false;
  let lastFocused = null;   // ปุ่ม/รูปที่กดเข้ามา ไว้คืน focus ตอนปิด

  // ปกติย่อรูปให้เห็นครบพอดีจอ กดขยายแล้วรูปจะเต็มความกว้างจอและเลื่อนอ่านลงมาได้
  function setZoom(on) {
    zoomed = on;
    stage.classList.toggle('zoomed', on);
    btnZoom.querySelector('i').className = on ? 'fas fa-magnifying-glass-minus' : 'fas fa-magnifying-glass-plus';
    btnZoom.setAttribute('aria-label', on ? 'ย่อรูป' : 'ขยายรูป');
    stage.scrollTop  = 0;
    stage.scrollLeft = 0;
  }

  function show(i) {
    index = (i + list.length) % list.length;   // วนกลับหัวท้าย
    const item = list[index];

    setZoom(false);
    bigImg.src = item.src;
    bigImg.alt = item.alt;
    counter.textContent = (index + 1) + ' / ' + list.length;

    const many = list.length > 1;
    btnPrev.hidden = !many;
    btnNext.hidden = !many;
    counter.hidden = !many;
  }

  // รูปที่เล็กกว่าพื้นที่อยู่แล้วไม่ต้องมีปุ่มขยาย
  bigImg.addEventListener('load', () => {
    btnZoom.hidden = bigImg.naturalWidth <= stage.clientWidth && bigImg.naturalHeight <= stage.clientHeight;
  });

  function open(itemList, i) {
    lastFocused = document.activeElement;
    list = itemList;
    show(i);
    box.hidden = false;
    document.body.classList.add('lightbox-open');
    btnClose.focus();
  }

  function close() {
    box.hidden = true;
    document.body.classList.remove('lightbox-open');
    // removeAttribute ไม่ใช่ src='' เพราะ src ว่างทำให้เบราว์เซอร์ยิงโหลด URL ของหน้านี้ซ้ำ
    bigImg.removeAttribute('src');
    if (lastFocused) lastFocused.focus();
  }

  function bindOpeners(itemList) {
    itemList.forEach((item, i) => {
      item.el.addEventListener('click', () => open(itemList, i));
      item.el.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          open(itemList, i);
        }
      });
    });
  }
  bindOpeners(infoImgs);
  bindOpeners(mbtiItems);

  btnClose.addEventListener('click', close);
  btnPrev.addEventListener('click', () => show(index - 1));
  btnNext.addEventListener('click', () => show(index + 1));
  btnZoom.addEventListener('click', () => setZoom(!zoomed));
  bigImg.addEventListener('click', () => { if (!btnZoom.hidden) setZoom(!zoomed); });

  // กดพื้นดำรอบ ๆ เพื่อปิด แต่กดที่ตัวรูปหรือปุ่มไม่ปิด
  box.addEventListener('click', (e) => {
    if (e.target === box || e.target === stage) close();
  });

  document.addEventListener('keydown', (e) => {
    if (box.hidden) return;
    if (e.key === 'Escape')     close();
    if (e.key === 'ArrowLeft')  show(index - 1);
    if (e.key === 'ArrowRight') show(index + 1);
  });

  // สไวป์ซ้าย-ขวาบนมือถือ (ระยะ 50px กันนิ้วสั่นตอนแตะ)
  let touchX = null;
  box.addEventListener('touchstart', (e) => { touchX = e.changedTouches[0].clientX; }, { passive: true });
  box.addEventListener('touchend', (e) => {
    if (touchX === null || list.length < 2 || zoomed) { touchX = null; return; }
    const diff = e.changedTouches[0].clientX - touchX;
    if (Math.abs(diff) > 50) show(diff < 0 ? index + 1 : index - 1);
    touchX = null;
  }, { passive: true });
})();
