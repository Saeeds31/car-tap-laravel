<?php

namespace Modules\Banners\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Banners\Http\Requests\BannerStoreRequest;
use Modules\Banners\Http\Requests\BannerUpdateRequest;
use Modules\Banners\Models\Banner;
use Modules\Notifications\Services\NotificationService;

class BannersController extends Controller
{

    public function index()
    {
        $banners = Banner::get();

        return response()->json([
            'data' => $banners,
        ]);
    }

    /**
     * ایجاد بنر جدید
     */
    public function store(BannerStoreRequest $request,NotificationService $notifications)
    {
        $data = $request->validated();
        if ($request->hasFile('image_desktop')) {
            $path = $request->file('image_desktop')->store('banners', 'public');
            $data['image_desktop'] = $path;
        }
        if ($request->hasFile('image_mobile')) {
            $path = $request->file('image_mobile')->store('banners', 'public');
            $data['image_mobile'] = $path;
        }
        $banner = Banner::create($data);
        $notifications->create(
            " ثبت بنر",
            "بنر {$banner->title} در سیستم ثبت  شد",
            "notification_content",
            ['banner' => $banner->id]
        );
        return response()->json([
            'message' => 'Banner created successfully',
            'data'    => $banner,
        ], 201);
    }

    /**
     * نمایش یک بنر
     */
    public function show(Banner $banner)
    {
        return response()->json([
            'data' => $banner,
        ]);
    }

    /**
     * ویرایش یک بنر
     */
    public function update(BannerStoreRequest $request, $id,NotificationService $notifications)
    {

        $banner = Banner::findOrFail($id);
        $data = $request->validated();
        if ($request->hasFile('image_desktop')) {
            if ($banner->image_desktop) {
                Storage::disk('public')->delete($banner->image_desktop);
            }
            $path = $request->file('image_desktop')->store('banners', 'public');
            $data['image_desktop'] = $path;
        }
        if ($request->hasFile('image_mobile')) {
            if ($banner->image_mobile) {
                Storage::disk('public')->delete($banner->image_mobile);
            }
            $path = $request->file('image_mobile')->store('articles', 'public');
            $data['image_mobile'] = $path;
        }
        $banner->update($data);
        $notifications->create(
            " ویرایش بنر",
            "بنر {$banner->title} در سیستم ویرایش  شد",
            "notification_content",
            ['banner' => $banner->id]
        );
        return response()->json([
            'message' => 'Banner updated successfully',
            'data'    => $banner,
        ]);
    }

    /**
     * حذف یک بنر
     */
    public function destroy(Banner $banner,NotificationService $notifications)
    {
        // حذف فایل‌ها از storage
        if ($banner->image_desktop) {
            Storage::disk('public')->delete($banner->image_desktop);
        }
        if ($banner->image_mobile) {
            Storage::disk('public')->delete($banner->image_mobile);
        }
        $notifications->create(
            " حذف بنر",
            "بنر {$banner->title} از سیستم حذف  شد",
            "notification_content",
            ['banner' =>null]
        );
        $banner->delete();
        return response()->json([
            'message' => 'Banner deleted successfully',
        ]);
    }
}
