<?php

namespace Modules\Specifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Notifications\Services\NotificationService;
use Modules\Products\Models\Product;
use Modules\Specifications\Http\Requests\StoreSpecificationRequest;
use Modules\Specifications\Http\Requests\UpdateSpecificationRequest;
use Modules\Specifications\Models\CarSpecification;
use Modules\Specifications\Models\Specification;
use Modules\Specifications\Models\SpecificationGroup;
use Modules\Specifications\Models\SpecificationValue;

class SpecificationsController extends Controller
{
    public function specificationTree()
    {
        $groups = SpecificationGroup::with([
            'specifications:id,title,group_id',
            'specifications.values:id,specification_id,value',
        ])->get();

        $result = $groups->map(function ($group) {
            return [
                'id' => $group->id,
                'title' => $group->title,
                'specifications' => $group->specifications->map(function ($spec) {
                    return [
                        'id' => $spec->id,
                        'title' => $spec->title,
                        'values' => $spec->values->map(function ($value) {
                            return [
                                'id' => $value->id,
                                'value' => $value->value,
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
    /**
     * Store a new specification with its values
     */
    public function store(StoreSpecificationRequest $request, NotificationService $notifications)
    {
        // ایجاد مشخصه
        $spec = Specification::create([
            'title' => $request->title,
            'group_id' => $request->group_id,
        ]);

        // ذخیره مقادیر
        if ($request->has('values')) {
            foreach ($request->values as $value) {
                if ($value && trim($value) !== '') {
                    SpecificationValue::create([
                        'specification_id' => $spec->id,
                        'value' => $value,
                    ]);
                }
            }
        }
        $notifications->create(
            "ثبت جدول مشخصات",
            "جدول مشخصات {$spec->title} در سیستم ثبت شد",
            "notification_product",
            ['specification' => $spec->id]
        );
        return response()->json([
            'message' => 'جدول مشخصات با موفقیت ثبت شد',
            'data' => $spec->load('values'),
        ], 201);
    }

    /**
     * Update an existing specification and its values
     */
    public function update(UpdateSpecificationRequest $request, $id, NotificationService $notifications)
    {
        $spec = Specification::with('values')->findOrFail($id);

        // آپدیت عنوان
        $spec->update([
            'title' => $request->title,
            'group_id' => $request->group_id
        ]);

        $newValues = collect($request->input('values', []))
            ->filter(fn($v) => trim($v) !== '');

        $existingValues = $spec->values->pluck('value', 'id'); 
        // [id => value]

        /**
         * 1️⃣ حذف valueهایی که دیگه وجود ندارن
         */
        foreach ($existingValues as $valueId => $valueText) {
            if (!$newValues->contains($valueText)) {

                // آیا در car_specification استفاده شده؟
                $used = CarSpecification::where('specification_value_id', $valueId)->exists();

                if (!$used) {
                    SpecificationValue::where('id', $valueId)->delete();
                }
            }
        }

        /**
         * 2️⃣ اضافه کردن valueهای جدید
         */
        foreach ($newValues as $value) {
            if (!$existingValues->contains($value)) {
                SpecificationValue::create([
                    'specification_id' => $spec->id,
                    'value' => $value,
                ]);
            }
        }

        $notifications->create(
            "ویرایش جدول مشخصات",
            "جدول مشخصات {$spec->title} در سیستم ویرایش شد",
            "notification_product",
            ['specification' => $spec->id]
        );

        return response()->json([
            'message' => 'جدول مشخصات با موفقیت ویرایش شد',
            'data' => $spec->load('values'),
        ]);
    }

    /**
     * Show a specification with its values
     */
    public function show($id)
    {
        $spec = Specification::findOrFail($id);
        return response()->json($spec->load('values'));
    }

    /**
     * Delete a specification and its values
     */ public function destroy($id, NotificationService $notifications)
    {
        $spec = Specification::with('values')->findOrFail($id);

        // بررسی اینکه آیا این مشخصات در ماشین‌ها استفاده شده یا نه
        $used = CarSpecification::whereIn(
            'specification_value_id',
            $spec->values->pluck('id')
        )->exists();

        if ($used) {
            return response()->json([
                'message' => 'این جدول مشخصات در حال استفاده است و امکان حذف آن وجود ندارد',
            ], 422);
        }

        // حذف مقادیر
        $spec->values()->delete();

        // حذف خود مشخصات
        $spec->delete();

        $notifications->create(
            "حذف جدول مشخصات",
            "جدول مشخصات {$spec->title} از سیستم حذف شد",
            "notification_product",
            ['specification' => $spec->id]
        );

        return response()->json([
            'message' => 'جدول مشخصات با موفقیت حذف شد',
        ]);
    }


    /**
     * List all specifications with their values
     */
    public function index()
    {
        $data = Specification::with('values')->paginate(25);
        return response()->json(
            [
                'message' => "جدول مشخصات",
                'success' => true,
                'data' => $data
            ]
        );
    }
    public function allSpecifications()
    {
        $data = Specification::with('values')->get();
        return response()->json(
            [
                'message' => "جدول مشخصات",
                'success' => true,
                'data' => $data
            ]
        );
    }
    public function syncSpecifications(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        // پاک کردن تمام روابط قبلی برای این محصول در جدول واسط
        $product->specifications()->detach();
        // اضافه کردن مقادیر جدید
        foreach ($request->specifications as $spec) {
            $product->specifications()->attach($spec['specification_id'], [
                'specification_value_id' => $spec['specification_value_id'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        // همزمان مقادیر جدید اضافه می‌کنه و مقادیر قبلی رو پاک می‌کنه
        return response()->json(
            [
                'message' => "جدول مشخصات",
                'success' => true,
                'data' => $product,
            ]
        );
    }
}
