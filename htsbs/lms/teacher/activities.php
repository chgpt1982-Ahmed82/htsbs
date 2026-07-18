<?php
/*
=====================================================================
LMS - إدارة أنشطة الدرس الخمسة (معلم)
النشاط 1: mcq | 2: true_false | 3: ordering/matching | 4: short_answer | 5: project
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';

lms_require_role(2);

$teacher = $lms->getTeacherByUserId((int)$_SESSION['user_id']);
if (!$teacher) exit('Teacher Not Found');

$teacherId = (int)$teacher['id'];
$lessonId  = (int)($_GET['lesson_id'] ?? $_POST['lesson_id'] ?? 0);
$flash = null;

// التأكد من ملكية الدرس
/*
🔧 التحقق الآن: هل المقرر مسند لهذا المعلم؟ (بدل ملكية الدرس حصراً)
يسمح لأي معلم مشارك في تدريس المقرر بإدارة الأنشطة التفاعلية
*/
$stmt = $db->prepare("
    SELECT l.*, c.course_name
    FROM lms_lessons l
    INNER JOIN courses c ON l.course_id = c.id
    WHERE l.id = ?
      AND EXISTS (
          SELECT 1 FROM course_assignments ca
          WHERE ca.teacher_id = ? AND ca.course_id = l.course_id
      )
");
$stmt->execute([$lessonId, $teacherId]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) exit('الدرس غير موجود أو لا تملك صلاحية عليه');

// الأنواع المسموحة لكل مستوى صعوبة
$levelTypes = [
    1 => ['mcq' => 'اختيار من متعدد'],
    2 => ['true_false' => 'صح أو خطأ'],
    3 => ['ordering' => 'ترتيب', 'matching' => 'توصيل'],
    4 => ['short_answer' => 'سؤال قصير'],
    5 => ['project' => 'مشروع / رفع ملف / سؤال برمجي'],
];

/* ==================== معالجة النماذج ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    lms_csrf_check();

    try {
        $action = $_POST['action'] ?? '';

        /* ----- إنشاء نشاط ----- */
        if ($action === 'save_activity') {
            $order = (int)($_POST['activity_order'] ?? 0);
            $type  = (string)($_POST['activity_type'] ?? '');
            $title = trim((string)($_POST['title'] ?? ''));

            if ($order < 1 || $order > 5 || !isset($levelTypes[$order][$type]) || $title === '') {
                throw new Exception('تحقق من مستوى النشاط ونوعه وعنوانه');
            }

            $db->prepare("
                INSERT INTO lms_activities (lesson_id, activity_order, activity_type, title, description)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    activity_type = VALUES(activity_type),
                    title = VALUES(title),
                    description = VALUES(description)
            ")->execute([
                $lessonId, $order, $type, $title,
                trim((string)($_POST['description'] ?? ''))
            ]);
            $lms->log((int)$_SESSION['user_id'], 'save_activity', "lesson=$lessonId order=$order");
            $flash = ['success', 'تم حفظ النشاط - أضف أسئلته الآن'];
        }

        /* ----- إضافة سؤال بإجاباته ----- */
        if ($action === 'add_question') {
            $activityId = (int)($_POST['activity_id'] ?? 0);

            // التأكد أن النشاط يتبع هذا الدرس
            $stmt = $db->prepare("SELECT * FROM lms_activities WHERE id = ? AND lesson_id = ?");
            $stmt->execute([$activityId, $lessonId]);
            $act = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$act) throw new Exception('النشاط غير موجود');

            $qText = trim((string)($_POST['question_text'] ?? ''));
            if ($qText === '') throw new Exception('نص السؤال مطلوب');

            $db->prepare("
                INSERT INTO lms_activity_questions
                    (activity_id, question_order, question_text, points, explanation)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([
                $activityId,
                (int)($_POST['question_order'] ?? 1),
                $qText,
                max(0.5, (float)($_POST['points'] ?? 10)),
                trim((string)($_POST['explanation'] ?? ''))
            ]);
            $qid = (int)$db->lastInsertId();

            $type = $act['activity_type'];

            if ($type === 'mcq') {
                // 4 خيارات + رقم الخيار الصحيح
                $correctIdx = (int)($_POST['correct_option'] ?? 1);
                foreach (($_POST['options'] ?? []) as $i => $opt) {
                    $opt = trim((string)$opt);
                    if ($opt === '') continue;
                    $db->prepare("
                        INSERT INTO lms_activity_answers (question_id, answer_text, is_correct)
                        VALUES (?, ?, ?)
                    ")->execute([$qid, $opt, ((int)$i + 1) === $correctIdx ? 1 : 0]);
                }

            } elseif ($type === 'true_false') {
                $isTrue = ($_POST['tf_answer'] ?? 'true') === 'true';
                $db->prepare("
                    INSERT INTO lms_activity_answers (question_id, answer_text, is_correct)
                    VALUES (?, ?, 1)
                ")->execute([$qid, $isTrue ? 'صح' : 'خطأ']);

            } elseif ($type === 'ordering') {
                // العناصر بترتيبها الصحيح (سطر لكل عنصر)
                $items = preg_split('/\r\n|\n/', (string)($_POST['ordering_items'] ?? ''));
                $ord = 1;
                foreach ($items as $item) {
                    $item = trim($item);
                    if ($item === '') continue;
                    $db->prepare("
                        INSERT INTO lms_activity_answers (question_id, answer_text, correct_order)
                        VALUES (?, ?, ?)
                    ")->execute([$qid, $item, $ord++]);
                }
                if ($ord < 3) throw new Exception('أدخل عنصرين على الأقل للترتيب');

            } elseif ($type === 'matching') {
                // أزواج: يسار = يمين (سطر لكل زوج بصيغة "يسار = يمين")
                $pairs = preg_split('/\r\n|\n/', (string)($_POST['matching_pairs'] ?? ''));
                $k = 1;
                foreach ($pairs as $pair) {
                    if (strpos($pair, '=') === false) continue;
                    [$lft, $rgt] = array_map('trim', explode('=', $pair, 2));
                    if ($lft === '' || $rgt === '') continue;
                    $key = 'k' . $k++;
                    $db->prepare("
                        INSERT INTO lms_activity_answers (question_id, answer_text, match_key, match_side)
                        VALUES (?, ?, ?, 'left'), (?, ?, ?, 'right')
                    ")->execute([$qid, $lft, $key, $qid, $rgt, $key]);
                }
                if ($k < 3) throw new Exception('أدخل زوجين على الأقل بصيغة: الطرف الأول = الطرف الثاني');

            } elseif ($type === 'short_answer') {
                // إجابات نموذجية مقبولة (سطر لكل إجابة)
                $models = preg_split('/\r\n|\n/', (string)($_POST['model_answers'] ?? ''));
                $added = 0;
                foreach ($models as $m) {
                    $m = trim($m);
                    if ($m === '') continue;
                    $db->prepare("
                        INSERT INTO lms_activity_answers (question_id, answer_text, is_correct)
                        VALUES (?, ?, 1)
                    ")->execute([$qid, $m]);
                    $added++;
                }
                if ($added === 0) throw new Exception('أدخل إجابة نموذجية واحدة على الأقل');
            }
            // project: لا إجابات نموذجية - تصحيح يدوي

            $flash = ['success', 'تمت إضافة السؤال'];
        }

        /* ----- حذف سؤال ----- */
        if ($action === 'delete_question') {
            $db->prepare("
                DELETE q FROM lms_activity_questions q
                INNER JOIN lms_activities a ON q.activity_id = a.id
                WHERE q.id = ? AND a.lesson_id = ?
            ")->execute([(int)($_POST['question_id'] ?? 0), $lessonId]);
            $flash = ['success', 'تم حذف السؤال'];
        }

        /* ----- حذف نشاط ----- */
        if ($action === 'delete_activity') {
            $db->prepare("DELETE FROM lms_activities WHERE id = ? AND lesson_id = ?")
               ->execute([(int)($_POST['activity_id'] ?? 0), $lessonId]);
            $flash = ['success', 'تم حذف النشاط'];
        }

    } catch (Exception $ex) {
        $flash = ['danger', $ex->getMessage()];
    }
}

/* ==================== الأنشطة الحالية ==================== */
$stmt = $db->prepare("
    SELECT a.*,
           (SELECT COUNT(*) FROM lms_activity_questions WHERE activity_id = a.id) AS q_count
    FROM lms_activities a WHERE a.lesson_id = ? ORDER BY a.activity_order
");
$stmt->execute([$lessonId]);
$activities = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $a) {
    $activities[(int)$a['activity_order']] = $a;
}

$allTypeLabels = [
    'mcq' => 'اختيار من متعدد', 'true_false' => 'صح أو خطأ',
    'ordering' => 'ترتيب', 'matching' => 'توصيل',
    'short_answer' => 'سؤال قصير', 'project' => 'مشروع'
];

include dirname(__DIR__, 2) . '/app/views/layouts/header.php';
?>

<div class="container-fluid px-0">
<div class="row flex-lg-row-reverse g-0">

<?php include dirname(__DIR__, 2) . '/app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="lessons.php">دروسي</a></li>
    <li class="breadcrumb-item active">أنشطة: <?= e($lesson['title']) ?></li>
  </ol>
</nav>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash[0] ?> alert-dismissible fade show">
  <?= e($flash[1]) ?><button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="accordion" id="activitiesAcc">
<?php for ($lvl = 1; $lvl <= 5; $lvl++):
    $a = $activities[$lvl] ?? null; ?>
  <div class="accordion-item mb-2 border rounded shadow-sm">
    <h2 class="accordion-header">
      <button class="accordion-button <?= $a ? 'collapsed' : '' ?>" type="button"
              data-bs-toggle="collapse" data-bs-target="#lvl<?= $lvl ?>">
        <span class="me-2"><?= str_repeat('⭐', $lvl) ?></span>
        النشاط <?= $lvl ?>:
        <?php if ($a): ?>
          <strong class="ms-1"><?= e($a['title']) ?></strong>
          <span class="badge bg-primary ms-2"><?= e($allTypeLabels[$a['activity_type']]) ?></span>
          <span class="badge bg-<?= (int)$a['q_count'] > 0 ? 'success' : 'danger' ?> ms-1">
            <?= (int)$a['q_count'] ?> سؤال</span>
        <?php else: ?>
          <span class="badge bg-warning text-dark ms-2">لم يُنشأ بعد</span>
        <?php endif; ?>
      </button>
    </h2>
    <div id="lvl<?= $lvl ?>" class="accordion-collapse collapse <?= (!$a && $lvl === 1) ? 'show' : '' ?>"
         data-bs-parent="#activitiesAcc">
      <div class="accordion-body">

        <!-- إنشاء/تعديل النشاط -->
        <form method="post" class="border rounded p-3 bg-light mb-3">
          <?= lms_csrf_field() ?>
          <input type="hidden" name="action" value="save_activity">
          <input type="hidden" name="lesson_id" value="<?= (int)$lessonId ?>">
          <input type="hidden" name="activity_order" value="<?= $lvl ?>">
          <div class="row g-2">
            <div class="col-md-3">
              <label class="form-label fw-bold">النوع</label>
              <select name="activity_type" class="form-select">
                <?php foreach ($levelTypes[$lvl] as $tk => $tl): ?>
                  <option value="<?= $tk ?>"
                    <?= ($a && $a['activity_type'] === $tk) ? 'selected' : '' ?>><?= $tl ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">عنوان النشاط</label>
              <input type="text" name="title" class="form-control" required
                     value="<?= e($a['title'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">تعليمات النشاط</label>
              <input type="text" name="description" class="form-control"
                     value="<?= e($a['description'] ?? '') ?>">
            </div>
            <div class="col-md-1 d-flex align-items-end">
              <button class="btn btn-primary w-100"><i class="bi bi-save"></i></button>
            </div>
          </div>
        </form>

        <?php if ($a): ?>

        <!-- إضافة سؤال حسب النوع -->
        <?php if ($a['activity_type'] !== 'project' || (int)$a['q_count'] === 0): ?>
        <form method="post" class="border rounded p-3 mb-3">
          <?= lms_csrf_field() ?>
          <input type="hidden" name="action" value="add_question">
          <input type="hidden" name="lesson_id" value="<?= (int)$lessonId ?>">
          <input type="hidden" name="activity_id" value="<?= (int)$a['id'] ?>">

          <h6 class="fw-bold">➕ إضافة سؤال</h6>
          <div class="row g-2">
            <div class="col-md-7">
              <label class="form-label">نص السؤال / المطلوب</label>
              <textarea name="question_text" class="form-control" rows="2" required></textarea>
            </div>
            <div class="col-md-2">
              <label class="form-label">النقاط</label>
              <input type="number" name="points" class="form-control" value="10" min="1" step="0.5">
            </div>
            <div class="col-md-3">
              <label class="form-label">ترتيب السؤال</label>
              <input type="number" name="question_order" class="form-control"
                     value="<?= (int)$a['q_count'] + 1 ?>" min="1">
            </div>

            <?php if ($a['activity_type'] === 'mcq'): ?>
              <?php for ($i = 1; $i <= 4; $i++): ?>
                <div class="col-md-6">
                  <div class="input-group">
                    <span class="input-group-text">
                      <input type="radio" name="correct_option" value="<?= $i ?>"
                             <?= $i === 1 ? 'checked' : '' ?> title="الإجابة الصحيحة">
                    </span>
                    <input type="text" name="options[]" class="form-control"
                           placeholder="الخيار <?= $i ?>" <?= $i <= 2 ? 'required' : '' ?>>
                  </div>
                </div>
              <?php endfor; ?>
              <div class="col-12"><small class="text-muted">حدد الإجابة الصحيحة بالدائرة بجانب الخيار</small></div>

            <?php elseif ($a['activity_type'] === 'true_false'): ?>
              <div class="col-md-6">
                <label class="form-label">الإجابة الصحيحة</label>
                <select name="tf_answer" class="form-select">
                  <option value="true">✔ صح</option>
                  <option value="false">✘ خطأ</option>
                </select>
              </div>

            <?php elseif ($a['activity_type'] === 'ordering'): ?>
              <div class="col-12">
                <label class="form-label">العناصر بترتيبها الصحيح <small class="text-muted">(سطر لكل عنصر - ستُعرض مخلوطة للطالب)</small></label>
                <textarea name="ordering_items" class="form-control" rows="4" required
                          placeholder="الخطوة الأولى&#10;الخطوة الثانية&#10;الخطوة الثالثة"></textarea>
              </div>

            <?php elseif ($a['activity_type'] === 'matching'): ?>
              <div class="col-12">
                <label class="form-label">أزواج التوصيل <small class="text-muted">(سطر لكل زوج بصيغة: الطرف الأول = الطرف الثاني)</small></label>
                <textarea name="matching_pairs" class="form-control" rows="4" required
                          placeholder="HTML = لغة هيكلة الصفحات&#10;CSS = لغة تنسيق الصفحات"></textarea>
              </div>

            <?php elseif ($a['activity_type'] === 'short_answer'): ?>
              <div class="col-12">
                <label class="form-label">الإجابات النموذجية المقبولة <small class="text-muted">(سطر لكل صياغة مقبولة)</small></label>
                <textarea name="model_answers" class="form-control" rows="3" required
                          placeholder="المعالج&#10;وحدة المعالجة المركزية&#10;CPU"></textarea>
              </div>
            <?php else: ?>
              <div class="col-12">
                <small class="text-muted">📤 نشاط المشروع: اكتب المطلوب في نص السؤال، وسيرفع الطالب ملفه ثم تصححه من صفحة "تصحيح المشاريع"</small>
              </div>
            <?php endif; ?>

            <?php if ($a['activity_type'] !== 'project'): ?>
            <div class="col-12">
              <label class="form-label">شرح سبب صحة الإجابة <small class="text-muted">(يظهر للطالب بعد التصحيح)</small></label>
              <textarea name="explanation" class="form-control" rows="2"></textarea>
            </div>
            <?php endif; ?>

            <div class="col-12">
              <button class="btn btn-success"><i class="bi bi-plus-lg"></i> إضافة السؤال</button>
            </div>
          </div>
        </form>
        <?php endif; ?>

        <!-- أسئلة النشاط الحالية -->
        <?php
        $stmt = $db->prepare("
            SELECT q.*,
                   (SELECT GROUP_CONCAT(
                        CASE
                          WHEN ans.is_correct = 1 THEN CONCAT('✔ ', ans.answer_text)
                          WHEN ans.correct_order IS NOT NULL THEN CONCAT(ans.correct_order, ') ', ans.answer_text)
                          WHEN ans.match_side = 'left' THEN CONCAT(ans.answer_text, ' =')
                          WHEN ans.match_side = 'right' THEN ans.answer_text
                          ELSE ans.answer_text
                        END SEPARATOR ' | ')
                      FROM lms_activity_answers ans WHERE ans.question_id = q.id) AS answers_preview
            FROM lms_activity_questions q
            WHERE q.activity_id = ? ORDER BY q.question_order, q.id
        ");
        $stmt->execute([(int)$a['id']]);
        $qs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <?php if ($qs): ?>
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle">
            <thead class="table-light">
              <tr><th>#</th><th>السؤال</th><th>الإجابات</th><th>النقاط</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($qs as $q): ?>
              <tr>
                <td><?= (int)$q['question_order'] ?></td>
                <td><?= e(mb_substr($q['question_text'], 0, 60)) ?></td>
                <td class="small text-muted"><?= e(mb_substr($q['answers_preview'] ?? '—', 0, 80)) ?></td>
                <td><?= e($q['points']) ?></td>
                <td>
                  <form method="post" onsubmit="return confirm('حذف السؤال؟')">
                    <?= lms_csrf_field() ?>
                    <input type="hidden" name="action" value="delete_question">
                    <input type="hidden" name="lesson_id" value="<?= (int)$lessonId ?>">
                    <input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <form method="post" class="text-end" onsubmit="return confirm('حذف النشاط بجميع أسئلته ونتائجه؟')">
          <?= lms_csrf_field() ?>
          <input type="hidden" name="action" value="delete_activity">
          <input type="hidden" name="lesson_id" value="<?= (int)$lessonId ?>">
          <input type="hidden" name="activity_id" value="<?= (int)$a['id'] ?>">
          <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> حذف النشاط بالكامل</button>
        </form>

        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endfor; ?>
</div>

</div>
</div>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layouts/footer.php'; ?>
