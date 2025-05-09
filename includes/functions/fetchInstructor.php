<?php
include __DIR__ . "/../db/db.config.php";
class fetchInstructor
{
    public static function fetchInstructor()
    {
        global $pdo;

        $sql = "SELECT * FROM assigned_instructors";
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