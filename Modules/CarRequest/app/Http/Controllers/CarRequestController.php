<?php

namespace Modules\CarRequest\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\CarRequest\Models\CarRequest;
use Modules\Cars\Models\Car;
use Modules\Notifications\Services\NotificationService;
use Modules\SalesPlan\Models\SalePlan;
use Modules\Users\Models\User;

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
        $smsService = new SmsService();
        $smsText = "درخواست شما با موفقیت ثبت شد و نتیجه آن به زودی به شما اطلاع رسانی خواهد شد\n تکین آراز پرگاس";
        $smsService->sendText($user->mobile, $smsText);
        // 5. کاهش موجودی خودرو در طرح
        DB::table('sales_plan_cars')
            ->where('id', $salePlanCar->id)
            ->decrement('min_order_count', 1);

        return response()->json([
            'message' => 'درخواست شما با موفقیت ثبت شد و به زودی با شما ارتباط حاصل خواهد شد',
            'success' => true
        ]);
    }
    public function index(Request $request)
    {
        $query = CarRequest::with(['car', 'user', 'sale_plan']);
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
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $requests = $query->latest()->paginate($request->get('per_page', 15));
        return response()->json($requests);
    }


    public function changeStatus(Request $request, $id, NotificationService $notifications)
    {
        $validated_data = $request->validate([
            'status' => 'required|in:pending,pre_registration,invitation_to_attend,delivered'
        ]);
        $car_request = CarRequest::with(['user', 'car'])->findOrFail($id);
        $car_request->update([
            'status' => $validated_data['status']
        ]);
        $notifications->create(
            "  وضعیت درخواست خودرو",
            "وضعیت درخواست خودرو {$car_request->car->name} در سیستم برای کاربر {$car_request->user->full_name} تغییر کرد  شد",
            "notification_saleplan",
            ['car_request' => $car_request->id]
        );
        $smsService = new SmsService();
        $new_status_label = $this->statusLabel($validated_data['status']);
        $smsText = "وضعیت درخواست شما به {$new_status_label} تغییر کرد \n تکین آراز پرگاس";
        $smsService->sendText($car_request->user->mobile, $smsText);
        return response()->json([
            'message' => 'وضعیت با موفقیت تغییر کرد',
            'success' => true
        ]);
    }
    public static function statusLabel($status)
    {
        $labels = [
            'pending' => 'در حال بررسی',
            'pre_registration' => 'پیش ثبت نام',
            'invitation_to_attend' => 'مراجعه حضوری',
            'delivered' => 'تحویل شده',
        ];
        return $labels[$status] ?? $status;
    }
    public function store(Request $request, NotificationService $notifications)
    {
        $validated_data = $request->validate([
            'status' => 'required|in:pending,pre_registration,invitation_to_attend,delivered',
            'sale_plan_id' => 'required|integer|exists:sales_plans,id',
            'user_id' => 'required|integer|exists:users,id',
            'car_id' => 'required|integer|exists:cars,id'

        ]);
        $car = Car::findOrFail($validated_data['car_id']);
        $validated_data['price'] = $car->price;
        $salePlan = SalePlan::findOrFail($validated_data['sale_plan_id']);
        $user = User::findOrFail($validated_data['user_id']);
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
        $exists = CarRequest::where('sale_plan_id', $salePlan->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'کاربر قبلاً در این طرح ثبت‌نام کرده‌ و امکان ثبت‌نام مجدد وجود ندارد',
                'success' => false
            ], 400);
        }
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
        $admin = $request->user();
        $car_request = CarRequest::create($validated_data);
        DB::table('sales_plan_cars')
            ->where('id', $salePlanCar->id)
            ->decrement('min_order_count', 1);
        $notifications->create(
            "ثبت درخواست خودرو",
            "یک درخواست خودرو به وسیله {$admin->full_name} ثبت شد",
            "notification_saleplan",
            ['car_request' => $car_request->id]
        );
        return response()->json([
            'message' => 'درخواست ثبت خودرو با موفقیت انجام شد',
            'success' => true,
            'data' => $car_request
        ]);
    }
    public function update(Request $request, $id, NotificationService $notifications)
    {
        $car_request = CarRequest::with(['car', 'user', 'sale_plan'])->findOrFail($id);
        $validated_data = $request->validate([
            'car_id' => 'required|integer|exists:cars,id'

        ]);
        $new_car = Car::findOrFail($validated_data['car_id']);

        $salePlanCar = DB::table('sales_plan_cars')
            ->where('sale_plan_id', $car_request->sale_plan_id)
            ->where('car_id', $new_car->id)
            ->first();

        $old_salePlanCar = DB::table('sales_plan_cars')
            ->where('sale_plan_id', $car_request->sale_plan_id)
            ->where('car_id', $car_request->car_id)
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

        DB::transaction(function () use ($old_salePlanCar, $salePlanCar) {
            DB::table('sales_plan_cars')
                ->where('id', $old_salePlanCar->id)
                ->increment('min_order_count', 1);
            DB::table('sales_plan_cars')
                ->where('id', $salePlanCar->id)
                ->decrement('min_order_count', 1);
        });

        $admin = $request->user();
        $car_request->update([
            'car_id' => $new_car->id,
            'price'  => $new_car->price,
        ]);

        $notifications->create(
            "ویرایش درخواست خودرو",
            "یک درخواست خودرو به وسیله {$admin->full_name} ویرایش شد",
            "notification_saleplan",
            ['car_request' => $car_request->id]
        );
        return response()->json([
            'message' => 'درخواست ثبت خودرو با موفقیت ویرایش شد',
            'success' => true,
        ]);
    }
}
