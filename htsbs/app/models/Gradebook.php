public function calculateFinalGrade(
$studentId,
$courseId
)
{
$sql = "

SELECT

assessment_type,

AVG(score/max_score*100) AS percentage

FROM gradebook

WHERE student_id=?
AND course_id=?

GROUP BY assessment_type

";

$stmt = $this->db->prepare($sql);

$stmt->execute([
    $studentId,
    $courseId
]);

$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

$weights = $this->db->query(
"SELECT * FROM grade_weights LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

$finalGrade = 0;

foreach($grades as $grade)
{
    switch($grade['assessment_type'])
    {
        case 'Quiz':
            $finalGrade +=
            $grade['percentage']
            *
            ($weights['quiz_weight']/100);
            break;

        case 'Assignment':
            $finalGrade +=
            $grade['percentage']
            *
            ($weights['assignment_weight']/100);
            break;

        case 'Activity':
            $finalGrade +=
            $grade['percentage']
            *
            ($weights['activity_weight']/100);
            break;

        case 'Midterm':
            $finalGrade +=
            $grade['percentage']
            *
            ($weights['midterm_weight']/100);
            break;

        case 'Final':
            $finalGrade +=
            $grade['percentage']
            *
            ($weights['final_weight']/100);
            break;

        case 'Participation':
            $finalGrade +=
            $grade['percentage']
            *
            ($weights['participation_weight']/100);
            break;
    }
}

return round(
    $finalGrade,
    2
);


}
