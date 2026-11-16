<?php
namespace App\Models;

abstract class User
{
    protected $conn;
    protected $name;
    protected $email;
    protected $password;
    protected $role;

    public function __construct($conn, $name, $email, $password, $role)
    {
        $this->conn = $conn;
        $this->name = $name;
        $this->email = $email;
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->role = $role;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getRole()
    {
        return $this->role;
    }

    abstract public function register();
}
?>