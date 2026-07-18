<?php
/*
=====================================================================
LMS - التقارير (معلم)
استخراج تقارير: عرض على الشاشة | تحميل Excel | تحميل PDF
أنواع التقارير:
  1) progress    : تقرير تقدم الطلاب في مقرر (إنجاز، نجوم، شارات، وقت)
  2) grades      : تقرير الدرجات التفصيلي (أفضل نتيجة لكل نشاط لكل طالب)
  3) leaderboard : تقرير لوحة الصدارة العامة
- PDF عبر مكتبة mpdf الموجودة في المشروع (vendor/autoload.php)
- Excel عبر جدول HTML بترويسة ‎.xls (يعمل مباشرة على Hostinger بدون مكتبات)
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';

lms_require_role(2);

$teacher = $lms->getTeacherByUserId((int)$_SESSION['user_id']);
if (!$teacher) exit('Teacher Not Found');

$teacherId = (int)$teacher['id'];

/* ==================== مقررات المعلم ==================== */
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

/* ==================== مدخلات التقرير ==================== */
$reportType = (string)($_GET['report'] ?? '');
$format     = (string)($_GET['format'] ?? 'view'); // view | excel | pdf
$courseId   = (int)($_GET['course_id'] ?? 0);

$allowedReports = ['progress', 'grades', 'leaderboard'];
$allowedFormats = ['view', 'excel', 'pdf'];

if (!in_array($reportType, $allowedReports, true)) $reportType = '';
if (!in_array($format, $allowedFormats, true))     $format = 'view';

// حماية: المقرر يجب أن يكون من مقررات المعلم فقط
if ($courseId && !in_array($courseId, $courseIds, true)) {
    $courseId = 0;
}

$courseName = '';
if ($courseId) {
    foreach ($courses as $c) {
        if ((int)$c['id'] === $courseId) { $courseName = $c['course_name']; break; }
    }
}

/*
====================================
جلب بيانات التقرير المطلوب
تُعيد: [العنوان، رؤوس الأعمدة، الصفوف]
====================================
*/
function lms_report_data(PDO $db, string $reportType, int $courseId, string $courseName, int $teacherId): array
{
    $title   = '';
    $headers = [];
    $rows    = [];

    if ($reportType === 'progress' && $courseId) {

        $title   = 'تقرير تقدم الطلاب - مقرر: ' . $courseName;
        $headers = ['#', 'الطالب', 'الرقم الأكاديمي', 'الصف',
                    'الدروس المكتملة', 'نسبة الإنجاز %', 'متوسط الدرجات %',
                    'النجوم', 'الشارات', 'وقت التعلم', 'آخر درس'];

        $stmt = $db->prepare("
            SELECT DISTINCT s.id, s.student_number, u.full_name, cls.class_name,
                   COALESCE(sp.completed_lessons, 0)  AS completed_lessons,
                   COALESCE(sp.progress_percent, 0)   AS progress_percent,
                   COALESCE(sp.avg_grade, 0)          AS avg_grade,
                   COALESCE(sp.total_time_seconds, 0) AS total_time_seconds,
                   (SELECT COUNT(*) FROM lms_stars st
                     WHERE st.student_id = s.id AND st.course_id = ?)    AS stars,
                   (SELECT COUNT(*) FROM lms_student_badges sb
                     WHERE sb.student_id = s.id)                          AS badges,
                   (SELECT l2.title FROM lms_lessons l2
                     WHERE l2.id = sp.last_lesson_id)                     AS last_lesson_title
            FROM course_assignments ca
            INNER JOIN students s ON s.class_id = ca.class_id
            INNER JOIN users u ON s.user_id = u.id
            LEFT JOIN classes cls ON s.class_id = cls.id
            LEFT JOIN lms_student_progress sp
                   ON sp.student_id = s.id AND sp.course_id = ca.course_id
            WHERE ca.course_id = ? AND ca.teacher_id = ?
            ORDER BY progress_percent DESC, avg_grade DESC, u.full_name
        ");
        $stmt->execute([$courseId, $courseId, $teacherId]);

        $i = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $rows[] = [
                ++$i,
                $r['full_name'],
                $r['student_number'] ?? '—',
                $r['class_name'] ?? '—',
                (int)$r['completed_lessons'],
                round((float)$r['progress_percent'], 1),
                round((float)$r['avg_grade'], 1),
                (int)$r['stars'],
                (int)$r['badges'],
                lms_format_seconds((int)$r['total_time_seconds']),
                $r['last_lesson_title'] ?? 'لم يبدأ بعد',
            ];
        }

    } elseif ($reportType === 'grades' && $courseId) {

        $title   = 'تقرير الدرجات التفصيلي - مقرر: ' . $courseName;
        $headers = ['#', 'الطالب', 'الرقم الأكاديمي', 'الدرس', 'النشاط',
                    'النوع', 'أفضل درجة %', 'عدد المحاولات', 'الحالة'];

        $typeLabels = [
            'mcq'          => 'اختيار من متعدد',
            'true_false'   => 'صح أو خطأ',
            'ordering'     => 'ترتيب',
            'matching'     => 'توصيل',
            'short_answer' => 'سؤال قصير',
            'project'      => 'مشروع',
        ];

        $stmt = $db->prepare("
            SELECT u.full_name, s.student_number,
                   l.title AS lesson_title, l.lesson_order,
                   a.title AS activity_title, a.activity_type, a.activity_order,
                   MAX(att.score)              AS best_score,
                   COUNT(att.id)               AS attempts,
                   MAX(att.is_passed)          AS is_passed
            FROM lms_student_activity_attempts att
            INNER JOIN lms_activities a ON att.activity_id = a.id
            INNER JOIN lms_lessons l ON a.lesson_id = l.id
            INNER JOIN students s ON att.student_id = s.id
            INNER JOIN users u ON s.user_id = u.id
            WHERE l.course_id = ? AND l.teacher_id = ?
            GROUP BY att.student_id, att.activity_id,
                     u.full_name, s.student_number,
                     l.title, l.lesson_order,
                     a.title, a.activity_type, a.activity_order
            ORDER BY u.full_name, l.lesson_order, a.activity_order
        ");
        $stmt->execute([$courseId, $teacherId]);

        $i = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $rows[] = [
                ++$i,
                $r['full_name'],
                $r['student_number'] ?? '—',
                $r['lesson_title'],
                $r['activity_title'],
                $typeLabels[$r['activity_type']] ?? $r['activity_type'],
                round((float)$r['best_score'], 1),
                (int)$r['attempts'],
                ((int)$r['is_passed'] === 1) ? 'ناجح' : 'لم يجتز',
            ];
        }

    } elseif ($reportType === 'leaderboard') {

        $title   = 'تقرير لوحة الصدارة العامة';
        $headers = ['الترتيب', 'الطالب', 'الرقم الأكاديمي', 'الصف',
                    'النجوم', 'الشارات', 'الدروس المكتملة',
                    'نسبة الإنجاز %', 'متوسط الدرجات %', 'وقت التعلم'];

        $stmt = $db->prepare("
            SELECT lb.*, u.full_name, s.student_number, cls.class_name
            FROM lms_leaderboard lb
            INNER JOIN students s ON lb.student_id = s.id
            INNER JOIN users u ON s.user_id = u.id
            LEFT JOIN classes cls ON s.class_id = cls.id
            ORDER BY lb.rank_position ASC
            LIMIT 100
        ");
        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $rows[] = [
                (int)$r['rank_position'],
                $r['full_name'],
                $r['student_number'] ?? '—',
                $r['class_name'] ?? '—',
                (int)$r['total_stars'],
                (int)$r['total_badges'],
                (int)$r['completed_lessons'],
                round((float)$r['progress_percent'], 1),
                round((float)$r['avg_grade'], 1),
                lms_format_seconds((int)$r['total_time_seconds']),
            ];
        }
    }

    return [$title, $headers, $rows];
}

/*
====================================
بناء جدول HTML مشترك (للعرض / Excel / PDF)
====================================
*/
function lms_report_table_html(array $headers, array $rows, bool $forExport = false): string
{
    $border = $forExport ? ' border="1" cellspacing="0" cellpadding="5"' : '';
    $class  = $forExport ? '' : ' class="table table-bordered table-hover align-middle mb-0"';

    $html = '<table' . $border . $class . ' style="border-collapse:collapse;width:100%;">';
    $html .= '<thead><tr style="background:#f1f3f5;">';
    foreach ($headers as $h) {
        $html .= '<th style="text-align:center;">' . e($h) . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    if (!$rows) {
        $html .= '<tr><td colspan="' . count($headers) . '" style="text-align:center;">لا توجد بيانات</td></tr>';
    }

    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td style="text-align:center;">' . e((string)$cell) . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}

/* ==================== تنفيذ التصدير (قبل أي إخراج HTML) ==================== */
if ($reportType !== '' && in_array($format, ['excel', 'pdf'], true)) {

    [$title, $headers, $rows] = lms_report_data($db, $reportType, $courseId, $courseName, $teacherId);

    if ($title === '') {
        // نوع تقرير يتطلب اختيار مقرر ولم يُحدد
        header('Location: reports.php');
        exit;
    }

    $fileBase = 'lms_' . $reportType . '_' . date('Y-m-d');

    // سجل التدقيق
    $lms->log((int)$_SESSION['user_id'], 'export_report',
        "report=$reportType format=$format course=$courseId");

    /* ---------- Excel ---------- */
    if ($format === 'excel') {

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fileBase . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // BOM لدعم العربية في Excel
        echo "\xEF\xBB\xBF";
        echo '<html dir="rtl"><head><meta charset="UTF-8"></head><body>';
        echo '<h3 style="text-align:center;">' . e($title) . '</h3>';
        echo '<p style="text-align:center;">تاريخ الاستخراج: ' . date('d/m/Y H:i') . '</p>';
        echo lms_report_table_html($headers, $rows, true);
        echo '</body></html>';
        exit;
    }

    /* ---------- PDF (mpdf) ---------- */
    if ($format === 'pdf') {

        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

        $mpdf = new \Mpdf\Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'A4-L', // عرضي لاستيعاب الأعمدة
            'default_font'     => 'xbriyaz',
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'margin_top'       => 12,
            'margin_bottom'    => 12,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle($title);

        $html = '
        <html dir="rtl">
        <head><meta charset="UTF-8">
        <style>
            body { direction: rtl; text-align: right; font-family: xbriyaz; font-size: 11pt; }
            h2   { text-align: center; margin-bottom: 2px; }
            .sub { text-align: center; color: #666; font-size: 9pt; margin-bottom: 12px; }
            table { border-collapse: collapse; width: 100%; font-size: 9.5pt; }
            th, td { border: 1px solid #999; padding: 5px; text-align: center; }
            th { background: #f1f3f5; font-weight: bold; }
        </style>
        </head>
        <body>
            <h2>' . e($title) . '</h2>
            <div class="sub">
                المعلم: ' . e($teacher['full_name']) . '
                | تاريخ الاستخراج: ' . date('d/m/Y H:i') . '
                | عدد السجلات: ' . count($rows) . '
            </div>
            ' . lms_report_table_html($headers, $rows, true) . '
        </body>
        </html>';

        $mpdf->WriteHTML($html);
        $mpdf->Output($fileBase . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}

/* ==================== عرض على الشاشة ==================== */
$viewTitle = '';
$viewHeaders = [];
$viewRows = [];

if ($reportType !== '' && $format === 'view') {
    [$viewTitle, $viewHeaders, $viewRows] = lms_report_data($db, $reportType, $courseId, $courseName, $teacherId);
}

include dirname(__DIR__, 2) . '/app/views/layouts/header.php';
?>

<div class="container-fluid px-0">
<div class="row flex-lg-row-reverse g-0">

<?php include dirname(__DIR__, 2) . '/app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-spreadsheet text-info"></i> التقارير</h4>
<p class="text-muted small mb-4">استخراج تقارير Excel وPDF أو عرضها مباشرة على الشاشة</p>

<!-- نموذج اختيار التقرير -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <form method="get" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label fw-bold">نوع التقرير *</label>
        <select name="report" class="form-select" required id="reportType">
          <option value="">اختر التقرير...</option>
          <option value="progress"    <?= $reportType === 'progress' ? 'selected' : '' ?>>📈 تقرير تقدم الطلاب (حسب المقرر)</option>
          <option value="grades"      <?= $reportType === 'grades' ? 'selected' : '' ?>>📊 تقرير الدرجات التفصيلي (حسب المقرر)</option>
          <option value="leaderboard" <?= $reportType === 'leaderboard' ? 'selected' : '' ?>>🏆 تقرير لوحة الصدارة العامة</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-bold">المقرر</label>
        <select name="course_id" class="form-select" id="courseSelect">
          <option value="">اختر المقرر...</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $courseId === (int)$c['id'] ? 'selected' : '' ?>>
              <?= e($c['course_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <div class="btn-group w-100">
          <button name="format" value="view"  class="btn btn-primary">
            <i class="bi bi-eye"></i> عرض
          </button>
          <button name="format" value="excel" class="btn btn-success">
            <i class="bi bi-file-earmark-excel"></i> Excel
          </button>
          <button name="format" value="pdf"   class="btn btn-danger">
            <i class="bi bi-file-earmark-pdf"></i> PDF
          </button>
        </div>
      </div>
    </form>
    <small class="text-muted d-block mt-2">
      * تقريرا التقدم والدرجات يتطلبان اختيار المقرر - تقرير لوحة الصدارة لا يحتاج مقرراً
    </small>
  </div>
</div>

<?php if ($reportType !== '' && $viewTitle === ''): ?>
<div class="alert alert-warning">
  <i class="bi bi-exclamation-triangle"></i> يرجى اختيار المقرر لهذا النوع من التقارير
</div>
<?php endif; ?>

<!-- نتيجة العرض على الشاشة -->
<?php if ($viewTitle !== ''): ?>
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span class="fw-bold"><i class="bi bi-table"></i> <?= e($viewTitle) ?></span>
    <span class="badge bg-secondary">عدد السجلات: <?= count($viewRows) ?></span>
  </div>
  <div class="table-responsive">
    <?= lms_report_table_html($viewHeaders, $viewRows, false) ?>
  </div>
</div>
<?php endif; ?>

</div>
</div>
</div>

<script>
/* تفعيل/تعطيل اختيار المقرر حسب نوع التقرير */
(function () {
    const reportType   = document.getElementById('reportType');
    const courseSelect = document.getElementById('courseSelect');
    function toggle() {
        const needCourse = (reportType.value === 'progress' || reportType.value === 'grades');
        courseSelect.disabled = !needCourse;
        courseSelect.required = needCourse;
    }
    reportType.addEventListener('change', toggle);
    toggle();
})();
</script>

<?php include dirname(__DIR__, 2) . '/app/views/layouts/footer.php'; ?>
