<?php
require_once "config/db.config.php";
class User_service
{
    public static function install()
    {
        global $conn;

        try {
            // Check if users already exist
            $sql = "SELECT * FROM users";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $existingUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($existingUsers)) {
                return [
                    'message' => 'System Already Installed',
                ];
            }

            // Default users to insert
            $defaultPassword = password_hash("ChangeMe123!", PASSWORD_DEFAULT);
            $users = [
                [
                    'user_id' => 1,
                    'name' => 'System Admin',
                    'email' => 'admin@example.com',
                    'role' => 'Admin'
                ],
                [
                    'user_id' => 2,
                    'name' => 'Jane Instructor',
                    'email' => 'instructor@example.com',
                    'role' => 'Instructor'
                ],
                [
                    'user_id' => 3,
                    'name' => 'John Student',
                    'email' => 'student@example.com',
                    'role' => 'Student'
                ]
            ];

            $insertStmt = $conn->prepare("INSERT INTO users (user_id, name, email, password, role, must_reset_password)
                                      VALUES (:user_id, :name, :email, :password, :role, 1)");

            foreach ($users as $user) {
                $insertStmt->execute([
                    ':user_id' => $user['user_id'],
                    ':name' => $user['name'],
                    ':email' => $user['email'],
                    ':password' => $defaultPassword,
                    ':role' => $user['role']
                ]);
            }

            return [
                'message' => 'Default users installed successfully.',
                'created' => ['Admin', 'Instructor', 'Student']
            ];
        } catch (PDOException $e) {
            return [
                'error' => 'Installation failed.',
                'details' => $e->getMessage()
            ];
        }
    }

}