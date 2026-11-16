<?php

namespace App\Models;
use App\Database;
class Authentication{
    private $conn;
    private const STAFF_CODE = "FITSYNC-STAFF-2026";
    public function __construct()
    {
        $database=new Database();
        $this->conn=$database->connection();
    }
    public function register($name, $email, $password, $role,)
    {
        $hashPass=password_hash($password,PASSWORD_DEFAULT);
        $sql="INSERT INTO users(name, email, password, role) VALUES(?,?,?,?)";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute([
            $name,
            $email,
            $hashPass,
            $role
        ]); 
        $userId = $this->conn->lastInsertId();
        if ($role === "staff") {
            $this->conn->prepare("INSERT INTO staff(user_id, position, hire_date) VALUES(?, 'Staff', CURDATE())")
               ->execute([$userId]);
            }   
            elseif ($role === "client") 
            {
                $this->conn->prepare("INSERT INTO client(user_id) VALUES(?)")->execute([$userId]);
            }

        return "Registration Success";
}
    
    public function login($email,$password)
    {
        $sql="SELECT * FROM users WHERE email=?";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute([$email]);
        $user=$stmt->fetch();

    if($user && $email==$user["email"] && password_verify($password,$user["password"]))
        {
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["name"]    = $user["name"];
            $_SESSION["role"]    = $user["role"];
            return "Login Success";
        }
        else 
        {
        return "Email or Password invalid";
        }
    }
}

?>