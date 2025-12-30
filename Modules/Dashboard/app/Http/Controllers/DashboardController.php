<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CarRequest\Models\CarRequest;
use Modules\Cars\Models\Car;
use Modules\SalesPlan\Models\SalePlan;
use Modules\Users\Models\User;

class DashboardController extends Controller
{

    public function dashboard()
    {

        return response()->json(
            [
                'message' => 'dashboard content',
                'success' => true,
                'data' => [
                    'users'    => User::dashboardReport(),
                    'cars'    => Car::dashboardReport(),
                    'sales_plan'    => SalePlan::dashboardReport(),
                    'car_request'    => CarRequest::dashboardReport(),
                ]
            ]
        );
    }
}
