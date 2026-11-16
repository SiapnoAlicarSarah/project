<?php
namespace App\Models;

interface Manageable
{
    public function insert($plan_name, $price, $duration_days, $description = "", $image = null);
    public function delete($plan_id);
    public function update($plan_name, $price, $duration_days, $description, $plan_id);
}
?>