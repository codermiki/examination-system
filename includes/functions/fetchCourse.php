<?php
include __DIR__ . "/../db/db.config.php";
class fetchCourse
{
    public static function fetchCourse()
    {
        global $pdo;

        $sql = "SELECT * FROM assigned_courses";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($result)) {
            return $result;
        } else {
            return [];
        }
    }
}