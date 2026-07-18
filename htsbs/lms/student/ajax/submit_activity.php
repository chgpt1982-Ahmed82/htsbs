<?php
/*
=====================================================================
LMS - AJAX: تصحيح النشاط تلقائياً وحفظ المحاولة
يُرجع JSON: الدرجة، الصحيح، الشرح، المحاولات، الزمن، التحفيز، الإنجازات
=====================================================================
*/
require_once dirname(__DIR__, 2) . '/includes/lms_init.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || (int)($_SESSION['role_id'] ?? 0) !== 3) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE));
}

lms_csrf_check();

$student = $lms->getStudentByUserId((int)$_SESSION['user_id']);
if (!$student) exit(json_encode(['success' => false, 'message' => 'Student Not Found'], JSON_UNESCAPED_UNICODE));

$studentId  = (int)$student['id'];
$activityId = (int)($_POST['activity_id'] ?? 0);

try {
    /* ============ التحقق من النشاط وصلاحية الوصول ============ */
    $stmt = $db->prepare("
        SELECT a.*, l.pass_grade, l.id AS lesson_id, l.course_id
        FROM lms_activities a
        INNER JOIN lms_lessons l ON a.lesson_id = l.id
        INNER JOIN course_assignments ca ON ca.course_id = l.course_id AND ca.class_id = ?
        WHERE a.id = ?
        LIMIT 1
    ");
    $stmt->execute([(int)$student['class_id'], $activityId]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$activity) {
        exit(json_encode(['success' => false, 'message' => 'النشاط غير متاح'], JSON_UNESCAPED_UNICODE));
    }

    if (!$lms->isLessonUnlocked((int)$activity['lesson_id'], $studentId)) {
        exit(json_encode(['success' => false, 'message' => 'الدرس مقفل'], JSON_UNESCAPED_UNICODE));
    }

    // الحد الأقصى للمحاولات (0 = غير محدود)
    $maxAttempts = (int)$lms->getSetting('max_attempts', 0);
    $stmt = $db->prepare("SELECT COUNT(*) FROM lms_student_activity_attempts WHERE activity_id=? AND student_id=?");
    $stmt->execute([$activityId, $studentId]);
    $prevAttempts = (int)$stmt->fetchColumn();

    if ($maxAttempts > 0 && $prevAttempts >= $maxAttempts) {
        exit(json_encode(['success' => false, 'message' => 'استنفدت عدد المحاولات المسموح'], JSON_UNESCAPED_UNICODE));
    }

    /* ============ حساب الزمن ============ */
    $startedAt = (int)($_POST['started_at'] ?? time());
    $duration  = max(0, min(time() - $startedAt, 86400));

    /* ============ التصحيح ============ */
    $type        = $activity['activity_type'];
    $userAnswers = $_POST['answers'] ?? [];
    if (!is_array($userAnswers)) $userAnswers = [];

    $details      = [];
    $totalPoints  = 0.0;
    $earnedPoints = 0.0;
    $pending      = false;
    $projectFile  = null;

    // الأسئلة والإجابات النموذجية
    $stmt = $db->prepare("
        SELECT * FROM lms_activity_questions
        WHERE activity_id = ? ORDER BY question_order, id
    ");
    $stmt->execute([$activityId]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
    دالة تطبيع النص للمقارنة (سؤال قصير)
    */
    $normalize = function (string $s): string {
        $s = trim(mb_strtolower($s));
        $s = preg_replace('/\s+/u', ' ', $s);
        // توحيد بعض الحروف العربية
        $s = str_replace(['أ','إ','آ'], 'ا', $s);
        $s = str_replace('ة', 'ه', $s);
        $s = str_replace('ى', 'ي', $s);
        return preg_replace('/[\x{064B}-\x{065F}]/u', '', $s); // إزالة التشكيل
    };

    if ($type === 'project') {
        /* ⭐⭐⭐⭐⭐ مشروع: رفع ملف + تصحيح يدوي لاحق من المعلم */
        $pending = true;

        if (!empty($_FILES['project_file']['name'])) {
            $projectFile = lms_upload_file(
                $_FILES['project_file'], 'projects',
                ['pdf','doc','docx','ppt','pptx','zip','rar','png','jpg','jpeg','py','cpp','java','txt'],
                50
            );
        }

        $hasText = false;
        foreach ($userAnswers as $txt) {
            if (trim((string)$txt) !== '') { $hasText = true; break; }
        }

        if (!$hasText && !$projectFile) {
            exit(json_encode(['success' => false, 'message' => 'أرفق ملفاً أو اكتب حلك قبل التسليم'], JSON_UNESCAPED_UNICODE));
        }

        $score    = 0;   // بانتظار تصحيح المعلم
        $isPassed = 0;

    } else {

        foreach ($questions as $q) {
            $qid    = (int)$q['id'];
            $points = (float)$q['points'];
            $totalPoints += $points;

            // إجابات السؤال النموذجية
            $stmt = $db->prepare("SELECT * FROM lms_activity_answers WHERE question_id = ?");
            $stmt->execute([$qid]);
            $modelAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $correct       = false;
            $yourAnswer    = '';
            $correctAnswer = '';

            switch ($type) {

                case 'mcq':
                    $chosenId = (int)($userAnswers[$qid] ?? 0);
                    foreach ($modelAnswers as $a) {
                        if ((int)$a['is_correct'] === 1) $correctAnswer = $a['answer_text'];
                        if ((int)$a['id'] === $chosenId) {
                            $yourAnswer = $a['answer_text'];
                            $correct    = ((int)$a['is_correct'] === 1);
                        }
                    }
                    break;

                case 'true_false':
                    $chosen = ($userAnswers[$qid] ?? '') === 'true';
                    $model  = null;
                    foreach ($modelAnswers as $a) {
                        if ((int)$a['is_correct'] === 1) {
                            $model = $normalize($a['answer_text']);
                        }
                    }
                    // الإجابة النموذجية نصها "صح" أو "خطأ"
                    $modelIsTrue   = in_array($model, ['صح','true','1','نعم'], true);
                    $correct       = ($chosen === $modelIsTrue);
                    $yourAnswer    = $chosen ? 'صح' : 'خطأ';
                    $correctAnswer = $modelIsTrue ? 'صح' : 'خطأ';
                    break;

                case 'ordering':
                    // ترتيب الطالب: مصفوفة IDs بترتيبها المعروض
                    $studentOrder = array_map('intval', array_values((array)($userAnswers[$qid] ?? [])));
                    usort($modelAnswers, fn($a, $b) => (int)$a['correct_order'] <=> (int)$b['correct_order']);
                    $correctOrder  = array_map(fn($a) => (int)$a['id'], $modelAnswers);
                    $correct       = ($studentOrder === $correctOrder);
                    $byId          = array_column($modelAnswers, 'answer_text', 'id');
                    $yourAnswer    = implode(' ← ', array_map(fn($id) => $byId[$id] ?? '?', $studentOrder));
                    $correctAnswer = implode(' ← ', array_map(fn($a) => $a['answer_text'], $modelAnswers));
                    break;

                case 'matching':
                    // pairs: left_id => right_id (match_key متطابق = زوج صحيح)
                    $pairs    = (array)($userAnswers[$qid] ?? []);
                    $keyById  = array_column($modelAnswers, 'match_key', 'id');
                    $textById = array_column($modelAnswers, 'answer_text', 'id');
                    $lefts    = array_filter($modelAnswers, fn($a) => $a['match_side'] === 'left');

                    $okPairs = 0;
                    $yTxt = []; $cTxt = [];
                    foreach ($lefts as $lft) {
                        $lid     = (int)$lft['id'];
                        $chosenR = (int)($pairs[$lid] ?? 0);
                        $good    = $chosenR && ($keyById[$chosenR] ?? '≠') === $lft['match_key'];
                        if ($good) $okPairs++;

                        $yTxt[] = $lft['answer_text'] . ' ⟵ ' . ($textById[$chosenR] ?? '—');
                        foreach ($modelAnswers as $r) {
                            if ($r['match_side'] === 'right' && $r['match_key'] === $lft['match_key']) {
                                $cTxt[] = $lft['answer_text'] . ' ⟵ ' . $r['answer_text'];
                            }
                        }
                    }
                    $correct       = (count($lefts) > 0 && $okPairs === count($lefts));
                    $yourAnswer    = implode(' | ', $yTxt);
                    $correctAnswer = implode(' | ', $cTxt);
                    // درجة جزئية للتوصيل
                    if (!$correct && count($lefts) > 0) {
                        $earnedPoints += $points * ($okPairs / count($lefts));
                    }
                    break;

                case 'short_answer':
                    $yourAnswer = trim((string)($userAnswers[$qid] ?? ''));
                    $normUser   = $normalize($yourAnswer);
                    $threshold  = (float)$lms->getSetting('short_answer_similarity', 80);

                    foreach ($modelAnswers as $a) {
                        if ((int)$a['is_correct'] !== 1) continue;
                        $correctAnswer = $a['answer_text'];
                        $normModel     = $normalize($a['answer_text']);

                        if ($normUser !== '' && $normModel !== '') {
                            if ($normUser === $normModel) { $correct = true; break; }
                            similar_text($normUser, $normModel, $percent);
                            if ($percent >= $threshold) { $correct = true; break; }
                        }
                    }
                    break;
            }

            if ($correct) $earnedPoints += $points;

            $details[] = [
                'question'       => $q['question_text'],
                'correct'        => $correct,
                'your_answer'    => $yourAnswer,
                'correct_answer' => $correctAnswer,
                'explanation'    => $q['explanation']
            ];
        }

        $score    = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;
        $isPassed = ($score >= (float)$activity['pass_grade']) ? 1 : 0;
    }

    /* ============ حفظ المحاولة ============ */
    $attemptNo = $prevAttempts + 1;

    $stmt = $db->prepare("
        INSERT INTO lms_student_activity_attempts
            (activity_id, student_id, attempt_no, score, is_passed,
             answers_json, project_file, started_at, finished_at, duration_seconds)
        VALUES (?, ?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?), NOW(), ?)
    ");
    $stmt->execute([
        $activityId, $studentId, $attemptNo, $score, $isPassed,
        json_encode($userAnswers, JSON_UNESCAPED_UNICODE),
        $projectFile, $startedAt, $duration
    ]);

    $lms->log((int)$_SESSION['user_id'], 'submit_activity',
        "activity=$activityId score=$score passed=$isPassed attempt=$attemptNo");

    /* ============ أفضل نتيجة ============ */
    $stmt = $db->prepare("
        SELECT MAX(score) FROM lms_student_activity_attempts
        WHERE activity_id = ? AND student_id = ?
    ");
    $stmt->execute([$activityId, $studentId]);
    $bestScore = (float)$stmt->fetchColumn();

    /* ============ إعادة الحساب: تقدم + نجوم + شارات + شهادة + صدارة ============ */
    $recalc = $lms->recalculateAfterActivity($activityId, $studentId);

    /* ============ ملاحظة تشجيعية ============ */
    if ($pending) {
        $motivation = 'رائع! تم استلام مشروعك 📤 وسيصلك إشعار عند تصحيحه';
    } elseif ($score >= 100) {
        $motivation = 'مذهل! 🎯 درجة كاملة، أنت نجم حقيقي!';
    } elseif ($isPassed) {
        $motivation = 'أحسنت! 👏 اجتزت النشاط بنجاح، تابع للنشاط التالي';
    } elseif ($score >= 40) {
        $motivation = 'قريب جداً! 💪 راجع الشرح أعلاه وحاول مرة أخرى';
    } else {
        $motivation = 'لا تستسلم! 🌱 راجع الدرس جيداً ثم أعد المحاولة، كل محاولة تقربك من النجاح';
    }

    echo json_encode([
        'success'          => true,
        'score'            => $score,
        'is_passed'        => (bool)$isPassed,
        'attempt_no'       => $attemptNo,
        'best_score'       => $bestScore,
        'duration_text'    => lms_format_seconds($duration),
        'details'          => $details,
        'motivation'       => $motivation,
        'pending_grading'  => $pending,
        'lesson_completed' => $recalc['lesson_completed'],
        'star_awarded'     => $recalc['star_awarded'],
        'new_badges'       => $recalc['new_badges'],
        'certificate'      => $recalc['certificate'] ? ['no' => $recalc['certificate']['certificate_no']] : null
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $ex->getMessage()], JSON_UNESCAPED_UNICODE);
}
