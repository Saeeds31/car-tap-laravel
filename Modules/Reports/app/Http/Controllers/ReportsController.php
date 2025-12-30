<?php

namespace Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\CarRequest\Models\CarRequest;
use Modules\Cars\Models\Car;
use Modules\CourseOrder\Models\CourseOrder;
use Modules\CourseOrder\Models\OrderResult;
use Modules\Orders\Models\Order;
use Modules\Products\Models\Product;
use Modules\Users\Models\User;

class ReportsController extends Controller
{

    public function usersReport(Request $request)
    {
        $query = User::whereHas('roles', function ($q) {
            $q->where('slug', 'customer');
        });

        if ($request->filled('full_name')) {
            $query->where('full_name', 'like', '%' . $request->full_name . '%');
        }
        if ($request->filled('mobile')) {
            $query->where('mobile', 'like', '%' . $request->mobile . '%');
        }

        if ($request->filled('national_code')) {
            $query->where('national_code', 'like', '%' . $request->national_code . '%');
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        $users = $query->latest()->paginate($request->get('per_page', 15));
        return response()->json($users);
    }
    public function carsReport(Request $request)
    {
        $query = Car::query()->with(['brand', 'category']);

        // فیلتر بر اساس نام خودرو
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // فیلتر بر اساس برند
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // فیلتر بر اساس دسته‌بندی
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // فیلتر بر اساس بازه قیمت
        if ($request->filled('min_price') && $request->filled('max_price')) {
            $query->whereBetween('price', [$request->min_price, $request->max_price]);
        } elseif ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        } elseif ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // فیلتر بر اساس تاریخ ثبت (created_at)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // صفحه‌بندی
        $cars = $query->latest()->paginate($request->get('per_page', 15));

        // خروجی JSON برای API
        return response()->json($cars);
    }
    public function carRequestReport(Request $request)
    {
        $query = CarRequest::query()->with(['user', 'car', 'sale_plan']);

        if ($request->filled('user_name')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('full_name', 'like', '%' . $request->user_name . '%');
            });
        }
        if ($request->filled('sale_plan_id')) {
            $query->where('sale_plan_id', $request->sale_plan_id);
        }

        if ($request->filled('car_name')) {
            $query->whereHas('car', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->car_name . '%');
            });
        }
        if ($request->filled('min_price') && $request->filled('max_price')) {
            $query->whereBetween('price', [$request->min_price, $request->max_price]);
        } elseif ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        } elseif ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $requests = $query->latest()->paginate($request->get('per_page', 15));
        return response()->json($requests);
    }
}
