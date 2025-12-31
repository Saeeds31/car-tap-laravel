<?php

namespace Modules\Cars\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Cars\Models\Brand;
use Modules\Cars\Models\Car;
use Modules\Notifications\Services\NotificationService;

class BrandController extends Controller
{
    public function index(Request $request)
    {

        $brands = Brand::latest('id')->paginate(20);
        return response()->json([
            'success' => true,
            'message' => 'لیست برندها',
            'data'    => $brands
        ]);
    }

    // Show single brand
    public function show($id)
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'برن پیدا نشد',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'جزئیات یک برند',
            'data'    => $brand
        ]);
    }

    // Store new brand
    public function store(Request $request, NotificationService $notifications)
    {
        $data = $request->validate([
            'title' => 'required|string|min:3',
            'show_in_home' => 'nullable|boolean',
            'image' => 'required|file|max:1024',
            'description' => 'nullable|string|min:10',
        ]);

        // Handle image upload if exists
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('brands', 'public');
            $data['image'] = $path;
        }
        $brand = Brand::create($data);
        // Sync categories

        $notifications->create(
            " ثبت برند",
            "برند {$brand->title} در سیستم ثبت  شد",
            "notification_content",
            ['brand' => $brand->id]
        );
        return response()->json([
            'success' => true,
            'message' => 'برند با موفقیت ثبت شد',
            'data'    => $brand
        ], 201);
    }


    // Update brand
    public function update(Request $request, $id, NotificationService $notifications)
    {
        $brand = Brand::findOrFail($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'برند پیدا نشد',
            ], 404);
        }

        $data = $request->validate([
            'title' => 'required|string|min:3',
            'image' => 'nullable|file|max:1024',
            'show_in_home' => 'nullable|boolean',
            'description' => 'nullable|string|min:10',
        ]);

        // Handle image upload if exists
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($brand->image) {
                Storage::disk('public')->delete($brand->image);
            }
            $path = $request->file('image')->store('brands', 'public');
            $data['image'] = $path;
        }

        $brand->update($data);
        $notifications->create(
            " ویرایش برند",
            "برند {$brand->title} در سیستم ویرایش  شد",
            "notification_content",
            ['brand' => $brand->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'برند با موفقیت ویرایش شد',
            'data'    => $brand
        ]);
    }

    // Delete brand
    public function destroy($id, NotificationService $notifications)
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'برند پیدا نشد',
            ], 404);
        }
        $cars = Car::where('brand_id', $brand->id)->exists();
        if ($cars) {
            return response()->json([
                'success' => true,
                'message' => 'این برند قابل حذف نیست و برای آن ماشین ثبت شده است',
            ], 403);
        }
        // Delete image if exists
        if ($brand->image) {
            Storage::disk('public')->delete($brand->image);
        }
        $notifications->create(
            " حذف برند",
            "برند {$brand->title} از سیستم حذف  شد",
            "notification_content",
            ['brand' => null]
        );
        $brand->delete();

        return response()->json([
            'success' => true,
            'message' => 'برند با موفقیت حذف شد',
        ]);
    }
    public function homeBrand()
    {
        $brands = Brand::with(['cars' => function ($query) {
            $query->select('id', 'name', 'price', 'image', 'brand_id', 'category_id')
                ->limit(10)
                ->with(['category' => function ($q) {
                    $q->select('id', 'title');
                }]);
        }])
            ->whereHas('cars')
            ->select('id', 'title', 'description', 'image')
            ->get()
            ->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'title' => $brand->title,
                    'description' => $brand->description,
                    'image' => $brand->image,
                    'cars' => $brand->cars->map(function ($car) {
                        return [
                            'id' => $car->id,
                            'name' => $car->name,
                            'price' => $car->price,
                            'image' => $car->image,
                            'category' => $car->category ? [
                                'id' => $car->category->id,
                                'title' => $car->category->title,
                            ] : null,
                        ];
                    })->values()->all(),
                ];
            })->values()->all();

        return response()->json([
            'data' => $brands,
            'success' => true
        ]);
    }
}
