<?php

namespace Modules\Cars\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\CarRequest\Models\CarRequest;
use Modules\Cars\Models\Car;
use Modules\Notifications\Services\NotificationService;
use Modules\Cars\Http\Requests\CarsStoreRequest;
use Modules\Cars\Http\Requests\CarsUpdateRequest;
use Modules\Specifications\Models\SpecificationValue;

class CarsController extends Controller
{
    public function index(Request $request)
    {
        $query = Car::with(['category', 'brand']);
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
            $cars = $query->paginate(100);
        } else {

            $cars = $query->paginate(15);
        }
        return response()->json($cars);
    }

    // ذخیره ماشین


    public function store(CarsStoreRequest $request, NotificationService $notifications)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('cars/main', 'public');
            }

            $car = Car::create($data);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('cars/images', 'public');
                    $car->images()->create([
                        'path' => $path,
                    ]);
                }
            }

            $specificationIds = $request->input('specification_id', []);
            $specificationValues = $request->input('specification_value', []);

            foreach ($specificationIds as $index => $specificationId) {

                $value = $specificationValues[$index] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }

                // مقدار از قبل موجود (id)
                if (is_numeric($value)) {

                    $car->specifications()->attach($specificationId, [
                        'specification_value_id' => $value,
                    ]);
                } else {
                    $specValue = SpecificationValue::create([
                        'specification_id' => $specificationId,
                        'value' => $value,
                    ]);

                    $car->specifications()->attach($specificationId, [
                        'specification_value_id' => $specValue->id,
                    ]);
                }
            }

            // نوتیفیکیشن
            $notifications->create(
                "ثبت ماشین",
                "ماشین {$car->title} در سیستم ثبت شد",
                "notification_car",
                ['car' => $car->id]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $car->load('category', 'brand', 'images', 'specifications'),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'خطا در ثبت ماشین',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // نمایش یک ماشین
    public function show(Car $car)
    {
        // eager loading کامل برای جلوگیری از N+1
        $car->load([
            'category',
            'images',
            'brand',
            'specifications.group',
            'specifications.values',
        ]);

        // گروه‌بندی مشخصات بر اساس group
        $groupedSpecifications = $car->specifications
            ->groupBy(fn($spec) => $spec->group->id)
            ->map(function ($specs) {
                $group = $specs->first()->group;

                return [
                    'group_id' => $group->id,
                    'group_title' => $group->title,
                    'items' => $specs->map(function ($spec) {
                        $value = $spec->values->firstWhere(
                            'id',
                            $spec->pivot->specification_value_id
                        );

                        return [
                            'specification_id' => $spec->id,
                            'title' => $spec->title,
                            'value' => $value?->value, // مقدار واقعی
                            'value_id' => $value?->id,
                        ];
                    })->values(),
                ];
            })
            ->values();

        // خروجی نهایی
        return response()->json([
            'success' => true,
            'data' => [
                'car' => $car,
                'specifications' => $groupedSpecifications,
            ],
        ]);
    }


    public function update(CarsUpdateRequest $request, Car $car, NotificationService $notifications)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            /** -------------------------
             *  تصویر اصلی
             * --------------------------*/
            if ($request->hasFile('image')) {
                if ($car->image) {
                    Storage::disk('public')->delete($car->image);
                }

                $data['image'] = $request->file('image')->store('cars/main', 'public');
            } elseif ($request->filled('image') && is_string($request->image)) {
                $data['image'] = $car->image;
            } else {
                if ($car->image) {
                    Storage::disk('public')->delete($car->image);
                }
                $data['image'] = null;
            }

            $car->update($data);

            /** -------------------------
             *  حذف تصاویر انتخاب‌شده
             * --------------------------*/
            if ($request->filled('deleted_images')) {
                $deletedIds = $request->input('deleted_images');
                $oldImages = $car->images()->whereIn('id', $deletedIds)->get();

                foreach ($oldImages as $img) {
                    Storage::disk('public')->delete($img->path);
                    $img->delete();
                }
            }

            /** -------------------------
             *  تصاویر جدید
             * --------------------------*/
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('cars/images', 'public');

                    $car->images()->create([
                        'path'       => $path,
                        'alt'        => $car->title,
                        'sort_order' => $index,
                    ]);
                }
            }

            /** -------------------------
             *  🔥 بروزرسانی مشخصات
             * --------------------------*/

            // حذف تمام مقادیر قبلی
            $car->specifications()->detach();

            $specificationIds    = $request->input('specification_id', []);
            $specificationValues = $request->input('specification_value', []);

            foreach ($specificationIds as $index => $specificationId) {

                $value = $specificationValues[$index] ?? null;

                if ($value === null || $value === '') {
                    continue;
                }

                // مقدار از قبل موجود
                if (is_numeric($value)) {
                    $car->specifications()->attach($specificationId, [
                        'specification_value_id' => $value,
                    ]);
                }
                // مقدار جدید
                else {
                    $specValue = SpecificationValue::create([
                        'specification_id' => $specificationId,
                        'value' => $value,
                    ]);

                    $car->specifications()->attach($specificationId, [
                        'specification_value_id' => $specValue->id,
                    ]);
                }
            }

            /** -------------------------
             *  نوتیفیکیشن
             * --------------------------*/
            $notifications->create(
                "ویرایش ماشین",
                "ماشین {$car->title} در سیستم ویرایش شد",
                "notification_car",
                ['car' => $car->id]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $car->load('category', 'brand', 'images', 'specifications'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'خطا در ویرایش ماشین',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // حذف ماشین
    public function destroy($id, NotificationService $notifications)
    {
        $car = Car::findOrFail($id);
        $ex = CarRequest::where('car_id', $car->id)->exists();
        if ($ex) {
            return response()->json([
                'success' => false,
                'message' => 'برای این ماشین درخواستی ثبت شده و قابل حذف نیست'
            ], 403);
        }
        DB::beginTransaction();

        try {
            // حذف تصویر اصلی
            if ($car->image && Storage::disk('public')->exists($car->image)) {
                Storage::disk('public')->delete($car->image);
            }

            // حذف تصاویر گالری
            foreach ($car->images as $img) {
                if ($img->path && Storage::disk('public')->exists($img->path)) {
                    Storage::disk('public')->delete($img->path);
                }
            }
            // حذف رکوردهای تصاویر
            $car->images()->delete();
            // حذف pivot specifications
            $car->specifications()->detach();
            // نوتیفیکیشن
            $notifications->create(
                "حذف ماشین",
                "ماشین {$car->title} از سیستم حذف شد",
                "notification_car",
                ['car' => $car->id]
            );
            // حذف ماشین
            $car->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'ماشین با موفقیت حذف شد'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف ماشین',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function search(Request $request)
    {
        $query = Car::with(['category', 'brand']);
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }
        $cars = $query->take(15)->get();
        return response()->json($cars);
    }
    public function frontIndex(Request $request)
    {
        $query = Car::with(['category', 'brand'])->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->get('category_id')) {
            $query->whereHas('category_id', $categoryId);
        }
        if ($brandId = $request->get('brand_id')) {
            $query->whereHas('brand_id', $brandId);
        }

        if ($minPrice = $request->get('min_price')) {
            $query->where(function ($q) use ($minPrice) {
                $q->where('price', '>=', $minPrice);
            });
        }

        if ($maxPrice = $request->get('max_price')) {
            $query->where(function ($q) use ($maxPrice) {
                $q->where('price', '<=', $maxPrice);
            });
        }

        $cars = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'لیست ماشینات',
            'data'    => $cars,
        ]);
    }
    public function frontDetail($id)
    {
        $car = Car::findOrFail($id);
        // 1. eager load کامل
        $car->load([
            'category:id,title,slug',
            'brand:id,title',
            'images:id,car_id,path',
            'specifications:id,title,group_id',
            'specifications.group:id,title',
            'specifications.values:id,specification_id,value',
        ]);

        // 2. ساخت جدول مشخصات گروه‌بندی‌شده
        $specificationTable = $car->specifications
            ->groupBy(fn($spec) => $spec->group->id)
            ->map(function ($specs) {
                $group = $specs->first()->group;

                return [
                    'group_id' => $group->id,
                    'group_title' => $group->title,
                    'rows' => $specs->map(function ($spec) {
                        $value = $spec->values->firstWhere(
                            'id',
                            $spec->pivot->specification_value_id
                        );

                        return [
                            'specification_id' => $spec->id,
                            'title' => $spec->title,
                            'value' => $value?->value,
                            'value_id' => $value?->id,
                        ];
                    })->values(),
                ];
            })
            ->values();

        // 3. خروجی نهایی API
        return response()->json([
            'success' => true,
            'data' => [
                'car' => [
                    'id' => $car->id,
                    'title' => $car->title,
                    'slug' => $car->slug,
                    'brand' => $car->brand,
                    'category' => $car->category,
                    'description' => $car->description,
                    'images' => $car->images,
                ],
                'specifications' => $specificationTable,
            ],
        ]);
    }
}
