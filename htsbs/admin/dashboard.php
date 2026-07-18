<?php
/*
=====================================================================
admin/dashboard.php — لوحة إحصائيات الأدمن
=====================================================================
إعادة بناء كاملة (النسخة السابقة كانت بطاقة واحدة برقم "0" ثابت،
و<html>/<head>/Bootstrap مكررة داخل الصفحة فوق header.php).

يحتوي:
  - 6 بطاقات مؤشرات (KPI) بأرقام حقيقية من قاعدة البيانات
  - رسم خطي: نسبة الحضور آخر 14 يوماً
  - رسم دائري: توزيع الطلاب حسب القسم
  - رسم أعمدة: توزيع الدرجات (فئات)
  - رسم أعمدة أفقي: الصفوف الأكثر عدداً بالطلاب
  - رسم دائري: نشاط النظام حسب الخطورة (7 أيام)
  - آخر 8 أحداث في سجل النشاط (تحديث حي بسيط)
  - عدّادات متحركة (Count-up) عند تحميل الصفحة
=====================================================================
*/

session_start();

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../core/Auth.php';

if (!Auth::check()) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

if ((int)($_SESSION['role_id'] ?? 0) !== 1) {
    die('Access Denied');
}

if (!function_exists('e')) {
    function e($v): string {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

/*
====================================================================
دالة مساعدة: تنفيذ استعلام بأمان — تُرجع قيمة افتراضية عند الفشل
(بعض الجداول مثل lms_* أو system_logs قد لا تكون مستوردة بعد)
====================================================================
*/
function safe_scalar(PDO $db, string $sql, array $params = [], $default = 0)
{
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $v = $stmt->fetchColumn();
        return $v !== false && $v !== null ? $v : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

function safe_rows(PDO $db, string $sql, array $params = []): array
{
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

/* ==================== KPI 1: الطلاب ==================== */
$totalStudents   = (int)safe_scalar($db, "SELECT COUNT(*) FROM students");
$newStudentsWeek = (int)safe_scalar($db, "
    SELECT COUNT(*) FROM students s
    INNER JOIN users u ON s.user_id = u.id
    WHERE u.created_at >= (NOW() - INTERVAL 7 DAY)
");

/* ==================== KPI 2: المعلمون ==================== */
$totalTeachers = (int)safe_scalar($db, "SELECT COUNT(*) FROM teachers");

/* ==================== KPI 3: الصفوف ==================== */
$totalClasses = (int)safe_scalar($db, "SELECT COUNT(*) FROM classes");

/* ==================== KPI 4: المقررات ==================== */
$totalCourses = (int)safe_scalar($db, "SELECT COUNT(*) FROM courses");

/* ==================== KPI 5: نسبة الحضور اليوم وأمس ==================== */
$attendanceToday = safe_rows($db, "
    SELECT status, COUNT(*) AS c
    FROM attendance
    WHERE attendance_date = CURDATE()
    GROUP BY status
");
$presentToday = 0; $totalToday = 0;
foreach ($attendanceToday as $r) {
    $totalToday += (int)$r['c'];
    if ($r['status'] === 'Present') $presentToday = (int)$r['c'];
}
$attendanceRateToday = $totalToday > 0 ? round(($presentToday / $totalToday) * 100, 1) : null;

$attendanceYesterday = safe_rows($db, "
    SELECT status, COUNT(*) AS c
    FROM attendance
    WHERE attendance_date = (CURDATE() - INTERVAL 1 DAY)
    GROUP BY status
");
$presentYesterday = 0; $totalYesterday = 0;
foreach ($attendanceYesterday as $r) {
    $totalYesterday += (int)$r['c'];
    if ($r['status'] === 'Present') $presentYesterday = (int)$r['c'];
}
$attendanceRateYesterday = $totalYesterday > 0 ? round(($presentYesterday / $totalYesterday) * 100, 1) : null;

/* ==================== KPI 6: متوسط المعدل التراكمي ==================== */
$avgGpa = safe_scalar($db, "SELECT AVG(gpa) FROM students WHERE gpa IS NOT NULL", [], null);
$avgGpa = $avgGpa !== null ? round((float)$avgGpa, 2) : null;

/* ==================== الشهادات الصادرة (هذا الشهر) ==================== */
$certsTotal     = (int)safe_scalar($db, "SELECT COUNT(*) FROM certificates");
$certsThisMonth = (int)safe_scalar($db, "
    SELECT COUNT(*) FROM certificates
    WHERE issue_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
");

/* ==================== رسم 1: اتجاه الحضور آخر 14 يوماً ==================== */
$attendanceTrendRaw = safe_rows($db, "
    SELECT attendance_date,
           SUM(status = 'Present') AS present_count,
           COUNT(*) AS total_count
    FROM attendance
    WHERE attendance_date >= (CURDATE() - INTERVAL 13 DAY)
    GROUP BY attendance_date
    ORDER BY attendance_date ASC
");
$trendMap = [];
foreach ($attendanceTrendRaw as $r) {
    $trendMap[$r['attendance_date']] = $r['total_count'] > 0
        ? round(((int)$r['present_count'] / (int)$r['total_count']) * 100, 1)
        : 0;
}
$trendLabels = [];
$trendValues = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $trendLabels[] = date('d/m', strtotime($d));
    $trendValues[] = $trendMap[$d] ?? 0;
}

/* ==================== رسم 2: توزيع الطلاب حسب القسم ==================== */
$deptDistribution = safe_rows($db, "
    SELECT d.department_name, COUNT(s.id) AS student_count
    FROM departments d
    LEFT JOIN students s ON s.department_id = d.id
    GROUP BY d.id, d.department_name
    ORDER BY student_count DESC
");
$deptLabels = array_column($deptDistribution, 'department_name');
$deptValues = array_map('intval', array_column($deptDistribution, 'student_count'));

/* ==================== رسم 3: توزيع الدرجات (فئات) ==================== */
$gradeRows = safe_rows($db, "
    SELECT
        SUM(CASE WHEN (score / max_score * 100) >= 90 THEN 1 ELSE 0 END) AS band_a,
        SUM(CASE WHEN (score / max_score * 100) >= 80 AND (score / max_score * 100) < 90 THEN 1 ELSE 0 END) AS band_b,
        SUM(CASE WHEN (score / max_score * 100) >= 70 AND (score / max_score * 100) < 80 THEN 1 ELSE 0 END) AS band_c,
        SUM(CASE WHEN (score / max_score * 100) >= 60 AND (score / max_score * 100) < 70 THEN 1 ELSE 0 END) AS band_d,
        SUM(CASE WHEN (score / max_score * 100) < 60 THEN 1 ELSE 0 END) AS band_f
    FROM gradebook
    WHERE max_score > 0
");
$gradeBands = $gradeRows[0] ?? ['band_a'=>0,'band_b'=>0,'band_c'=>0,'band_d'=>0,'band_f'=>0];

/* ==================== رسم 4: أكثر 6 صفوف عدداً بالطلاب ==================== */
$classDistribution = safe_rows($db, "
    SELECT c.class_name, COUNT(s.id) AS student_count
    FROM classes c
    LEFT JOIN students s ON s.class_id = c.id
    GROUP BY c.id, c.class_name
    ORDER BY student_count DESC
    LIMIT 6
");
$classLabels = array_column($classDistribution, 'class_name');
$classValues = array_map('intval', array_column($classDistribution, 'student_count'));

/* ==================== رسم 5: نشاط النظام حسب الخطورة (7 أيام) ==================== */
$severityRows = safe_rows($db, "
    SELECT severity, COUNT(*) AS c
    FROM system_logs
    WHERE created_at >= (NOW() - INTERVAL 7 DAY)
    GROUP BY severity
");
$sevMap = ['info' => 0, 'warning' => 0, 'danger' => 0];
foreach ($severityRows as $r) {
    if (isset($sevMap[$r['severity']])) {
        $sevMap[$r['severity']] = (int)$r['c'];
    }
}
$logsAvailable = (bool)safe_rows($db, "SHOW TABLES LIKE 'system_logs'");

/* ==================== آخر 8 أحداث نشاط ==================== */
$recentLogs = safe_rows($db, "
    SELECT l.action, l.module, l.details, l.severity, l.created_at,
           COALESCE(u.full_name, l.user_name, 'زائر') AS user_name
    FROM system_logs l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT 8
");

/* ==================== المستخدمون الجدد هذا الأسبوع (كل الأدوار) ==================== */
$newUsersWeek = (int)safe_scalar($db, "
    SELECT COUNT(*) FROM users WHERE created_at >= (NOW() - INTERVAL 7 DAY)
");

include '../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">

<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="col-md-10 p-4">

<style>
/* ===================== بطاقات المؤشرات ===================== */
.kpi-card {
    border: none;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 2px 14px rgba(15, 23, 42, .06);
    padding: 1.25rem 1.4rem;
    position: relative;
    overflow: hidden;
    height: 100%;
    transition: transform .2s ease, box-shadow .2s ease;
}
.kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 24px rgba(15, 23, 42, .10);
}
.kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    color: #fff;
    flex-shrink: 0;
}
.kpi-value {
    font-size: 1.9rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
}
.kpi-label {
    font-size: .82rem;
    color: #64748b;
    font-weight: 600;
}
.kpi-trend {
    font-size: .74rem;
    font-weight: 700;
    padding: .15rem .5rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    gap: .2rem;
}
.kpi-trend.up   { background: #dcfce7; color: #15803d; }
.kpi-trend.down { background: #fee2e2; color: #b91c1c; }
.kpi-trend.flat { background: #f1f5f9; color: #64748b; }

.grad-indigo { background: linear-gradient(135deg,#6366f1,#4338ca); }
.grad-teal   { background: linear-gradient(135deg,#14b8a6,#0f766e); }
.grad-amber  { background: linear-gradient(135deg,#f59e0b,#b45309); }
.grad-rose   { background: linear-gradient(135deg,#f43f5e,#be123c); }
.grad-sky    { background: linear-gradient(135deg,#38bdf8,#0369a1); }
.grad-violet { background: linear-gradient(135deg,#a78bfa,#6d28d9); }

/* ===================== بطاقات الرسوم ===================== */
.chart-card {
    border: none;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 2px 14px rgba(15, 23, 42, .06);
    padding: 1.25rem 1.4rem;
    height: 100%;
}
.chart-card h6 {
    font-weight: 800;
    color: #1e293b;
    margin-bottom: .1rem;
}
.chart-card .subtitle {
    font-size: .78rem;
    color: #94a3b8;
    margin-bottom: 1rem;
}

/* ===================== خط الزمن للنشاط ===================== */
.activity-item {
    display: flex;
    gap: .75rem;
    padding: .6rem 0;
    border-bottom: 1px dashed #e2e8f0;
}
.activity-item:last-child { border-bottom: none; }
.activity-dot {
    width: 9px; height: 9px;
    border-radius: 50%;
    margin-top: .4rem;
    flex-shrink: 0;
}
.activity-dot.info    { background: #94a3b8; }
.activity-dot.warning { background: #f59e0b; }
.activity-dot.danger  { background: #e11d48; }

/* ===================== ترحيب ===================== */
.welcome-strip {
    border-radius: 18px;
    background: linear-gradient(120deg,#1e1b4b,#312e81 55%,#4338ca);
    color: #fff;
    padding: 1.5rem 1.75rem;
    margin-bottom: 1.25rem;
    position: relative;
    overflow: hidden;
}
.welcome-strip::after {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 90% -10%, rgba(255,255,255,.15), transparent 60%);
}
</style>

<!-- ==================== شريط الترحيب ==================== -->
<div class="welcome-strip d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <div class="fs-5 fw-bold">
            مرحباً، <?= e($_SESSION['name'] ?? 'المسؤول'); ?> 👋
        </div>
        <div class="small opacity-75" id="liveClock">—</div>
    </div>
    <div class="text-end">
        <div class="small opacity-75">مستخدمون جدد هذا الأسبوع</div>
        <div class="fs-4 fw-bold"><?= number_format($newUsersWeek); ?></div>
    </div>
</div>

<!-- ==================== بطاقات المؤشرات ==================== -->
<div class="row g-3 mb-4">

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="kpi-card">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="kpi-icon grad-indigo"><i class="bi bi-people-fill"></i></div>
            </div>
            <div class="kpi-value" data-countup="<?= $totalStudents; ?>">0</div>
            <div class="kpi-label">إجمالي الطلاب</div>
            <?php if ($newStudentsWeek > 0): ?>
                <span class="kpi-trend up mt-2"><i class="bi bi-arrow-up-short"></i> +<?= $newStudentsWeek; ?> هذا الأسبوع</span>
            <?php else: ?>
                <span class="kpi-trend flat mt-2">لا جدد هذا الأسبوع</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="kpi-card">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="kpi-icon grad-teal"><i class="bi bi-person-workspace"></i></div>
            </div>
            <div class="kpi-value" data-countup="<?= $totalTeachers; ?>">0</div>
            <div class="kpi-label">المعلمون</div>
            <span class="kpi-trend flat mt-2">عبر <?= $totalClasses; ?> صفاً</span>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="kpi-card">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="kpi-icon grad-sky"><i class="bi bi-book-fill"></i></div>
            </div>
            <div class="kpi-value" data-countup="<?= $totalCourses; ?>">0</div>
            <div class="kpi-label">المقررات الدراسية</div>
            <span class="kpi-trend flat mt-2"><?= $totalClasses; ?> صف دراسي</span>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="kpi-card">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="kpi-icon grad-amber"><i class="bi bi-calendar-check-fill"></i></div>
            </div>
            <?php if ($attendanceRateToday !== null): ?>
                <div class="kpi-value" data-countup="<?= $attendanceRateToday; ?>" data-suffix="%">0%</div>
                <div class="kpi-label">نسبة الحضور اليوم</div>
                <?php
                    if ($attendanceRateYesterday !== null) {
                        $diff = round($attendanceRateToday - $attendanceRateYesterday, 1);
                        $cls  = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'flat');
                        $icon = $diff > 0 ? 'bi-arrow-up-short' : ($diff < 0 ? 'bi-arrow-down-short' : 'bi-dash');
                        echo "<span class=\"kpi-trend $cls mt-2\"><i class=\"bi $icon\"></i> " . abs($diff) . "% عن الأمس</span>";
                    } else {
                        echo '<span class="kpi-trend flat mt-2">لا بيانات أمس</span>';
                    }
                ?>
            <?php else: ?>
                <div class="kpi-value">—</div>
                <div class="kpi-label">لم يُسجَّل حضور اليوم</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="kpi-card">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="kpi-icon grad-violet"><i class="bi bi-graph-up-arrow"></i></div>
            </div>
            <?php if ($avgGpa !== null): ?>
                <div class="kpi-value" data-countup="<?= $avgGpa; ?>" data-decimals="2">0</div>
                <div class="kpi-label">متوسط المعدل التراكمي</div>
            <?php else: ?>
                <div class="kpi-value">—</div>
                <div class="kpi-label">لا بيانات معدلات</div>
            <?php endif; ?>
            <span class="kpi-trend flat mt-2">من أصل 100</span>
        </div>
    </div>

    <div class="col-6 col-lg-4 col-xl-2">
        <div class="kpi-card">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="kpi-icon grad-rose"><i class="bi bi-award-fill"></i></div>
            </div>
            <div class="kpi-value" data-countup="<?= $certsTotal; ?>">0</div>
            <div class="kpi-label">الشهادات الصادرة</div>
            <?php if ($certsThisMonth > 0): ?>
                <span class="kpi-trend up mt-2"><i class="bi bi-arrow-up-short"></i> +<?= $certsThisMonth; ?> هذا الشهر</span>
            <?php else: ?>
                <span class="kpi-trend flat mt-2">لا إصدار هذا الشهر</span>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ==================== الرسوم البيانية - الصف الأول ==================== -->
<div class="row g-3 mb-3">

    <div class="col-lg-8">
        <div class="chart-card">
            <h6><i class="bi bi-graph-up text-primary"></i> اتجاه نسبة الحضور — آخر 14 يوماً</h6>
            <p class="subtitle">النسبة المئوية للحضور الفعلي من إجمالي السجلات المسجَّلة يومياً</p>
            <canvas id="attendanceTrendChart" height="90"></canvas>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="chart-card">
            <h6><i class="bi bi-pie-chart-fill text-info"></i> توزيع الطلاب حسب القسم</h6>
            <p class="subtitle">إجمالي <?= number_format($totalStudents); ?> طالباً</p>
            <canvas id="deptChart" height="220"></canvas>
        </div>
    </div>

</div>

<!-- ==================== الرسوم البيانية - الصف الثاني ==================== -->
<div class="row g-3 mb-3">

    <div class="col-lg-5">
        <div class="chart-card">
            <h6><i class="bi bi-bar-chart-fill text-success"></i> توزيع الدرجات</h6>
            <p class="subtitle">حسب فئات النسبة المئوية لكل تقييمات جدول الدرجات</p>
            <canvas id="gradeChart" height="200"></canvas>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="chart-card">
            <h6><i class="bi bi-people-fill text-warning"></i> الصفوف الأكثر عدداً</h6>
            <p class="subtitle">أعلى 6 صفوف من حيث عدد الطلاب المسجَّلين</p>
            <canvas id="classChart" height="200"></canvas>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="chart-card">
            <h6><i class="bi bi-shield-fill-exclamation text-danger"></i> نشاط النظام</h6>
            <p class="subtitle">حسب الخطورة — آخر 7 أيام</p>
            <?php if ($logsAvailable): ?>
                <canvas id="severityChart" height="200"></canvas>
            <?php else: ?>
                <div class="alert alert-light border small text-muted text-center mt-3">
                    سجل النشاط غير مفعّل بعد
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ==================== أحدث نشاط ==================== -->
<div class="row g-3">
    <div class="col-12">
        <div class="chart-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0"><i class="bi bi-clock-history text-secondary"></i> أحدث الأحداث</h6>
                <?php if ($logsAvailable): ?>
                    <a href="logs/index.php" class="small text-decoration-none">عرض السجل الكامل ←</a>
                <?php endif; ?>
            </div>

            <?php if (!$recentLogs): ?>
                <p class="text-muted small text-center py-3 mb-0">لا توجد أحداث مسجَّلة بعد</p>
            <?php else: ?>
                <?php foreach ($recentLogs as $log): ?>
                <div class="activity-item">
                    <span class="activity-dot <?= e($log['severity']); ?>"></span>
                    <div class="flex-grow-1">
                        <span class="fw-bold small"><?= e($log['user_name']); ?></span>
                        <span class="small text-muted"> — <?= e($log['details'] ?? $log['action']); ?></span>
                    </div>
                    <div class="small text-muted text-nowrap">
                        <?= e(date('d/m H:i', strtotime((string)$log['created_at']))); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>
</div>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>

/* ==================== الساعة الحية ==================== */
function updateClock() {
    const el = document.getElementById('liveClock');
    if (!el) return;
    const now = new Date();
    const days = ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
    const day = days[now.getDay()];
    const time = now.toLocaleTimeString('ar-BH', { hour: '2-digit', minute: '2-digit' });
    el.textContent = day + ' — ' + time;
}
updateClock();
setInterval(updateClock, 30000);

/* ==================== عدّادات متحركة ==================== */
document.querySelectorAll('[data-countup]').forEach(function (el) {
    const target    = parseFloat(el.dataset.countup) || 0;
    const decimals  = parseInt(el.dataset.decimals || '0', 10);
    const suffix    = el.dataset.suffix || '';
    const duration  = 900;
    const start     = performance.now();

    function tick(now) {
        const progress = Math.min((now - start) / duration, 1);
        const eased    = 1 - Math.pow(1 - progress, 3);
        const value    = target * eased;
        el.textContent = value.toFixed(decimals) + suffix;
        if (progress < 1) requestAnimationFrame(tick);
        else el.textContent = target.toFixed(decimals) + suffix;
    }
    requestAnimationFrame(tick);
});

/* ==================== إعدادات مشتركة ==================== */
Chart.defaults.font.family = "'Tajawal', 'Segoe UI', sans-serif";
Chart.defaults.color = '#64748b';

/* ==================== رسم: اتجاه الحضور ==================== */
new Chart(document.getElementById('attendanceTrendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($trendLabels, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [{
            label: 'نسبة الحضور %',
            data: <?= json_encode($trendValues); ?>,
            borderColor: '#4338ca',
            backgroundColor: 'rgba(67,56,202,.12)',
            fill: true,
            tension: .35,
            pointRadius: 3,
            pointBackgroundColor: '#4338ca',
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } },
            x: { reverse: true }
        }
    }
});

/* ==================== رسم: توزيع الأقسام ==================== */
new Chart(document.getElementById('deptChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($deptLabels, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [{
            data: <?= json_encode($deptValues); ?>,
            backgroundColor: ['#4338ca','#0f766e','#f59e0b','#e11d48','#0369a1','#6d28d9','#059669'],
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
        cutout: '62%'
    }
});

/* ==================== رسم: توزيع الدرجات ==================== */
new Chart(document.getElementById('gradeChart'), {
    type: 'bar',
    data: {
        labels: ['90-100', '80-89', '70-79', '60-69', 'أقل من 60'],
        datasets: [{
            data: [
                <?= (int)$gradeBands['band_a']; ?>,
                <?= (int)$gradeBands['band_b']; ?>,
                <?= (int)$gradeBands['band_c']; ?>,
                <?= (int)$gradeBands['band_d']; ?>,
                <?= (int)$gradeBands['band_f']; ?>
            ],
            backgroundColor: ['#059669','#0d9488','#f59e0b','#f97316','#e11d48'],
            borderRadius: 8,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

/* ==================== رسم: الصفوف الأكثر عدداً ==================== */
new Chart(document.getElementById('classChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($classLabels, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [{
            data: <?= json_encode($classValues); ?>,
            backgroundColor: '#f59e0b',
            borderRadius: 8,
        }]
    },
    options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

<?php if ($logsAvailable): ?>
/* ==================== رسم: نشاط النظام حسب الخطورة ==================== */
new Chart(document.getElementById('severityChart'), {
    type: 'doughnut',
    data: {
        labels: ['عادي', 'تنبيه', 'خطر'],
        datasets: [{
            data: [<?= $sevMap['info']; ?>, <?= $sevMap['warning']; ?>, <?= $sevMap['danger']; ?>],
            backgroundColor: ['#94a3b8', '#f59e0b', '#e11d48'],
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
        cutout: '62%'
    }
});
<?php endif; ?>

</script>

<?php include '../app/views/layouts/footer.php'; ?>
