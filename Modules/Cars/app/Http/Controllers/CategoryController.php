<?php

namespace Modules\Cars\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Cars\Models\Car;
use Modules\Cars\Models\Category;
use Modules\Notifications\Services\NotificationService;

class CategoryController extends Controller
{
    public function index(Request $request)
    {

        $categories = Category::latest('id')->paginate(20);
        return response()->json([
            'success' => true,
            'message' => 'لیست دسته بندی های خودرو',
            'data'    => $categories
        ]);
    }

    // Show single category
    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'دسته بندی پیدا نشد',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'جزئیات دسته بندی',
            'data'    => $category
        ]);
    }

    // Store new category
    public function store(Request $request, NotificationService $notifications)
    {
        $data = $request->validate([
            'title' => 'required|string|min:3',
            'description' => 'nullable|string|min:10',
        ]);

        $category = Category::create($data);
        $notifications->create(
            " ثبت دسته بندی خودرو",
            "دسته بندی خودرو {$category->title} در سیستم ثبت  شد",
            "notification_content",
            ['category' => $category->id]
        );
        return response()->json([
            'success' => true,
            'message' => 'دسته بندی خودرو با موفقیت ثبت شد',
            'data'    => $category
        ], 201);
    }


    // Update category
    public function update(Request $request, $id, NotificationService $notifications)
    {
        $category = Category::findOrFail($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'دسته بندی پیدا نشد',
            ], 404);
        }

        $data = $request->validate([
            'title' => 'required|string|min:3',
            'description' => 'nullable|string|min:10',
        ]);

        $category->update($data);
        $notifications->create(
            " ویرایش دسته بندی خودرو",
            "دسته بندی خودرو {$category->title} در سیستم ویرایش  شد",
            "notification_content",
            ['category' => $category->id]
        );
        return response()->json([
            'success' => true,
            'message' => 'category updated successfully',
            'data'    => $category
        ]);
    }

    // Delete category
    public function destroy($id, NotificationService $notifications)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'دسته بندی پیدا نشد',
            ], 404);
        }
        $cars = Car::where('category_id', $category->id)->exists();
        if ($cars) {
            return response()->json([
                'success' => true,
                'message' => 'این دسته بندی قابل حذف نیست و برای آن ماشین ثبت شده است',
            ], 403);
        }
        $notifications->create(
            " حذف دسته بندی خودرو",
            "دسته بندی خودرو {$category->title} از سیستم حذف  شد",
            "notification_content",
            ['category' => null]
        );
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'category deleted successfully',
        ]);
    }
}
