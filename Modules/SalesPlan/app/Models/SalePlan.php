<?php

namespace Modules\SalesPlan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
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
    public static function dashboardReport()
    {
        return [
            'total_sale_plans' => self::count(),
            'active_sale_plans' => self::whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->count(),
            'cars_in_active_sale_plans' => DB::table('sales_plan_cars')
                ->join('sales_plans', 'sales_plan_cars.sale_plan_id', '=', 'sales_plans.id')
                ->whereDate('sales_plans.start_date', '<=', now())
                ->whereDate('sales_plans.end_date', '>=', now())
                ->count(),

        ];
    }
}
