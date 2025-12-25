<?php

namespace Modules\SalesPlan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Cars\Models\Car;


class SalePlan extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'start_date', 'end_date'];
    protected $table = 'sales_plans';
    public function cars()
    {
        return $this->belongsToMany(Car::class, 'sales_plan_cars');
    }
}
