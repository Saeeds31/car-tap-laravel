<?php

namespace Modules\Articles\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Articles\Models\Article;
use Modules\Articles\Http\Requests\ArticleStoreRequest;
use Modules\Articles\Http\Requests\ArticleUpdateRequest;
use Illuminate\Support\Facades\Storage;
use Modules\Notifications\Services\NotificationService;

class ArticlesController extends Controller
{
    // List articles with pagination
    public function index(Request $request)
    {

        $query = Article::with('categories', 'author');

        if ($province_id = $request->get('province_id')) {
            $query->where('province_id', "%{$province_id}%");
        }
        $articles = $query->latest('id')->paginate(20);
        return response()->json([
            'success' => true,
            'message' => 'Articles retrieved successfully',
            'data'    => $articles
        ]);
    }

    // Show single article
    public function show($id)
    {
        $article = Article::with('categories', 'author', 'comments')->find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Article retrieved successfully',
            'data'    => $article
        ]);
    }

    // Store new article
    public function store(ArticleStoreRequest $request, NotificationService $notifications)
    {
        $data = $request->validated();

        // Set author_id from authenticated user
        $data['author_id'] = $request->user()->id;

        // Handle image upload if exists
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('articles', 'public');
            $data['image'] = $path;
        }

        $article = Article::create($data);

        // Sync categories
        if (!empty($data['category_ids'])) {
            $article->categories()->sync($data['category_ids']);
        }
        $notifications->create(
            " ثبت مقاله",
            "مقاله {$article->title} در سیستم ثبت  شد",
            "notification_content",
            ['article' => $article->id]
        );
        return response()->json([
            'success' => true,
            'message' => 'مقاله با موفقیت ثبت شد',
            'data'    => $article->load('categories', 'author')
        ], 201);
    }


    // Update article
    public function update(ArticleUpdateRequest $request, $id, NotificationService $notifications)
    {
        $article = Article::findOrFail($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found',
            ], 404);
        }

        $data = $request->validated();

        // Handle image upload if exists
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($article->image) {
                Storage::disk('public')->delete($article->image);
            }
            $path = $request->file('image')->store('articles', 'public');
            $data['image'] = $path;
        }

        $article->update($data);
        $notifications->create(
            " ویرایش مقاله",
            "مقاله {$article->title} در سیستم ویرایش  شد",
            "notification_content",
            ['article' => $article->id]
        );
        // Sync categories
        if (isset($data['category_ids'])) {
            $article->categories()->sync($data['category_ids']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Article updated successfully',
            'data'    => $article->load('categories', 'author')
        ]);
    }

    // Delete article
    public function destroy($id, NotificationService $notifications)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found',
            ], 404);
        }

        // Delete image if exists
        if ($article->image) {
            Storage::disk('public')->delete($article->image);
        }
        $notifications->create(
            " حذف مقاله",
            "مقاله {$article->title} از سیستم حذف  شد",
            "notification_content",
            ['article' => null]
        );
        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article deleted successfully',
        ]);
    }
}
