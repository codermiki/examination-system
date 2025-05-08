<?php
include __DIR__ . "/../db/db.config.php";
class fetchStudent
{
    public static function fetchStudent()
    {
        global $pdo;

        $sql = "SELECT * FROM assigned_students";
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