<?php

declare(strict_types=1);

require_once __DIR__ . '/Model.php';

class DashboardModel extends Model
{
    /**
     * معرف المعلم الحالي
     */
    private int $teacherId = 0;

    /**
 * Cache داخلي
 */
private array $cache = [];
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * تعيين معرف المعلم
     */
    public function setTeacher(
        int $teacherId
    ): self {

        $this->teacherId = $teacherId;

        return $this;

    }

    /**
     * الحصول على معرف المعلم
     */
    public function getTeacherId(): int
    {
        return $this->teacherId;
    }

    /**
     * إنشاء مصفوفة أشهر فارغة
     *
     * [1=>0 ... 12=>0]
     */
    protected function emptyMonths(): array
    {
        return array_fill(

            1,

            12,

            0

        );
    }

    /**
     * تنفيذ استعلام شهري عام
     */
    protected function monthlyData(

        string $table,

        string $dateColumn = 'created_at'

    ): array {

        $months = $this->emptyMonths();

        $sql = "

            SELECT

                MONTH($dateColumn) AS month,

                COUNT(*) AS total

            FROM $table

            WHERE teacher_id = ?

            AND YEAR($dateColumn)=YEAR(CURDATE())

            GROUP BY MONTH($dateColumn)

            ORDER BY MONTH($dateColumn)

        ";

        $stmt = $this->db->prepare(

            $sql

        );

        $stmt->execute([

            $this->teacherId

        ]);

        foreach (

            $stmt->fetchAll(PDO::FETCH_ASSOC)

            as

            $row

        ) {

            $months[

                (int)$row['month']

            ] =

            (int)$row['total'];

        }

        return $months;

    }

    /**
     * تنفيذ COUNT عام
     */
    protected function total(

        string $table

    ): int {

        $sql = "

            SELECT COUNT(*) total

            FROM $table

            WHERE teacher_id = ?

        ";

        $stmt = $this->db->prepare(

            $sql

        );

        $stmt->execute([

            $this->teacherId

        ]);

        return (int)

        $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    }

    /**
     * آخر السجلات
     */
    protected function latest(

        string $table,

        int $limit = 5

    ): array {

        $sql = "

            SELECT *

            FROM $table

            WHERE teacher_id = ?

            ORDER BY created_at DESC

            LIMIT ?

        ";

        $stmt = $this->db->prepare(

            $sql

        );

        $stmt->bindValue(

            1,

            $this->teacherId,

            PDO::PARAM_INT

        );

        $stmt->bindValue(

            2,

            $limit,

            PDO::PARAM_INT

        );

        $stmt->execute();

        return $stmt->fetchAll(

            PDO::FETCH_ASSOC

        );

    }

}

/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

/**
 * عدد الدروس
 */
public function countTeacherLessons(): int
{
    return $this->total(

        'lessons'

    );
}

/**
 * عدد الواجبات
 */
public function countTeacherAssignments(): int
{
    return $this->total(

        'assignments'

    );
}

/**
 * عدد الأنشطة
 */
public function countTeacherActivities(): int
{
    return $this->total(

        'activities'

    );
}

/**
 * عدد الاختبارات
 */
public function countTeacherQuizzes(): int
{
    return $this->total(

        'quizzes'

    );
}

/**
 * جميع البطاقات الإحصائية
 */
public function dashboardStatistics(): array
{
    return [

        'lessons' => $this->countTeacherLessons(),

        'assignments' => $this->countTeacherAssignments(),

        'activities' => $this->countTeacherActivities(),

        'quizzes' => $this->countTeacherQuizzes(),
        
        'behavior' =>$this->behaviorStatistics()

    ];
}

/*
|--------------------------------------------------------------------------
| Latest Records
|--------------------------------------------------------------------------
*/

/**
 * آخر الدروس
 */
public function latestLessons(
    int $limit = 5
): array {

    return $this->latest(

        'lessons',

        $limit

    );

}

/**
 * آخر الواجبات
 */
public function latestAssignments(
    int $limit = 5
): array {

    return $this->latest(

        'assignments',

        $limit

    );

}

/**
 * آخر الأنشطة
 */
public function latestActivities(
    int $limit = 5
): array {

    return $this->latest(

        'activities',

        $limit

    );

}

/**
 * آخر الاختبارات
 */
public function latestQuizzes(
    int $limit = 5
): array {

    return $this->latest(

        'quizzes',

        $limit

    );

}

/**
 * جميع آخر العناصر
 */
public function dashboardLatest(
    int $limit = 5
): array {

    return [

        'lessons' => $this->latestLessons(

            $limit

        ),

        'assignments' => $this->latestAssignments(

            $limit

        ),

        'activities' => $this->latestActivities(

            $limit

        ),

        'quizzes' => $this->latestQuizzes(

            $limit

        )

    ];

}

/*
|--------------------------------------------------------------------------
| Monthly Charts
|--------------------------------------------------------------------------
*/

/**
 * الدروس الشهرية
 */
public function monthlyLessons(): array
{
    return $this->monthlyData(

        'lessons'

    );
}

/**
 * الواجبات الشهرية
 */
public function monthlyAssignments(): array
{
    return $this->monthlyData(

        'assignments'

    );
}

/**
 * الأنشطة الشهرية
 */
public function monthlyActivities(): array
{
    return $this->monthlyData(

        'activities'

    );
}

/**
 * الاختبارات الشهرية
 */
public function monthlyQuizzes(): array
{
    return $this->monthlyData(

        'quizzes'

    );
}

/**
 * جميع بيانات الرسوم البيانية
 */
public function dashboardCharts(): array
{
    return [

        'lessons' => $this->monthlyLessons(),

        'assignments' => $this->monthlyAssignments(),

        'activities' => $this->monthlyActivities(),

        'quizzes' => $this->monthlyQuizzes()

    ];
}
/*
|--------------------------------------------------------------------------
| Teacher Activity Line Chart
|--------------------------------------------------------------------------
*/

/**
 * نشاط المعلم خلال السنة
 */
public function teacherActivityYearly(): array
{

    return [

        'months' => [

            'يناير',

            'فبراير',

            'مارس',

            'أبريل',

            'مايو',

            'يونيو',

            'يوليو',

            'أغسطس',

            'سبتمبر',

            'أكتوبر',

            'نوفمبر',

            'ديسمبر'

        ],

        'lessons' => $this->monthlyLessons(),

        'assignments' => $this->monthlyAssignments(),

        'activities' => $this->monthlyActivities(),

        'quizzes' => $this->monthlyQuizzes()

    ];

}

/*
|--------------------------------------------------------------------------
| Dashboard Complete Data
|--------------------------------------------------------------------------
*/

/**
 * جميع بيانات لوحة التحكم
 */
public function dashboard(): array
{

    return [

        /*
        |--------------------------------------------------
        | Statistics Cards
        |--------------------------------------------------
        */

        'stats' => $this->dashboardStatistics(),

        /*
        |--------------------------------------------------
        | Latest Records
        |--------------------------------------------------
        */

        'latest' => $this->dashboardLatest(),

        /*
        |--------------------------------------------------
        | Monthly Charts
        |--------------------------------------------------
        */

        'charts' => $this->dashboardCharts(),

        /*
        |--------------------------------------------------
        | Teacher Activity Line Chart
        |--------------------------------------------------
        */

        'teacherActivity' =>

            $this->teacherActivityYearly()

    ];

}
/*
|--------------------------------------------------------------------------
| Dashboard Analytics
|--------------------------------------------------------------------------
*/

/**
 * أكثر شهر نشاطاً
 */
public function mostActiveMonth(): array
{

    $activity = $this->teacherActivityYearly();

    $months = $activity['months'];

    $totals = [];

    for ($i = 1; $i <= 12; $i++) {

        $totals[$i] =

            $activity['lessons'][$i]

            +

            $activity['assignments'][$i]

            +

            $activity['activities'][$i]

            +

            $activity['quizzes'][$i];

    }

    $month = array_search(

        max($totals),

        $totals

    );

    return [

        'month' =>

            $months[$month - 1],

        'total' =>

            max($totals)

    ];

}

/**
 * أكثر مقرر يحتوي على دروس
 */
public function mostActiveCourse(): ?array
{

    $sql = "

        SELECT

            c.course_name,

            COUNT(*) total

        FROM lessons l

        INNER JOIN courses c

            ON c.id = l.course_id

        WHERE l.teacher_id = ?

        GROUP BY c.id

        ORDER BY total DESC

        LIMIT 1

    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([

        $this->teacherId

    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

}

/**
 * معدل الدروس لكل مقرر
 */
public function averageLessonsPerCourse(): float
{

    $sql = "

        SELECT

            AVG(total_lessons) avg_lessons

        FROM (

            SELECT

                COUNT(*) total_lessons

            FROM lessons

            WHERE teacher_id=?

            GROUP BY course_id

        ) x

    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([

        $this->teacherId

    ]);

    return round(

        (float)$stmt->fetchColumn(),

        2

    );

}

/**
 * إجمالي النشاط
 */
public function totalTeacherActivity(): int
{

    return

        $this->countTeacherLessons()

        +

        $this->countTeacherAssignments()

        +

        $this->countTeacherActivities()

        +

        $this->countTeacherQuizzes();

}


/**
 * جميع بيانات لوحة التحكم
 */
public function dashboardSummary(): array
{

    return [

        'stats' =>

            $this->dashboardStatistics(),

        'latest' =>

            $this->dashboardLatest(),

        'charts' =>

            $this->dashboardCharts(),

        'teacherActivity' =>

            $this->teacherActivityYearly(),

        'pie' => [

            'activitiesVsQuizzes' =>

                $this->activitiesVsQuizzes(),

            'lessonsVsAssignments' =>

                $this->lessonsVsAssignments()

        ],

        'doughnut' => [

            'lessonsByCourse' =>

                $this->lessonsByCourse(),

            'studentsByClass' =>

                $this->studentsByClass()

        ],

        'analytics' => [

            'mostActiveMonth' =>

                $this->mostActiveMonth(),

            'mostActiveCourse' =>

                $this->mostActiveCourse(),

            'averageLessonsPerCourse' =>

                $this->averageLessonsPerCourse(),

            'totalTeacherActivity' =>

                $this->totalTeacherActivity()

        ]

    ];

}

/**
 * إجمالي الملاحظات السلوكية
 */
 
public function countBehaviorNotes(): int
{
    $sql = "

        SELECT COUNT(*) total

        FROM behavior_notes

        WHERE teacher_id = ?

    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([

        $this->teacherId

    ]);

    return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

}
/**
 * عدد الملاحظات حسب النوع
 */
public function countBehaviorByType(
    string $type
): int
{

    $sql = "

        SELECT COUNT(*) total

        FROM behavior_notes

        WHERE teacher_id = ?

        AND note_type = ?

    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([

        $this->teacherId,

        $type

    ]);

    return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

}

/**
 * إحصائيات السلوك
 */
public function behaviorStatistics(): array
{

    return [

        'total' =>

            $this->countBehaviorNotes(),

        'positive' =>

            $this->countBehaviorByType(

                'positive'

            ),

        'warning' =>

            $this->countBehaviorByType(

                'warning'

            ),

        'negative' =>

            $this->countBehaviorByType(

                'negative'

            )

    ];

}


