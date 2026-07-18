<?php

require_once dirname(__DIR__, 2) . '/config/database.php';

class QuizQuestion
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function getQuestions($quizId)
    {
        $stmt = $this->db->prepare(
        "SELECT *
        FROM quiz_questions
        WHERE quiz_id=?
        ORDER BY id DESC"
        );
        
        
        $stmt->execute([$quizId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    }


    public function create($data)
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
            $data['question'],
            'multiple_choice',
            $data['marks'] ?? 1,
            $data['option_a'],
            $data['option_b'],
            $data['option_c'],
            $data['option_d'],
            $data['correct_answer']
        ]);
        
        }


    public function delete($id)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM quiz_questions
             WHERE id=?"
        );

        return $stmt->execute([$id]);
    }
}
?>
