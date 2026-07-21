<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$conn = new mysqli('mysql.railway.internal', 'root', 'OLdaGruletpcPRSKSZkUOUrKaUWmDjri', 'railway', 3306);
if ($conn->connect_error) {
    die('DB connection failed');
}
$conn->set_charset('utf8mb4');

$result = $conn->query("SELECT * FROM mbti_questions ORDER BY category, question_no ASC");
$questions = [];
while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>แบบทดสอบ: คณะที่ใช่ตามสไตล์ MBTI</title>
<style>
    body { font-family: "Sarabun","Tahoma",sans-serif; background:#f4f6f9; margin:0; padding:20px; color:#2d2d2d; }
    .container { max-width:700px; margin:0 auto; background:#fff; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.08); padding:30px; }
    h1 { text-align:center; font-size:22px; margin-bottom:5px; color:#3b3b98; }
    p.subtitle { text-align:center; color:#777; margin-bottom:25px; }
    .progress-bar { background:#e0e0e0; border-radius:20px; height:10px; margin-bottom:30px; overflow:hidden; }
    .progress-fill { background:#3b3b98; height:100%; width:0%; transition:width .3s ease; }
    .step { display:none; }
    .step.active { display:block; animation:fadeIn .3s ease; }
    @keyframes fadeIn { from{opacity:0;transform:translateY(8px);} to{opacity:1;transform:translateY(0);} }
    .question-number { font-size:13px; color:#999; margin-bottom:8px; }
    .question-text { font-size:18px; font-weight:600; margin-bottom:20px; line-height:1.5; }
    .option { display:block; width:100%; text-align:left; background:#f8f9fc; border:2px solid #e2e4ec; border-radius:10px; padding:15px 18px; margin-bottom:12px; font-size:15px; cursor:pointer; line-height:1.5; }
    .option:hover { border-color:#b3b8f5; background:#f0f1ff; }
    .option.selected { border-color:#3b3b98; background:#eceeff; font-weight:600; }
    .grade-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
    .grade-row label { font-size:15px; }
    .grade-row input { width:90px; padding:8px; border:2px solid #e2e4ec; border-radius:8px; font-size:15px; text-align:center; }
    .nav-buttons { display:flex; justify-content:space-between; margin-top:25px; }
    button.nav-btn { padding:10px 22px; border:none; border-radius:8px; font-size:15px; cursor:pointer; background:#3b3b98; color:#fff; }
    button.nav-btn:disabled { background:#ccc; cursor:not-allowed; }
    button.nav-btn.secondary { background:#eee; color:#333; }
    #loadingBox { display:none; text-align:center; padding:40px 0; }
</style>
</head>
<body>
<div class="container">
    <h1>แบบทดสอบ: คณะที่ใช่ตามสไตล์ MBTI</h1>
    <p class="subtitle">กรอกเกรดและตอบคำถามเพื่อค้นหาคณะที่ใช่สำหรับคุณ</p>

    <div class="progress-bar"><div class="progress-fill" id="progressFill"></div></div>

    <!-- STEP 0: ฟอร์มกรอกเกรด -->
    <div class="step active" data-step="0">
        <div class="question-text">กรอกเกรดเฉลี่ยแต่ละวิชา (0.00 - 4.00)</div>

        <div class="grade-row"><label>คณิตศาสตร์</label><input type="number" step="0.01" min="0" max="4" id="grade_math"></div>
        <div class="grade-row"><label>วิทยาศาสตร์</label><input type="number" step="0.01" min="0" max="4" id="grade_sci"></div>
        <div class="grade-row"><label>ภาษาอังกฤษ</label><input type="number" step="0.01" min="0" max="4" id="grade_eng"></div>
        <div class="grade-row"><label>ภาษาไทย</label><input type="number" step="0.01" min="0" max="4" id="grade_thai"></div>
        <div class="grade-row"><label>สังคมศึกษา</label><input type="number" step="0.01" min="0" max="4" id="grade_social"></div>
        <div class="grade-row"><label>ศิลปะ</label><input type="number" step="0.01" min="0" max="4" id="grade_art"></div>

        <div class="nav-buttons">
            <span></span>
            <button type="button" class="nav-btn" id="gradeNextBtn" onclick="goToQuestions()">ถัดไป</button>
        </div>
    </div>

    <!-- STEP 1..N: คำถาม MBTI จาก database -->
    <?php foreach ($questions as $index => $q): ?>
        <div class="step" data-step="<?php echo $index + 1; ?>" data-qid="<?php echo (int)$q['id']; ?>">
            <div class="question-number">ข้อที่ <?php echo $index + 1; ?> / <?php echo count($questions); ?></div>
            <div class="question-text"><?php echo htmlspecialchars($q['question_text'], ENT_QUOTES, 'UTF-8'); ?></div>

            <div class="option" data-trait="<?php echo htmlspecialchars($q['option_a_trait']); ?>" onclick="selectOption(this)">
                <?php echo htmlspecialchars($q['option_a_text'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div class="option" data-trait="<?php echo htmlspecialchars($q['option_b_trait']); ?>" onclick="selectOption(this)">
                <?php echo htmlspecialchars($q['option_b_text'], ENT_QUOTES, 'UTF-8'); ?>
            </div>

            <div class="nav-buttons">
                <button type="button" class="nav-btn secondary" onclick="prevStep()">ย้อนกลับ</button>
                <?php if ($index === count($questions) - 1): ?>
                    <button type="button" class="nav-btn" id="submitBtn" onclick="submitQuiz()" disabled>ดูผลลัพธ์</button>
                <?php else: ?>
                    <button type="button" class="nav-btn" onclick="nextStep()" disabled>ถัดไป</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div id="loadingBox">
        <p>กำลังประมวลผล กรุณารอสักครู่...</p>
    </div>
</div>

<script>
const totalQuestions = <?php echo count($questions); ?>;
const totalSteps = totalQuestions + 1; // +1 คือหน้ากรอกเกรด
let currentStep = 0;
let answers = {}; // { qid: trait }

function updateProgress() {
    const percent = (currentStep / totalSteps) * 100;
    document.getElementById('progressFill').style.width = percent + '%';
}

function showStep(step) {
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    document.querySelector('.step[data-step="' + step + '"]').classList.add('active');
    updateProgress();
}

function goToQuestions() {
    const ids = ['grade_math','grade_sci','grade_eng','grade_thai','grade_social','grade_art'];
    for (const id of ids) {
        const val = document.getElementById(id).value;
        if (val === '' || isNaN(val) || val < 0 || val > 4) {
            alert('กรุณากรอกเกรดให้ครบและถูกต้อง (0.00 - 4.00)');
            return;
        }
    }
    currentStep = 1;
    showStep(currentStep);
}

function selectOption(el) {
    const stepDiv = el.closest('.step');
    stepDiv.querySelectorAll('.option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');

    const qid = stepDiv.getAttribute('data-qid');
    answers[qid] = el.getAttribute('data-trait');

    const nextBtn = stepDiv.querySelector('.nav-btn:not(.secondary)');
    if (nextBtn) nextBtn.disabled = false;
}

function nextStep() {
    const stepDiv = document.querySelector('.step[data-step="' + currentStep + '"]');
    const qid = stepDiv.getAttribute('data-qid');
    if (!answers[qid]) { alert('กรุณาเลือกคำตอบก่อนไปข้อถัดไป'); return; }
    if (currentStep < totalSteps - 1) {
        currentStep++;
        showStep(currentStep);
    }
}

function prevStep() {
    if (currentStep > 0) {
        currentStep--;
        showStep(currentStep);
    }
}

function submitQuiz() {
    const stepDiv = document.querySelector('.step[data-step="' + currentStep + '"]');
    const qid = stepDiv.getAttribute('data-qid');
    if (!answers[qid]) { alert('กรุณาเลือกคำตอบก่อนดูผลลัพธ์'); return; }

    const counts = { E:0,I:0,S:0,N:0,T:0,F:0,J:0,P:0 };
    Object.values(answers).forEach(t => { if (counts.hasOwnProperty(t)) counts[t]++; });

    const mbtiType =
        (counts.E >= counts.I ? 'E' : 'I') +
        (counts.S >= counts.N ? 'S' : 'N') +
        (counts.T >= counts.F ? 'T' : 'F') +
        (counts.J >= counts.P ? 'J' : 'P');

    const payload = {
        mbti_type: mbtiType,
        grades: {
            math:   parseFloat(document.getElementById('grade_math').value),
            sci:    parseFloat(document.getElementById('grade_sci').value),
            eng:    parseFloat(document.getElementById('grade_eng').value),
            thai:   parseFloat(document.getElementById('grade_thai').value),
            social: parseFloat(document.getElementById('grade_social').value),
            art:    parseFloat(document.getElementById('grade_art').value)
        }
    };

    document.querySelector('.step.active').classList.remove('active');
    document.getElementById('loadingBox').style.display = 'block';

    fetch('save_result.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'result.html?id=' + data.result_id;
        } else {
            alert('เกิดข้อผิดพลาด: ' + (data.error || 'ไม่ทราบสาเหตุ'));
            document.getElementById('loadingBox').style.display = 'none';
            document.querySelector('.step[data-step="' + currentStep + '"]').classList.add('active');
        }
    })
    .catch(err => {
        alert('เชื่อมต่อเซิร์ฟเวอร์ไม่สำเร็จ');
        document.getElementById('loadingBox').style.display = 'none';
        document.querySelector('.step[data-step="' + currentStep + '"]').classList.add('active');
    });
}

updateProgress();
</script>
</body>
</html>