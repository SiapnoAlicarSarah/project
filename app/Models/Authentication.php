<?php

namespace App\Models;
use App\Database;
use App\Models\Client;
use App\Models\Staff;

class Authentication{
    private $conn;
    private const STAFF_CODE = "FITSYNC-STAFF-2026";
    public function __construct()
    {
        $database=new Database();
        $this->conn=$database->connection();
    }
    public function register($name, $email, $password, $role, $staffCode = "")
    {
        if ($role === "staff" && $staffCode !== self::STAFF_CODE) {
            return "Invalid staff access code.";
        }
        if (!in_array($role, ["client", "staff"], true)) {
            $role = "client";
        }

        $user = ($role === "staff")
            ? new Staff($this->conn, $name, $email, $password, $role)
            : new Client($this->conn, $name, $email, $password, $role);

        return $user->register();
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