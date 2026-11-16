<?php
namespace App\Models;

class Client extends User
{
    public function register()
    {
        $stmt = $this->conn->prepare("INSERT INTO users(name, email, password, role) VALUES(?,?,?,?)");
        $stmt->execute([$this->name, $this->email, $this->password, $this->role]);
        $userId = $this->conn->lastInsertId();
        $this->conn->prepare("INSERT INTO client(user_id) VALUES(?)")->execute([$userId]);
        return "Registration Success";
    }
}
?>