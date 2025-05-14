 <?php
    include __DIR__ . "/../../db/db.config.php";
    class Feedback_function
    {
        public static function fetchFeedbacks($instructorId)
        {
            global $conn;

            try {
                $sql = "SELECT
                f.id AS feedback_id,
                f.student_id,
                f.exam_id,
                f.feedback_text,
                f.rate,
                f.created_at,
                u.name AS student_name,
                c.course_name,
                e.exam_title
            FROM feedbacks f
            JOIN users u ON f.student_id = u.user_id
            JOIN exams e ON f.exam_id = e.exam_id
            JOIN courses c ON e.course_id = c.course_id 
            WHERE e.instructor_id = :instructor_id
            ORDER BY f.created_at DESC";

                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($result)) {
                    return $result;
                } else {
                    return [];
                }
            } catch (PDOException $e) {
                error_log("Error fetching instructor's feedbacks: " . $e->getMessage());
                $message = '<div class="message error">Error loading feedbacks. Please try again later.</div>';
            }
        }
    }
