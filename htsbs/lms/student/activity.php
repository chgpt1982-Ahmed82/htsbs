<?php
/*
=====================================================================
LMS - حل النشاط (5 أنواع) مع تقييم فوري عبر AJAX
mcq | true_false | ordering | matching | short_answer | project
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';

lms_require_role(3);

$student = $lms->getStudentByUserId((int)$_SESSION['user_id']);
if (!$student) exit('Student Not Found');

$studentId  = (int)$student['id'];
$activityId = (int)($_GET['id'] ?? 0);

// جلب النشاط مع التحقق من صف الطالب
$stmt = $db->prepare("
    SELECT a.*, l.title AS lesson_title, l.pass_grade, l.id AS lesson_id, l.course_id
    FROM lms_activities a
    INNER JOIN lms_lessons l ON a.lesson_id = l.id
    INNER JOIN course_assignments ca ON ca.course_id = l.course_id AND ca.class_id = ?
    WHERE a.id = ?
    LIMIT 1
");
$stmt->execute([(int)$student['class_id'], $activityId]);
$activity = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$activity) exit('النشاط غير موجود أو غير متاح');

// حماية القفل التسلسلي للدرس
if (!$lms->isLessonUnlocked((int)$activity['lesson_id'], $studentId)) {
    exit('🔒 هذا الدرس مقفل');
}

// حماية تسلسل الأنشطة: يجب اجتياز الأنشطة الأقل ترتيباً
$stmt = $db->prepare("
    SELECT COUNT(*) FROM lms_activities a2
    WHERE a2.lesson_id = ? AND a2.activity_order < ?
      AND NOT EXISTS (
          SELECT 1 FROM lms_student_activity_attempts att
          WHERE att.activity_id = a2.id AND att.student_id = ? AND att.is_passed = 1
      )
");
$stmt->execute([(int)$activity['lesson_id'], (int)$activity['activity_order'], $studentId]);
if ((int)$stmt->fetchColumn() > 0) {
    exit('🔒 أكمل الأنشطة السابقة أولاً');
}

$lms->touchLessonProgress((int)$activity['lesson_id'], $studentId, $activityId);

// الأسئلة مع الإجابات (دون كشف الصحيح في الواجهة)
$stmt = $db->prepare("
    SELECT id, question_text, points, question_order
    FROM lms_activity_questions
    WHERE activity_id = ? ORDER BY question_order, id
");
$stmt->execute([$activityId]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$answersByQ = [];
if ($questions) {
    $ids = array_column($questions, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("
        SELECT id, question_id, answer_text, match_key, match_side, correct_order
        FROM lms_activity_answers
        WHERE question_id IN ($in)
    ");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ans) {
        $answersByQ[$ans['question_id']][] = $ans;
    }
}

$typeLabels = [
    'mcq' => 'اختيار من متعدد', 'true_false' => 'صح أو خطأ',
    'ordering' => 'رتّب العناصر', 'matching' => 'صِل بين العمودين',
    'short_answer' => 'سؤال قصير', 'project' => 'مشروع / رفع ملف'
];
$type = $activity['activity_type'];

include dirname(__DIR__, 2) . '/app/views/layouts/header.php';
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container-fluid px-0">
<div class="row flex-lg-row-reverse g-0">

<?php include dirname(__DIR__, 2) . '/app/views/layouts/student_sidebar.php'; ?>

<div class="main-content">

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="lesson.php?id=<?= (int)$activity['lesson_id'] ?>"><?= e($activity['lesson_title']) ?></a></li>
    <li class="breadcrumb-item active"><?= e($activity['title']) ?></li>
  </ol>
</nav>

<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
    <div>
      <span class="fs-5"><?= str_repeat('⭐', (int)$activity['activity_order']) ?></span>
      <strong class="ms-2"><?= e($activity['title']) ?></strong>
      <span class="badge bg-primary ms-2"><?= e($typeLabels[$type] ?? $type) ?></span>
    </div>
    <div>
      <span class="badge bg-dark" id="timerBadge"><i class="bi bi-stopwatch"></i> 00:00</span>
      <span class="badge bg-success">النجاح: <?= e($activity['pass_grade']) ?>%</span>
    </div>
  </div>

  <div class="card-body">
    <?php if (!empty($activity['description'])): ?>
      <div class="alert alert-light border"><?= nl2br(e($activity['description'])) ?></div>
    <?php endif; ?>

    <form id="activityForm" enctype="multipart/form-data">
      <?= lms_csrf_field() ?>
      <input type="hidden" name="activity_id" value="<?= (int)$activityId ?>">
      <input type="hidden" name="started_at" id="startedAt" value="">

      <?php if ($type === 'project'): ?>
        <!-- ⭐⭐⭐⭐⭐ مشروع: نص + رفع ملف -->
        <?php foreach ($questions as $q): ?>
          <div class="mb-3 p-3 border rounded">
            <label class="fw-bold mb-2 d-block"><?= nl2br(e($q['question_text'])) ?></label>
            <textarea class="form-control" name="answers[<?= (int)$q['id'] ?>]" rows="5"
                      placeholder="اكتب حلك أو وصف مشروعك هنا..."></textarea>
          </div>
        <?php endforeach; ?>
        <div class="mb-3">
          <label class="fw-bold">رفع ملف المشروع (اختياري)</label>
          <input type="file" class="form-control" name="project_file"
                 accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.rar,.png,.jpg,.jpeg,.py,.cpp,.java,.txt">
          <small class="text-muted">الحد الأقصى 50MB - سيقوم المعلم بتصحيح مشروعك</small>
        </div>

      <?php else: ?>

        <?php foreach ($questions as $qi => $q):
            $qid  = (int)$q['id'];
            $opts = $answersByQ[$qid] ?? [];
        ?>
        <div class="mb-4 p-3 border rounded question-block">
          <label class="fw-bold mb-2 d-block">
            <span class="badge bg-secondary"><?= $qi + 1 ?></span>
            <?= nl2br(e($q['question_text'])) ?>
            <small class="text-muted">(<?= e($q['points']) ?> نقطة)</small>
          </label>

          <?php if ($type === 'mcq'): ?>
            <?php foreach ($opts as $o): ?>
              <div class="form-check">
                <input class="form-check-input" type="radio"
                       name="answers[<?= $qid ?>]" value="<?= (int)$o['id'] ?>"
                       id="opt<?= (int)$o['id'] ?>">
                <label class="form-check-label" for="opt<?= (int)$o['id'] ?>">
                  <?= e($o['answer_text']) ?>
                </label>
              </div>
            <?php endforeach; ?>

          <?php elseif ($type === 'true_false'): ?>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="answers[<?= $qid ?>]" value="true" id="t<?= $qid ?>">
              <label class="form-check-label" for="t<?= $qid ?>">✔ صح</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="answers[<?= $qid ?>]" value="false" id="f<?= $qid ?>">
              <label class="form-check-label" for="f<?= $qid ?>">✘ خطأ</label>
            </div>

          <?php elseif ($type === 'ordering'): ?>
            <?php
              // خلط العناصر للعرض
              $shuffled = $opts;
              shuffle($shuffled);
            ?>
            <p class="small text-muted">استخدم الأسهم لترتيب العناصر بالترتيب الصحيح (من الأعلى للأسفل)</p>
            <ul class="list-group ordering-list" data-question="<?= $qid ?>">
              <?php foreach ($shuffled as $o): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center"
                    data-answer="<?= (int)$o['id'] ?>">
                  <span><?= e($o['answer_text']) ?></span>
                  <span>
                    <button type="button" class="btn btn-sm btn-outline-secondary move-up"><i class="bi bi-arrow-up"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary move-down"><i class="bi bi-arrow-down"></i></button>
                  </span>
                </li>
              <?php endforeach; ?>
            </ul>

          <?php elseif ($type === 'matching'): ?>
            <?php
              $left  = array_values(array_filter($opts, fn($o) => $o['match_side'] === 'left'));
              $right = array_values(array_filter($opts, fn($o) => $o['match_side'] === 'right'));
              shuffle($right);
            ?>
            <p class="small text-muted">اختر من القائمة ما يناسب كل عنصر</p>
            <?php foreach ($left as $lft): ?>
              <div class="row align-items-center mb-2">
                <div class="col-5 fw-bold"><?= e($lft['answer_text']) ?></div>
                <div class="col-2 text-center">⟵</div>
                <div class="col-5">
                  <select class="form-select" name="answers[<?= $qid ?>][<?= (int)$lft['id'] ?>]">
                    <option value="">اختر...</option>
                    <?php foreach ($right as $r): ?>
                      <option value="<?= (int)$r['id'] ?>"><?= e($r['answer_text']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            <?php endforeach; ?>

          <?php elseif ($type === 'short_answer'): ?>
            <input type="text" class="form-control" name="answers[<?= $qid ?>]"
                   placeholder="اكتب إجابتك هنا..." autocomplete="off">
          <?php endif; ?>
        </div>
        <?php endforeach; ?>

      <?php endif; ?>

      <button type="submit" class="btn btn-success btn-lg w-100" id="submitBtn">
        <i class="bi bi-send"></i> تسليم النشاط
      </button>
    </form>

    <!-- نتيجة النشاط -->
    <div id="resultBox" class="mt-4 d-none"></div>
  </div>
</div>

</div>
</div>
</div>

<script>
/*
====================================
منطق النشاط: المؤقت + الترتيب + الإرسال AJAX
====================================
*/
(function () {
    const form      = document.getElementById('activityForm');
    const submitBtn = document.getElementById('submitBtn');
    const resultBox = document.getElementById('resultBox');
    const timerEl   = document.getElementById('timerBadge');

    // المؤقت
    const startTime = Date.now();
    document.getElementById('startedAt').value = Math.floor(startTime / 1000);

    setInterval(function () {
        const s = Math.floor((Date.now() - startTime) / 1000);
        const mm = String(Math.floor(s / 60)).padStart(2, '0');
        const ss = String(s % 60).padStart(2, '0');
        timerEl.innerHTML = '<i class="bi bi-stopwatch"></i> ' + mm + ':' + ss;
    }, 1000);

    // أزرار الترتيب أعلى/أسفل
    document.addEventListener('click', function (e) {
        const up   = e.target.closest('.move-up');
        const down = e.target.closest('.move-down');
        if (up) {
            const li = up.closest('li');
            if (li.previousElementSibling) li.parentNode.insertBefore(li, li.previousElementSibling);
        }
        if (down) {
            const li = down.closest('li');
            if (li.nextElementSibling) li.parentNode.insertBefore(li.nextElementSibling, li);
        }
    });

    // الإرسال
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> جارٍ التصحيح...';

        const fd = new FormData(form);

        // جمع ترتيب عناصر ordering
        document.querySelectorAll('.ordering-list').forEach(function (ul) {
            const qid = ul.dataset.question;
            ul.querySelectorAll('li').forEach(function (li, idx) {
                fd.append('answers[' + qid + '][' + idx + ']', li.dataset.answer);
            });
        });

        try {
            const res  = await fetch('ajax/submit_activity.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (!data.success) {
                Swal.fire('تنبيه', data.message || 'حدث خطأ', 'warning');
                return;
            }

            renderResult(data);

            // Toast للإنجازات
            if (data.star_awarded) {
                Swal.fire({ toast: true, position: 'top', icon: 'success',
                    title: '⭐ حصلت على نجمة ذهبية!', showConfirmButton: false, timer: 4000 });
            }
            (data.new_badges || []).forEach(function (b, i) {
                setTimeout(function () {
                    Swal.fire({ toast: true, position: 'top', icon: 'success',
                        title: b.icon + ' شارة جديدة: ' + b.title,
                        showConfirmButton: false, timer: 4000 });
                }, 1200 * (i + 1));
            });
            if (data.certificate) {
                setTimeout(function () {
                    Swal.fire('مبروك! 🎓', 'استحققت شهادة إتمام المقرر', 'success');
                }, 2500);
            }
        } catch (err) {
            Swal.fire('خطأ', 'تعذر الاتصال بالخادم', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> محاولة جديدة';
        }
    });

    // عرض النتيجة التفصيلية
    function renderResult(d) {
        let html = '<div class="card border-' + (d.is_passed ? 'success' : 'danger') + '">';
        html += '<div class="card-header bg-' + (d.is_passed ? 'success' : 'danger') + ' text-white fw-bold">';
        html += d.is_passed ? '🎉 نجحت في النشاط' : '📝 لم تصل لدرجة النجاح بعد';
        html += '</div><div class="card-body">';

        html += '<div class="row text-center mb-3">';
        html += stat('الدرجة', d.score + '%');
        html += stat('عدد المحاولات', d.attempt_no);
        html += stat('الزمن المستغرق', d.duration_text);
        html += stat('أفضل نتيجة', d.best_score + '%');
        html += '</div>';

        if (d.pending_grading) {
            html += '<div class="alert alert-info">📤 تم استلام مشروعك وسيقوم المعلم بتصحيحه قريباً</div>';
        }

        // تفاصيل الأسئلة: الإجابة الصحيحة + الشرح
        (d.details || []).forEach(function (q, i) {
            html += '<div class="border rounded p-3 mb-2 ' + (q.correct ? 'border-success' : 'border-danger') + '">';
            html += '<strong>' + (i + 1) + '. ' + escapeHtml(q.question) + '</strong> ';
            html += q.correct ? '<span class="badge bg-success">✔ صحيح</span>'
                              : '<span class="badge bg-danger">✘ خطأ</span>';
            html += '<div class="small mt-1">إجابتك: ' + escapeHtml(q.your_answer || '—') + '</div>';
            if (!q.correct) {
                html += '<div class="small text-success">الإجابة الصحيحة: ' + escapeHtml(q.correct_answer || '') + '</div>';
            }
            if (q.explanation) {
                html += '<div class="small text-muted mt-1">💡 ' + escapeHtml(q.explanation) + '</div>';
            }
            html += '</div>';
        });

        // ملاحظة تشجيعية
        html += '<div class="alert alert-' + (d.is_passed ? 'success' : 'warning') + ' mb-0 mt-2">'
              + escapeHtml(d.motivation) + '</div>';

        if (d.is_passed) {
            html += '<a href="lesson.php?id=<?= (int)$activity['lesson_id'] ?>" class="btn btn-primary mt-3 w-100">'
                  + '<i class="bi bi-arrow-left"></i> العودة للدرس ومتابعة النشاط التالي</a>';
        }

        html += '</div></div>';
        resultBox.innerHTML = html;
        resultBox.classList.remove('d-none');
        resultBox.scrollIntoView({ behavior: 'smooth' });
    }

    function stat(label, value) {
        return '<div class="col-6 col-md-3"><div class="border rounded p-2">'
             + '<h5 class="fw-bold mb-0">' + value + '</h5>'
             + '<small class="text-muted">' + label + '</small></div></div>';
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
})();
</script>

<?php include dirname(__DIR__, 2) . '/app/views/layouts/footer.php'; ?>
