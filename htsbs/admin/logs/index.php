<?php
/*
=====================================================================
لوحة سجل النشاط (Audit Logs) — الأدمن فقط
- تدمج جدولين: system_logs (النظام) + lms_logs (التعلم التفاعلي)
- فلاتر: بحث نصي | المصدر | الوحدة | الإجراء | المستخدم | الخطورة | التاريخ
- بطاقات إحصائية + ترقيم صفحات + تصدير CSV + حذف السجلات القديمة
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';

/* ==================== الصلاحية: أدمن فقط ==================== */
if (!Auth::check()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

if ((int)($_SESSION['role_id'] ?? 0) !== 1) {
    die('Access Denied');
}

$database = new Database();
$db = $database->connect();

/* دالة إخراج آمن (قد تكون معرفة مسبقاً في config) */
if (!function_exists('e')) {
    function e($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$flash = null;

/* ==================== حذف السجلات القديمة ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'purge') {

    $days = (int)($_POST['days'] ?? 90);
    $days = max(7, min($days, 3650)); // حماية: بين أسبوع و 10 سنوات

    try {
        $stmt = $db->prepare("DELETE FROM system_logs WHERE created_at < (NOW() - INTERVAL ? DAY)");
        $stmt->execute([$days]);
        $n1 = $stmt->rowCount();

        $stmt = $db->prepare("DELETE FROM lms_logs WHERE created_at < (NOW() - INTERVAL ? DAY)");
        $stmt->execute([$days]);
        $n2 = $stmt->rowCount();

        // نسجّل عملية الحذف نفسها (شفافية)
        require_once '../../core/Logger.php';
        Logger::log('system', 'purge_logs',
            "حذف السجلات الأقدم من $days يوم (نظام: $n1 | تفاعلي: $n2)",
            null, null, 'danger');

        $flash = ['success', "تم حذف " . ($n1 + $n2) . " سجلاً أقدم من $days يوم"];

    } catch (Throwable $ex) {
        $flash = ['danger', 'تعذر الحذف: ' . $ex->getMessage()];
    }
}

/* ==================== الفلاتر ==================== */
$q        = trim((string)($_GET['q'] ?? ''));
$source   = (string)($_GET['source'] ?? '');   // system | lms | ''
$module   = (string)($_GET['module'] ?? '');
$action   = (string)($_GET['action_f'] ?? '');
$userId   = (int)($_GET['user_id'] ?? 0);
$severity = (string)($_GET['severity'] ?? '');
$from     = (string)($_GET['from'] ?? '');
$to       = (string)($_GET['to'] ?? '');

if (!in_array($source, ['system', 'lms'], true))            $source = '';
if (!in_array($severity, ['info', 'warning', 'danger'], true)) $severity = '';

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset  = ($page - 1) * $perPage;

/*
====================================
بناء استعلام موحّد (UNION ALL) للجدولين
- lms_logs لا يحتوي severity/module → نضع قيماً افتراضية
====================================
*/
function lms_logs_query(): string
{
    // تُعيد جدولاً مشتقاً باسم logs، يُستخدم بعد FROM مباشرة
    return "
    (

        SELECT
            'system'            AS source,
            l.id                AS id,
            l.user_id           AS user_id,
            COALESCE(u.full_name, l.user_name, 'زائر') AS user_name,
            l.role_id           AS role_id,
            l.module            AS module,
            l.action            AS action,
            l.details           AS details,
            l.severity          AS severity,
            l.ip_address        AS ip_address,
            l.created_at        AS created_at
        FROM system_logs l
        LEFT JOIN users u ON l.user_id = u.id

        UNION ALL

        SELECT
            'lms'               AS source,
            l.id                AS id,
            l.user_id           AS user_id,
            COALESCE(u.full_name, 'زائر') AS user_name,
            u.role_id           AS role_id,
            'lms'               AS module,
            l.action            AS action,
            l.details           AS details,
            'info'              AS severity,
            l.ip_address        AS ip_address,
            l.created_at        AS created_at
        FROM lms_logs l
        LEFT JOIN users u ON l.user_id = u.id

    ) AS logs
    ";
}

/* شروط الفلترة (تُطبَّق على النتيجة الموحّدة) */
$where  = [];
$params = [];

if ($q !== '') {
    $where[] = "(logs.details LIKE ? OR logs.action LIKE ? OR logs.user_name LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($source !== '')   { $where[] = "logs.source = ?";   $params[] = $source; }
if ($module !== '')   { $where[] = "logs.module = ?";   $params[] = $module; }
if ($action !== '')   { $where[] = "logs.action = ?";   $params[] = $action; }
if ($userId > 0)      { $where[] = "logs.user_id = ?";  $params[] = $userId; }
if ($severity !== '') { $where[] = "logs.severity = ?"; $params[] = $severity; }
if ($from !== '')     { $where[] = "DATE(logs.created_at) >= ?"; $params[] = $from; }
if ($to !== '')       { $where[] = "DATE(logs.created_at) <= ?"; $params[] = $to; }

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* ==================== العدد الكلي ==================== */
$stmt = $db->prepare("SELECT COUNT(*) FROM " . lms_logs_query() . " $whereSql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$totalPages = max(1, (int)ceil($total / $perPage));

/* ==================== السجلات ==================== */
$sql = "SELECT * FROM " . lms_logs_query() . " $whereSql ORDER BY logs.created_at DESC, logs.id DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==================== بيانات الفلاتر ==================== */
$modules = $db->query("
    SELECT DISTINCT module FROM system_logs
    UNION SELECT 'lms'
    ORDER BY module
")->fetchAll(PDO::FETCH_COLUMN);

$actions = $db->query("
    SELECT DISTINCT action FROM system_logs
    UNION
    SELECT DISTINCT action FROM lms_logs
    ORDER BY action
")->fetchAll(PDO::FETCH_COLUMN);

$users = $db->query("
    SELECT id, full_name FROM users ORDER BY full_name
")->fetchAll(PDO::FETCH_ASSOC);

/* ==================== بطاقات إحصائية ==================== */
$statToday = (int)$db->query("
    SELECT (SELECT COUNT(*) FROM system_logs WHERE DATE(created_at) = CURDATE())
         + (SELECT COUNT(*) FROM lms_logs    WHERE DATE(created_at) = CURDATE())
")->fetchColumn();

$statTotal = (int)$db->query("
    SELECT (SELECT COUNT(*) FROM system_logs) + (SELECT COUNT(*) FROM lms_logs)
")->fetchColumn();

$statDanger = (int)$db->query("
    SELECT COUNT(*) FROM system_logs
    WHERE severity = 'danger' AND created_at >= (NOW() - INTERVAL 7 DAY)
")->fetchColumn();

$statFailed = (int)$db->query("
    SELECT COUNT(*) FROM system_logs
    WHERE action = 'login_failed' AND created_at >= (NOW() - INTERVAL 7 DAY)
")->fetchColumn();

/* روابط تحافظ على الفلاتر */
$qs = $_GET;
unset($qs['page']);
$baseQs = http_build_query($qs);

/* تسميات الأدوار */
$roleLabels = [1 => 'أدمن', 2 => 'معلم', 3 => 'طالب', 4 => 'ولي أمر'];

include '../../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/sidebar.php'; ?>

<div class="col-md-10 p-4">

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <h4 class="fw-bold mb-0">
    <i class="bi bi-journal-text text-primary"></i> سجل نشاط النظام
  </h4>

  <div class="d-flex gap-2">
    <a href="export.php?<?= e($baseQs) ?>" class="btn btn-success btn-sm">
      <i class="bi bi-file-earmark-excel"></i> تصدير CSV
    </a>
    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#purgeModal">
      <i class="bi bi-trash"></i> حذف السجلات القديمة
    </button>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash[0] ?> alert-dismissible fade show">
  <?= e($flash[1]) ?>
  <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ==================== بطاقات إحصائية ==================== -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body">
        <i class="bi bi-calendar-day text-primary fs-3"></i>
        <div class="fs-4 fw-bold"><?= number_format($statToday) ?></div>
        <small class="text-muted">سجلات اليوم</small>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body">
        <i class="bi bi-database text-info fs-3"></i>
        <div class="fs-4 fw-bold"><?= number_format($statTotal) ?></div>
        <small class="text-muted">إجمالي السجلات</small>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body">
        <i class="bi bi-exclamation-triangle text-danger fs-3"></i>
        <div class="fs-4 fw-bold"><?= number_format($statDanger) ?></div>
        <small class="text-muted">عمليات خطرة (7 أيام)</small>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body">
        <i class="bi bi-shield-exclamation text-warning fs-3"></i>
        <div class="fs-4 fw-bold"><?= number_format($statFailed) ?></div>
        <small class="text-muted">محاولات دخول فاشلة (7 أيام)</small>
      </div>
    </div>
  </div>
</div>

<!-- ==================== الفلاتر ==================== -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <form method="get" class="row g-2">

      <div class="col-md-3">
        <label class="form-label small fw-bold">بحث</label>
        <input type="text" name="q" value="<?= e($q) ?>" class="form-control form-control-sm"
               placeholder="نص، إجراء، اسم مستخدم...">
      </div>

      <div class="col-md-2">
        <label class="form-label small fw-bold">المصدر</label>
        <select name="source" class="form-select form-select-sm">
          <option value="">الكل</option>
          <option value="system" <?= $source === 'system' ? 'selected' : '' ?>>النظام</option>
          <option value="lms"    <?= $source === 'lms' ? 'selected' : '' ?>>التعلم التفاعلي</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label small fw-bold">الوحدة</label>
        <select name="module" class="form-select form-select-sm">
          <option value="">الكل</option>
          <?php foreach ($modules as $m): ?>
            <option value="<?= e($m) ?>" <?= $module === $m ? 'selected' : '' ?>><?= e($m) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label small fw-bold">الإجراء</label>
        <select name="action_f" class="form-select form-select-sm">
          <option value="">الكل</option>
          <?php foreach ($actions as $a): ?>
            <option value="<?= e($a) ?>" <?= $action === $a ? 'selected' : '' ?>><?= e($a) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label small fw-bold">المستخدم</label>
        <select name="user_id" class="form-select form-select-sm">
          <option value="">الكل</option>
          <?php foreach ($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= $userId === (int)$u['id'] ? 'selected' : '' ?>>
              <?= e($u['full_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label small fw-bold">الخطورة</label>
        <select name="severity" class="form-select form-select-sm">
          <option value="">الكل</option>
          <option value="info"    <?= $severity === 'info' ? 'selected' : '' ?>>عادي</option>
          <option value="warning" <?= $severity === 'warning' ? 'selected' : '' ?>>تنبيه</option>
          <option value="danger"  <?= $severity === 'danger' ? 'selected' : '' ?>>خطر</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label small fw-bold">من تاريخ</label>
        <input type="date" name="from" value="<?= e($from) ?>" class="form-control form-control-sm">
      </div>

      <div class="col-md-2">
        <label class="form-label small fw-bold">إلى تاريخ</label>
        <input type="date" name="to" value="<?= e($to) ?>" class="form-control form-control-sm">
      </div>

      <div class="col-md-6 d-flex align-items-end gap-2">
        <button class="btn btn-primary btn-sm">
          <i class="bi bi-funnel"></i> تصفية
        </button>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-x-circle"></i> إلغاء الفلاتر
        </a>
      </div>

    </form>
  </div>
</div>

<!-- ==================== الجدول ==================== -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <span class="fw-bold">النتائج</span>
    <span class="badge bg-secondary"><?= number_format($total) ?> سجل</span>
  </div>

  <div class="table-responsive">
    <table class="table table-hover table-sm align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>التاريخ والوقت</th>
          <th>المستخدم</th>
          <th>الدور</th>
          <th>المصدر</th>
          <th>الوحدة</th>
          <th>الإجراء</th>
          <th>التفاصيل</th>
          <th>IP</th>
        </tr>
      </thead>
      <tbody>

        <?php if (!$rows): ?>
        <tr>
          <td colspan="8" class="text-center text-muted py-4">
            لا توجد سجلات مطابقة
          </td>
        </tr>
        <?php endif; ?>

        <?php foreach ($rows as $r): ?>
        <?php
            $sev = $r['severity'] ?? 'info';
            $rowClass = $sev === 'danger' ? 'table-danger'
                      : ($sev === 'warning' ? 'table-warning' : '');
        ?>
        <tr class="<?= $rowClass ?>">
          <td class="small text-nowrap">
            <?= e(date('Y-m-d H:i', strtotime((string)$r['created_at']))) ?>
          </td>
          <td class="small"><?= e($r['user_name'] ?? 'زائر') ?></td>
          <td class="small">
            <?= e($roleLabels[(int)($r['role_id'] ?? 0)] ?? '—') ?>
          </td>
          <td>
            <?php if ($r['source'] === 'lms'): ?>
              <span class="badge bg-info text-dark">تفاعلي</span>
            <?php else: ?>
              <span class="badge bg-secondary">نظام</span>
            <?php endif; ?>
          </td>
          <td class="small"><?= e($r['module']) ?></td>
          <td class="small"><code><?= e($r['action']) ?></code></td>
          <td class="small"><?= e($r['details'] ?? '—') ?></td>
          <td class="small text-muted" dir="ltr"><?= e($r['ip_address'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>

      </tbody>
    </table>
  </div>
</div>

<!-- ==================== ترقيم الصفحات ==================== -->
<?php if ($totalPages > 1): ?>
<nav class="mt-3">
  <ul class="pagination pagination-sm justify-content-center flex-wrap">

    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
      <a class="page-link" href="?<?= e($baseQs) ?>&page=<?= $page - 1 ?>">السابق</a>
    </li>

    <?php
    $start = max(1, $page - 3);
    $end   = min($totalPages, $page + 3);
    ?>

    <?php if ($start > 1): ?>
      <li class="page-item"><a class="page-link" href="?<?= e($baseQs) ?>&page=1">1</a></li>
      <li class="page-item disabled"><span class="page-link">…</span></li>
    <?php endif; ?>

    <?php for ($i = $start; $i <= $end; $i++): ?>
      <li class="page-item <?= $i === $page ? 'active' : '' ?>">
        <a class="page-link" href="?<?= e($baseQs) ?>&page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>

    <?php if ($end < $totalPages): ?>
      <li class="page-item disabled"><span class="page-link">…</span></li>
      <li class="page-item">
        <a class="page-link" href="?<?= e($baseQs) ?>&page=<?= $totalPages ?>"><?= $totalPages ?></a>
      </li>
    <?php endif; ?>

    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
      <a class="page-link" href="?<?= e($baseQs) ?>&page=<?= $page + 1 ?>">التالي</a>
    </li>

  </ul>
</nav>
<?php endif; ?>

</div>
</div>
</div>

<!-- ==================== نافذة حذف السجلات القديمة ==================== -->
<div class="modal fade" id="purgeModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="purge">

      <div class="modal-header">
        <h5 class="modal-title">حذف السجلات القديمة</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <p class="text-danger">
          <i class="bi bi-exclamation-triangle"></i>
          سيتم حذف السجلات الأقدم من المدة المحددة نهائياً ولا يمكن التراجع.
        </p>

        <label class="form-label fw-bold">احذف السجلات الأقدم من (بالأيام)</label>
        <input type="number" name="days" class="form-control" value="90" min="7" max="3650" required>
        <small class="text-muted">الحد الأدنى 7 أيام</small>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
        <button class="btn btn-danger">
          <i class="bi bi-trash"></i> تأكيد الحذف
        </button>
      </div>
    </form>
  </div>
</div>

<?php include '../../app/views/layouts/footer.php'; ?>
