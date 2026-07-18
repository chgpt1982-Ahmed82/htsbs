<?php

require_once dirname(__DIR__, 2) . '/config/database.php';

class Quiz
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function getTeacherId($userId)
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM teachers WHERE user_id=?"
        );

        $stmt->execute([$userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    }

    public function getTeacherCourses($userId)
    {
        $sql = "
        SELECT DISTINCT
            c.id,
            c.course_name
        FROM course_assignments ca
        INNER JOIN teachers t
            ON ca.teacher_id=t.id
        INNER JOIN courses c
            ON ca.course_id=c.id
        WHERE t.user_id=?
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

   public function getAllByTeacher($userId)
{
    $sql = "

    SELECT

        q.*,

        c.course_name,

        c.course_code,

        (
            SELECT COUNT(*)
            FROM quiz_assignments qa
            WHERE qa.quiz_id = q.id
        ) AS usage_count

    FROM quizzes q

    INNER JOIN courses c
        ON q.course_id = c.id

    INNER JOIN teachers t
        ON q.teacher_id = t.id

    WHERE t.user_id = ?

    ORDER BY q.id DESC

    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([
        $userId
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function create($data)
{
    try
    {
        $stmt = $this->db->prepare(

            "INSERT INTO quizzes
            (
                teacher_id,
                course_id,
                title,
                duration_minutes,
                total_marks,
                start_date,
                end_date,
                attempts_allowed,
                is_published
            )
            VALUES
            (
                ?,?,?,?,?,?,?,?,?
            )"

        );

        $stmt->execute([

            $data['teacher_id'],
            $data['course_id'],
            $data['title'],
            $data['duration_minutes'],
            $data['total_marks'],
            $data['start_date'],
            $data['end_date'],
            $data['attempts_allowed'],
            $data['is_published']

        ]);

        return (int)$this->db->lastInsertId();
    }
    catch(PDOException $e)
    {
        die(
            'SQL Error: ' .
            $e->getMessage()
        );
    }
}


    public function getById($id)
            {
            $stmt = $this->db->prepare(
            "SELECT * FROM quizzes WHERE id=?"
            );
            
            
            $stmt->execute([$id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
            
            }
            
           public function update($id, $data)
            {
                $stmt = $this->db->prepare(
                    "UPDATE quizzes
                     SET
                     course_id=?,
                     title=?,
                     total_marks=?,
                     start_date=?,
                     end_date=?
                     WHERE id=?"
                );
            
                return $stmt->execute([
                    $data['course_id'],
                    $data['title'],
                    $data['total_marks'],
                    $data['start_date'],
                    $data['end_date'],
                    $id
                ]);
            }
            
            public function delete($id)
            {
            $stmt = $this->db->prepare(
            "DELETE FROM quizzes
            WHERE id=?"
            );
            
            
            return $stmt->execute([$id]);
            
            
            }

    
    public function getClasses()
        {
            return $this->db
                ->query(
                    "SELECT *
                     FROM classes
                     ORDER BY class_name"
                )
                ->fetchAll(PDO::FETCH_ASSOC);
        }
    
    
    public function addQuestion($data)
{
    $stmt = $this->db->prepare(
    "INSERT INTO quiz_questions
    (
        quiz_id,
        question_text,
        question_type,
        marks,
        option_a,
        option_b,
        option_c,
        option_d,
        correct_answer
    )
    VALUES
    (
        ?,?,?,?,?,?,?,?,?
    )"
    );

    return $stmt->execute([

        $data['quiz_id'],
        $data['question_text'],
        $data['question_type'],
        $data['marks'],
        $data['option_a'],
        $data['option_b'],
        $data['option_c'],
        $data['option_d'],
        $data['correct_answer']

    ]);
}

public function getQuestions($quizId)
{
    $stmt = $this->db->prepare(
    "SELECT *
     FROM quiz_questions
     WHERE quiz_id=?
     ORDER BY id ASC"
    );

    $stmt->execute([$quizId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getStudentQuizzes($classId)
{
    $stmt = $this->db->prepare("

    SELECT

        q.id,

        q.title,

        q.duration_minutes,

        q.total_marks,

        q.start_date,

        q.end_date,

        q.attempts_allowed,

        c.course_name,

        c.course_code

    FROM quiz_assignments qa

    INNER JOIN quizzes q
        ON qa.quiz_id = q.id

    INNER JOIN courses c
        ON q.course_id = c.id

    WHERE qa.class_id = ?

    AND q.is_published = 1

    ORDER BY q.start_date DESC

    ");

    $stmt->execute([
        $classId
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function createAttempt(
    $quizId,
    $studentId,
    $totalQuestions
)
{
    $stmt = $this->db->prepare(
    "INSERT INTO quiz_attempts
    (
        quiz_id,
        student_id,
        total_questions
    )
    VALUES
    (
        ?,?,?
    )"
    );

    $stmt->execute([
        $quizId,
        $studentId,
        $totalQuestions
    ]);

    return $this->db->lastInsertId();
}

public function saveAnswer(
    $attemptId,
    $questionId,
    $answer,
    $isCorrect
)
{
    $stmt = $this->db->prepare(
    "INSERT INTO quiz_answers
    (
        attempt_id,
        question_id,
        answer_text,
        is_correct
    )
    VALUES
    (
        ?,?,?,?
    )"
    );

    return $stmt->execute([
        $attemptId,
        $questionId,
        $answer,
        $isCorrect
    ]);
}

public function saveResult(
    $quizId,
    $studentId,
    $score
)
{
    $stmt = $this->db->prepare(
    "INSERT INTO quiz_results
    (
        quiz_id,
        student_id,
        score,
        completed_at
    )
    VALUES
    (
        ?,?,?,NOW()
    )"
    );

    return $stmt->execute([
        $quizId,
        $studentId,
        $score
    ]);
}

public function getResults($quizId)
{
    $stmt = $this->db->prepare(
    "SELECT

        qr.*,
        u.full_name

     FROM quiz_results qr

     INNER JOIN students s
     ON qr.student_id=s.id

     INNER JOIN users u
     ON s.user_id=u.id

     WHERE qr.quiz_id=?

     ORDER BY qr.score DESC"
    );

    $stmt->execute([$quizId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getStudentResults($studentId)
{
$stmt = $this->db->prepare(


"SELECT

    qr.*,
    q.title

 FROM quiz_results qr

 INNER JOIN quizzes q
 ON qr.quiz_id=q.id

 WHERE qr.student_id=?

 ORDER BY qr.completed_at DESC"

);

$stmt->execute([
    $studentId
]);

return $stmt->fetchAll(
    PDO::FETCH_ASSOC
);


}

    
}
?>
