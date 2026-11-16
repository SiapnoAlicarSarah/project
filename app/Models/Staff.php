<?php
namespace App\Models;

class Staff extends User
{
    public function register()
    {
        $stmt = $this->conn->prepare("INSERT INTO users(name, email, password, role) VALUES(?,?,?,?)");
        $stmt->execute([$this->name, $this->email, $this->password, $this->role]);
        $userId = $this->conn->lastInsertId();
        $this->conn->prepare("INSERT INTO staff(user_id, position, hire_date) VALUES(?, 'Staff', CURDATE())")
            ->execute([$userId]);
        return "Registration Success";
    }
}
?>