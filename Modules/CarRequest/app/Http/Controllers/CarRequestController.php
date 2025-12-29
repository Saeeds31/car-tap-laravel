<?php

namespace Modules\CarRequest\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\CarRequest\Models\CarRequest;
use Modules\Cars\Models\Car;
use Modules\SalesPlan\Models\SalePlan;

class CarRequestController extends Controller
{
    public function storeRequest(Request $request, $saleId)
    {
        $user = $request->user();

        $data = $request->validate([
            'car_id' => 'required|integer',
        ]);
        $car = Car::findOrFail($data['car_id']);
        $salePlan = SalePlan::findOrFail($saleId);

        // 1. بررسی تاریخ طرح فروش
        $now = now();
        if ($salePlan->start_date > $now) {
            return response()->json([
                'message' => 'زمان ثبت‌نام این طرح هنوز شروع نشده است',
                'success' => false
            ], 400);
        }

        if ($salePlan->end_date < $now) {
            return response()->json([
                'message' => 'زمان ثبت‌نام این طرح به پایان رسیده است',
                'success' => false
            ], 400);
        }
        // 2. بررسی اینکه کاربر قبلاً در این طرح ثبت‌نام کرده یا نه
        $exists = CarRequest::where('sale_plan_id', $salePlan->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'شما قبلاً در این طرح ثبت‌نام کرده‌اید و امکان ثبت‌نام مجدد وجود ندارد',
                'success' => false
            ], 400);
        }

        // 3. بررسی موجودی خودرو در طرح
        $salePlanCar = DB::table('sales_plan_cars')
            ->where('sale_plan_id', $salePlan->id)
            ->where('car_id', $car->id)
            ->first();

        if (!$salePlanCar) {
            return response()->json([
                'message' => 'این خودرو در طرح انتخابی موجود نیست',
                'success' => false
            ], 400);
        }

        if ($salePlanCar->min_order_count <= 0) {
            return response()->json([
                'message' => 'موجودی این خودرو در طرح به پایان رسیده است',
                'success' => false
            ], 400);
        }

        // 4. ثبت درخواست
        $saleRequest = CarRequest::create([
            'sale_plan_id' => $salePlan->id,
            'user_id' => $user->id,
            'car_id' => $car->id,
            'price' => $car->price,
            'status' => 'pending'
        ]);

        // 5. کاهش موجودی خودرو در طرح
        DB::table('sales_plan_cars')
            ->where('id', $salePlanCar->id)
            ->decrement('min_order_count', 1);

        return response()->json([
            'message' => 'درخواست شما با موفقیت ثبت شد و به زودی با شما ارتباط حاصل خواهد شد',
            'success' => true
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('carrequest::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('carrequest::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('carrequest::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('carrequest::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
