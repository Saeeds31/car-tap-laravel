<?php

namespace Modules\ArticleCategories\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ArticleCategories\Models\ArticleCategory;
use Modules\ArticleCategories\Http\Requests\ArticleCategoryStoreRequest;
use Modules\ArticleCategories\Http\Requests\ArticleCategoryUpdateRequest;
use Modules\Notifications\Services\NotificationService;

class ArticleCategoriesController extends Controller
{
    private function getAllChildrenIds($category)
    {
        $ids = [];
        foreach ($category->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getAllChildrenIds($child));
        }

        return $ids;
    }
    // List all categories with pagination
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $categories = ArticleCategory::with('parent', 'children')->latest('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully',
            'data'    => $categories
        ]);
    }
    public function tree()
    {
        $categories = ArticleCategory::with('children')
            ->whereNull('parent_id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Category tree retrieved successfully',
            'data'    => $categories
        ]);
    }
    // Show a single category
    public function show($id)
    {
        $category = ArticleCategory::with('parent', 'children')->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category retrieved successfully',
            'data'    => $category
        ]);
    }

    // Store a new category
    public function store(ArticleCategoryStoreRequest $request, NotificationService $notifications)
    {
        $category = ArticleCategory::create($request->validated());
        $notifications->create(
            " ثبت دسته بندی",
            "دسته بندی {$category->title} در سیستم ثبت  شد",
            "notification_content",
            ['article_category' => $category->id]
        );
        return response()->json([
            'success' => true,
            'message' => 'دسته بندی ثبت شد',
            'data'    => $category
        ], 201);
    }

    // Update a category
    public function update(ArticleCategoryUpdateRequest $request, $id, NotificationService $notifications)
    {
        $category = ArticleCategory::find($id);
        $data = $request->validated();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'دسته بندی پیدا نشد',
            ], 404);
        }
        if (isset($data['parent_id']) && $category->id === $data['parent_id']) {
            return response()->json([
                'success' => false,
                'message' => 'دسته بندی  نمی تواند والد خودش باشد',
            ], 422);
        }
        $childrenIds = $this->getAllChildrenIds($category);

        if (isset($data['parent_id']) && in_array($data['parent_id'], $childrenIds)) {
            return response()->json([
                'success' => false,
                'message' => 'دسته‌بندی نمی‌تواند یکی از فرزندان خودش را به‌عنوان والد انتخاب کند',
            ], 422);
        }
        $category->update($data);
        $notifications->create(
            " ویرایش دسته بندی",
            "دسته بندی {$category->title} در سیستم ویرایش  شد",
            "notification_content",
            ['article_category' => $category->id]
        );
        return response()->json([
            'success' => true,
            'message' => 'دسته بندی با موفقیت بروز رسانی شد',
            'data'    => $category
        ]);
    }

    // Delete a category
    public function destroy($id, NotificationService $notifications)
    {
        $category = ArticleCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'دسته بندی پیدا نشد',
            ], 404);
        }
        $notifications->create(
            " حذف دسته بندی",
            "دسته بندی {$category->title} از سیستم حذف  شد",
            "notification_content",
            ['article_category' => null]
        );
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'دسته بندی با موفقیت حذف شد',
        ]);
    }
}
