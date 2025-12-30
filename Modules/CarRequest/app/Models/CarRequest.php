<?php

namespace Modules\CarRequest\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Cars\Models\Car;
use Modules\SalesPlan\Models\SalePlan;
use Modules\Users\Models\User;

// use Modules\CarRequest\Database\Factories\CarRequestFactory;

class CarRequest extends Model
{
    use HasFactory;
    protected $fillable = ['sale_plan_id', 'user_id', 'car_id', 'price', 'status'];
    protected $table = "car_requests";
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function sale_plan()
    {
        return $this->belongsTo(SalePlan::class);
    }
    public static function dashboardReport()
    {
        $now = now();
        $activeSalePlans = SalePlan::whereDate('start_date', '<=', $now)
            ->whereDate('end_date', '>=', $now)
            ->pluck('id');

        return [
            'total_requests' => self::count(),
            'requests_in_active_sale_plans' => self::whereIn('sale_plan_id', $activeSalePlans)->count(),
            'average_price_in_active_sale_plans' => self::whereIn('sale_plan_id', $activeSalePlans)->avg('price'),
        ];
    }
}
