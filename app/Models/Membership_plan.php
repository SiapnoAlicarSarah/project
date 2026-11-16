<?php 
namespace App\Models;
use App\Database;
use PDO;

class Membership_plan implements Manageable {
    private $conn;

    public function __construct()
    {
       $database=new Database(); 
       $this->conn=$database->connection();
    }

    public function getAll(){
        $sql="SELECT * FROM membership_plan";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute();
        $membership_plan=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return $membership_plan;
    }

    public function insert($plan_name,$price,$duration_days,$description="",$image=null){
        $sql="INSERT INTO membership_plan(plan_name,plan_price,duration_days,description,image) VALUES(?,?,?,?,?)";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute([
            $plan_name,$price,$duration_days,$description,$image
        ]);
        return "Membership Plan Added Successfully";
    }

    public function ViewbyId($plan_id){
        $sql="SELECT * FROM membership_plan WHERE plan_id=?";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute([$plan_id]);
        $plan=$stmt->fetch(PDO::FETCH_ASSOC);
        return $plan;
    }

    public function update($plan_name,$price,$duration_days,$description,$plan_id){
        $sql="UPDATE membership_plan SET plan_name=?,plan_price=?,duration_days=?,description=? WHERE plan_id=?";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute([
            $plan_name,$price,$duration_days,$description,$plan_id
        ]);
        return "Updated Successfully";
    }

    public function delete($plan_id){
        $sql="DELETE FROM membership_plan WHERE plan_id=?";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute([
            $plan_id
        ]);
        return "Deleted Successfully";
    }
}

?>