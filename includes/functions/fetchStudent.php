<?php
include __DIR__ . "/../db/db.config.php";
class fetchStudent
{
    public static function fetchAssignedStudent()
    {
        global $pdo;

        $sql = "SELECT * FROM assigned_students";
        $sql = "SELECT * FROM assigned_students";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($results)) {
            foreach ($results as $result) {
                
            }
            return $results;
        } else {
            return [];
        }
    }
}