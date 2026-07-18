<?php
/*
=====================================================================
LMS - إدارة الدروس (معلم)
إنشاء / تعديل / حذف + رفع (صورة، فيديو، PDF، PPT، ملفات إضافية)
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';

lms_require_role(2);

$teacher = $lms->getTeacherByUserId((int)$_SESSION['user_id']);
if (!$teacher) exit('Teacher Not Found');

$teacherId = (int)$teacher['id'];
$flash = null;

// مقررات المعلم
$stmt = $db->prepare("
    SELECT DISTINCT c.id, c.course_name
    FROM course_assignments ca
    INNER JOIN courses c ON ca.course_id = c.id
    WHERE ca.teacher_id = ?
    ORDER BY c.course_name
");
$stmt->execute([$teacherId]);
$courses   = $stmt->fetchAll(PDO::FETCH_ASSOC);
$courseIds = array_map('intval', array_column($courses, 'id'));

/* ==================== معالجة النماذج ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    lms_csrf_check();

    try {
        $action = $_POST['action'] ?? '';

        /* ----- حفظ درس (إنشاء/تعديل) ----- */
        if ($action === 'save_lesson') {

            $lessonId  = (int)($_POST['lesson_id'] ?? 0);
            $courseId  = (int)($_POST['course_id'] ?? 0);
            $title     = trim((string)($_POST['title'] ?? ''));
            $passGrade = min(100, max(1, (float)($_POST['pass_grade'] ?? 60)));

            // Input Validation
            if ($title === '' || !in_array($courseId, $courseIds, true)) {
                throw new Exception('تحقق من عنوان الدرس والمقرر');
            }

            // رفع الملفات
            $image = lms_upload_file($_FILES['image'] ?? [], 'images', ['jpg','jpeg','png','webp','gif'], 5);
            $video = lms_upload_file($_FILES['video_file'] ?? [], 'videos', ['mp4','webm','mov'], 200);
            $pdf   = lms_upload_file($_FILES['pdf_file'] ?? [], 'pdf', ['pdf'], 50);
            $ppt   = lms_upload_file($_FILES['ppt_file'] ?? [], 'ppt', ['ppt','pptx'], 50);

            $videoUrl = trim((string)($_POST['video_url'] ?? ''));
            if ($video) $videoUrl = $video; // الملف المرفوع له الأولوية

            $data = [
                'course_id'      => $courseId,
                'lesson_order'   => (int)($_POST['lesson_order'] ?? 1),
                'title'          => $title,
                'description'    => trim((string)($_POST['description'] ?? '')),
                'objectives'     => trim((string)($_POST['objectives'] ?? '')),
                'outcomes'       => trim((string)($_POST['outcomes'] ?? '')),
                'external_links' => trim((string)($_POST['external_links'] ?? '')),
                'references_text'=> trim((string)($_POST['references_text'] ?? '')),
                'pass_grade'     => $passGrade,
                'is_published'   => isset($_POST['is_published']) ? 1 : 0,
            ];

            if ($lessonId > 0) {
                // تعديل (مع التأكد من ملكية المعلم)
                $sql = "UPDATE lms_lessons SET
                        course_id=:course_id, lesson_order=:lesson_order, title=:title,
                        description=:description, objectives=:objectives, outcomes=:outcomes,
                        external_links=:external_links, references_text=:references_text,
                        pass_grade=:pass_grade, is_published=:is_published";
                if ($image)    { $sql .= ", image=:image";         $data['image'] = $image; }
                if ($videoUrl) { $sql .= ", video_url=:video_url"; $data['video_url'] = $videoUrl; }
                if ($pdf)      { $sql .= ", pdf_file=:pdf_file";   $data['pdf_file'] = $pdf; }
                if ($ppt)      { $sql .= ", ppt_file=:ppt_file";   $data['ppt_file'] = $ppt; }
                $sql .= " WHERE id=:id AND teacher_id=:tid";
                $data['id']  = $lessonId;
                $data['tid'] = $teacherId;

                $db->prepare($sql)->execute($data);
                $lms->log((int)$_SESSION['user_id'], 'update_lesson', 'lesson_id=' . $lessonId);
                $flash = ['success', 'تم تحديث الدرس بنجاح'];
            } else {
                $data['teacher_id'] = $teacherId;
                $data['image']      = $image;
                $data['video_url']  = $videoUrl ?: null;
                $data['pdf_file']   = $pdf;
                $data['ppt_file']   = $ppt;

                $db->prepare("
                    INSERT INTO lms_lessons
                        (course_id, teacher_id, lesson_order, title, description,
                         objectives, outcomes, image, video_url, pdf_file, ppt_file,
                         external_links, references_text, pass_grade, is_published)
                    VALUES
                        (:course_id, :teacher_id, :lesson_order, :title, :description,
                         :objectives, :outcomes, :image, :video_url, :pdf_file, :ppt_file,
                         :external_links, :references_text, :pass_grade, :is_published)
                ")->execute($data);
                $lessonId = (int)$db->lastInsertId();
                $lms->log((int)$_SESSION['user_id'], 'create_lesson', 'lesson_id=' . $lessonId);
                $flash = ['success', 'تم إنشاء الدرس - أضف الآن أنشطته الخمسة'];
            }

            // ملف إضافي مرفق مع الحفظ
            if (!empty($_FILES['extra_file']['name'])) {
                $extraPath = lms_upload_file($_FILES['extra_file'], 'files',
                    ['pdf','doc','docx','xls','xlsx','ppt','pptx','zip','rar','png','jpg','jpeg','txt'], 50);
                if ($extraPath) {
                    $db->prepare("
                        INSERT INTO lms_lesson_files (lesson_id, file_title, file_path, file_type)
                        VALUES (?, ?, ?, ?)
                    ")->execute([
                        $lessonId,
                        trim((string)($_POST['extra_file_title'] ?? 'ملف إضافي')) ?: 'ملف إضافي',
                        $extraPath,
                        pathinfo($extraPath, PATHINFO_EXTENSION)
                    ]);
                }
            }
        }

        /* ----- حذف درس ----- */
        if ($action === 'delete_lesson') {
            $lessonId = (int)($_POST['lesson_id'] ?? 0);
            $db->prepare("DELETE FROM lms_lessons WHERE id = ? AND teacher_id = ?")
               ->execute([$lessonId, $teacherId]);
            $lms->log((int)$_SESSION['user_id'], 'delete_lesson', 'lesson_id=' . $lessonId);
            $flash = ['success', 'تم حذف الدرس وجميع أنشطته'];
        }

    } catch (Exception $ex) {
        $flash = ['danger', $ex->getMessage()];
    }
}

/* ==================== درس للتعديل ==================== */
$editLesson = null;
if (!empty($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM lms_lessons WHERE id = ? AND teacher_id = ?");
    $stmt->execute([(int)$_GET['edit'], $teacherId]);
    $editLesson = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ==================== قائمة الدروس ==================== */
$lessons = [];
if ($courseIds) {
    $in = implode(',', $courseIds);

    /*
    🔧 التعديل: أُزيل شرط l.teacher_id = ? من هنا
    المشاهدة الآن مبنية على "هل المقرر مسند لي؟" (course_id IN $in)
    بدل "هل أنا من أنشأ الدرس؟" — لأن عدة معلمين قد يشتركون
    بتدريس نفس المقرر لصفوف مختلفة (كما في تقن107)

    التعديل والحذف يبقيان محميين بـ teacher_id بصرامة في أماكنهما
    (انظر save_lesson وdelete_lesson بالأسفل) — لا نغيّرهما
    */
    $stmt = $db->prepare("
        SELECT l.*, c.course_name, u.full_name AS created_by,
               (SELECT COUNT(*) FROM lms_activities WHERE lesson_id = l.id) AS activities_count
        FROM lms_lessons l
        INNER JOIN courses c ON l.course_id = c.id
        LEFT JOIN teachers t ON l.teacher_id = t.id
        LEFT JOIN users u ON t.user_id = u.id
        WHERE l.course_id IN ($in)
        ORDER BY c.course_name, l.lesson_order
    ");
    $stmt->execute();
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include dirname(__DIR__, 2) . '/app/views/layouts/header.php';
?>

<div class="container-fluid px-0">
<div class="row flex-lg-row-reverse g-0">

<?php include dirname(__DIR__, 2) . '/app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<h4 class="fw-bold mb-4"><i class="bi bi-collection-play text-primary"></i> إدارة دروس المنصة التفاعلية</h4>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash[0] ?> alert-dismissible fade show">
  <?= e($flash[1]) ?>
  <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- نموذج إنشاء/تعديل -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-bold">
    <?= $editLesson ? '✏️ تعديل درس: ' . e($editLesson['title']) : '➕ إنشاء درس جديد' ?>
  </div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data">
      <?= lms_csrf_field() ?>
      <input type="hidden" name="action" value="save_lesson">
      <input type="hidden" name="lesson_id" value="<?= (int)($editLesson['id'] ?? 0) ?>">

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label fw-bold">المقرر *</label>
          <select name="course_id" class="form-select" required>
            <option value="">اختر المقرر...</option>
            <?php foreach ($courses as $c): ?>
              <option value="<?= (int)$c['id'] ?>"
                <?= ((int)($editLesson['course_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                <?= e($c['course_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label fw-bold">عنوان الدرس *</label>
          <input type="text" name="title" class="form-control" required maxlength="255"
                 value="<?= e($editLesson['title'] ?? '') ?>">
        </div>
        <div class="col-md-1">
          <label class="form-label fw-bold">الترتيب</label>
          <input type="number" name="lesson_order" class="form-control" min="1"
                 value="<?= (int)($editLesson['lesson_order'] ?? (count($lessons) + 1)) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label fw-bold">درجة النجاح %</label>
          <input type="number" name="pass_grade" class="form-control" min="1" max="100"
                 value="<?= e($editLesson['pass_grade'] ?? 60) ?>">
        </div>

        <div class="col-12">
          <label class="form-label fw-bold">وصف الدرس</label>
          <textarea name="description" class="form-control" rows="2"><?= e($editLesson['description'] ?? '') ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-bold">أهداف التعلم <small class="text-muted">(سطر لكل هدف)</small></label>
          <textarea name="objectives" class="form-control" rows="3"><?= e($editLesson['objectives'] ?? '') ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-bold">مخرجات التعلم <small class="text-muted">(سطر لكل مخرج)</small></label>
          <textarea name="outcomes" class="form-control" rows="3"><?= e($editLesson['outcomes'] ?? '') ?></textarea>
        </div>

        <div class="col-md-3">
          <label class="form-label fw-bold">صورة الدرس</label>
          <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">رابط فيديو (YouTube)</label>
          <input type="url" name="video_url" class="form-control" dir="ltr"
                 placeholder="https://youtube.com/..."
                 value="<?= e(str_starts_with((string)($editLesson['video_url'] ?? ''), 'http') ? $editLesson['video_url'] : '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">أو رفع فيديو</label>
          <input type="file" name="video_file" class="form-control" accept=".mp4,.webm,.mov">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">ملف PDF</label>
          <input type="file" name="pdf_file" class="form-control" accept=".pdf">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">عرض PowerPoint</label>
          <input type="file" name="ppt_file" class="form-control" accept=".ppt,.pptx">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">ملف إضافي</label>
          <input type="file" name="extra_file" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">عنوان الملف الإضافي</label>
          <input type="text" name="extra_file_title" class="form-control" placeholder="مثال: ورقة عمل">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">روابط خارجية <small class="text-muted">(سطر لكل رابط)</small></label>
          <textarea name="external_links" class="form-control" rows="2" dir="ltr"><?= e($editLesson['external_links'] ?? '') ?></textarea>
        </div>

        <div class="col-md-9">
          <label class="form-label fw-bold">المراجع</label>
          <textarea name="references_text" class="form-control" rows="2"><?= e($editLesson['references_text'] ?? '') ?></textarea>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_published" id="pub"
                   <?= (int)($editLesson['is_published'] ?? 1) === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="pub">منشور للطلاب</label>
          </div>
        </div>

        <div class="col-12">
          <button class="btn btn-primary"><i class="bi bi-save"></i> حفظ الدرس</button>
          <?php if ($editLesson): ?>
            <a href="lessons.php" class="btn btn-outline-secondary">إلغاء التعديل</a>
          <?php endif; ?>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- جدول الدروس -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-bold">دروسي (<?= count($lessons) ?>)</div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr><th>#</th><th>الدرس</th><th>المقرر</th><th>الأنشطة</th><th>الحالة</th><th>إجراءات</th></tr>
      </thead>
      <tbody>
        <?php if (!$lessons): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">لا توجد دروس بعد</td></tr>
        <?php endif; ?>
        <?php foreach ($lessons as $l): ?>
        <tr>
          <td><?= (int)$l['lesson_order'] ?></td>
          <td class="fw-bold">
    <?= e($l['title']) ?>
    <br>
    <small class="text-muted fw-normal">
        أنشأه: <?= e($l['created_by'] ?? 'غير معروف'); ?>
    </small>
</td>
          <td><?= e($l['course_name']) ?></td>
          <td>
            <span class="badge bg-<?= (int)$l['activities_count'] >= 5 ? 'success' : 'warning text-dark' ?>">
              <?= (int)$l['activities_count'] ?> / 5
            </span>
          </td>
          <td>
            <?= (int)$l['is_published'] === 1
                ? '<span class="badge bg-success">منشور</span>'
                : '<span class="badge bg-secondary">مسودة</span>' ?>
          </td>
         <td>
    <a href="activities.php?lesson_id=<?= (int)$l['id'] ?>" class="btn btn-sm btn-success"
       title="الأنشطة"><i class="bi bi-pencil-square"></i> الأنشطة</a>

    <?php if ((int)$l['teacher_id'] === $teacherId): ?>

        <a href="lessons.php?edit=<?= (int)$l['id'] ?>" class="btn btn-sm btn-outline-primary"
           title="تعديل"><i class="bi bi-pencil"></i></a>

        <form method="post" class="d-inline"
              onsubmit="return confirm('حذف الدرس وجميع أنشطته ونتائجه؟')">
          <?= lms_csrf_field() ?>
          <input type="hidden" name="action" value="delete_lesson">
          <input type="hidden" name="lesson_id" value="<?= (int)$l['id'] ?>">
          <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="bi bi-trash"></i></button>
        </form>

    <?php else: ?>

        <span class="badge bg-light text-dark border" title="أنشأه معلم آخر — للعرض فقط">
            <i class="bi bi-lock-fill"></i> للعرض فقط
        </span>

    <?php endif; ?>
</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</div>
</div>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layouts/footer.php'; ?>
