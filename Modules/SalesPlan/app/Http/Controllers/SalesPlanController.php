<?php

namespace Modules\SalesPlan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Services\NotificationService;
use Modules\SalesPlan\Models\SalePlan;

class SalesPlanController extends Controller
{

    public function index(Request $request)
    {
        $cars = SalePlan::latest()->paginate(15);
        return response()->json($cars);
    }

    // ذخیره ماشین


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

    // نمایش یک ماشین
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
