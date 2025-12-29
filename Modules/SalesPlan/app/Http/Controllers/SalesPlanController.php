<?php

namespace Modules\SalesPlan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\CarRequest\Models\CarRequest;
use Modules\Notifications\Services\NotificationService;
use Modules\SalesPlan\Models\SalePlan;

class SalesPlanController extends Controller
{

    public function salesPlanDetail($id)
    {
        $salePlan = SalePlan::with('cars')->findOrFail($id);
        $now = now();
        if ($salePlan->start_date > $now) {
            return response()->json([
                'message' => 'زمان پیش ثبت نام این طرح شروع  نشده است',
                'salePlan' => $salePlan
            ]);
        }
        if ($salePlan->end_date < $now) {
            return response()->json([
                'message' => 'زمان پیش ثبت نام این طرح تمام شده است',
                'salePlan' =>null
            ]);
        }
        return response()->json([
            'message' => 'جزئیات طرح',
            'salePlan' => $salePlan
        ]);
    }
    public function checkCarInSale($carId)
    {
        $now = now();
        $salePlan = SalePlan::whereHas('cars', function ($query) use ($carId) {
            $query->where('cars.id', $carId);
        })
            ->where('start_date', '<=', $now)   // طرح شروع شده باشد
            ->where('end_date', '>=', $now)     // طرح هنوز تمام نشده باشد
            ->first();

        return response()->json([
            'message' => $salePlan ? 'طرح معتبر برای خودرو یافت شد' : 'هیچ طرح فعالی برای این خودرو وجود ندارد',
            'salePlan' => $salePlan
        ]);
    }

    public function index(Request $request)
    {
        $cars = SalePlan::latest()->paginate(15);
        return response()->json($cars);
    }
    public function store(Request $request, NotificationService $notifications)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate(
                [
                    'title' => 'required|string|min:5',
                    'start_date' => 'required|date',
                    'end_date' => 'required|date'
                ]
            );
            $SalePlans = SalePlan::create($data);
            $carIds = $request->input('car_id', []);
            $min_order_counts = $request->input('min_order_count', []);
            foreach ($carIds as $index => $carId) {
                $min_order_count = $min_order_counts[$index] ?? null;
                $SalePlans->cars()->attach($carId, [
                    'min_order_count' => $min_order_count,
                ]);
            }
            $notifications->create(
                "ثبت طرح فروش",
                "طرح فروش {$SalePlans->title} در سیستم ثبت شد",
                "notification_saleplan",
                ['SalePlans' => $SalePlans->id]
            );
            DB::commit();
            return response()->json([
                'success' => true,
                'data' => $SalePlans,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطا در ثبت طرح',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function show($id)
    {
        $saleplan = SalePlan::findOrFail($id);
        $saleplan->load([
            'cars',
        ]);
        return response()->json([
            'success' => true,
            'data' => $saleplan,
        ]);
    }
    public function update(Request $request, $id, NotificationService $notifications)
    {
        $saleplan = SalePlan::findOrFail($id);
        DB::beginTransaction();
        // اگر تاریخ رد شده باشه و کسی توش ثبت نام کرده باشه نباید ویرایش بشه
        $ex = CarRequest::where('car_id', $car->id)->exists();
        if ($ex) {
            return response()->json([
                'success' => false,
                'message' => 'برای این ماشین درخواستی ثبت شده و قابل حذف نیست'
            ], 403);
        }
        try {
            $data = $request->validate(
                [
                    'title' => 'required|string|min:5',
                    'start_date' => 'required|date',
                    'end_date' => 'required|date'
                ]
            );
            $saleplan->update($data);
            $saleplan->cars()->detach();
            $carIds = $request->input('car_id', []);
            $min_order_counts = $request->input('min_order_count', []);
            foreach ($carIds as $index => $carId) {
                $min_order_count = $min_order_counts[$index] ?? null;
                $saleplan->cars()->attach($carId, [
                    'min_order_count' => $min_order_count,
                ]);
            }
            $notifications->create(
                "ویرایش طرح فروش",
                "طرح فروش {$saleplan->title} در سیستم ویرایش شد",
                "notification_saleplan",
                ['saleplan' => $saleplan->id]
            );


            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $saleplan->load('cars'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'خطا در ویرایش طرح',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id, NotificationService $notifications)
    {

        DB::beginTransaction();
        $saleplan = SalePlan::with(['cars'])->findOrFail($id);
        $ex = CarRequest::where('sale_plan_id', $saleplan->id)->exists();
        if ($ex) {
            return response()->json([
                'success' => false,
                'message' => 'برای این طرح درخواستی ثبت شده و قابل حذف نیست'
            ], 403);
        }
        try {
            $saleplan->cars()->detach();
            $notifications->create(
                "حذف طرح",
                "طرح {$saleplan->title} از سیستم حذف شد",
                "notification_saleplan",
                ['saleplan' => $saleplan->id]
            );
            $saleplan->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'طرح با موفقیت حذف شد'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف طرح',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function frontDetail(SalePlan $saleplan)
    {
        // 1. eager load کامل
        $saleplan->load([
            'cars',
        ]);
        return response()->json([
            'success' => true,
            'data' => $saleplan,
        ]);
    }
}
