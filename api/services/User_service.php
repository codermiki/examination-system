<?php
require_once "config/db.config.php";
class User_service
{
    public static function getAllUsers()
    {
        global $conn;
        $sql = "SELECT * FROM users";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($result)) {
            return [
                'message' => 'success',
                'data' => $result
            ];
        } else {
            return [
                'error' => 'User not found'
            ];
        }
    }
}