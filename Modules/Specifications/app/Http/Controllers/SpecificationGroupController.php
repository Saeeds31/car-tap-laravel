<?php

namespace Modules\Specifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Notifications\Services\NotificationService;
use Modules\Specifications\Models\Specification;
use Modules\Specifications\Models\SpecificationGroup;

class SpecificationGroupController extends Controller
{
    public function index(Request $request)
    {

        $groups = SpecificationGroup::latest('id')->paginate(20);
        return response()->json([
            'success' => true,
            'message' => 'لیست گروه ها',
            'data'    => $groups
        ]);
    }

    // Show single group
    public function show($id)
    {
        $group = SpecificationGroup::find($id);

        if (!$group) {
            return response()->json([
                'success' => false,
                'message' => 'گروه پیدا نشد',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'جزئیات یک گروه ',
            'data'    => $group
        ]);
    }

    // Store new group
    public function store(Request $request, NotificationService $notifications)
    {
        $data = $request->validate([
            'title' => 'required|string|min:3',
        ]);

        $group = SpecificationGroup::create($data);

        $notifications->create(
            " ثبت گروه ",
            "گروه  {$group->title} در سیستم ثبت  شد",
            "notification_car",
            ['group' => $group->id]
        );
        return response()->json([
            'success' => true,
            'message' => 'گروه  با موفقیت ثبت شد',
            'data'    => $group
        ], 201);
    }


    // Update group
    public function update(Request $request, $id, NotificationService $notifications)
    {
        $group = SpecificationGroup::findOrFail($id);

        if (!$group) {
            return response()->json([
                'success' => false,
                'message' => 'گروه  پیدا نشد',
            ], 404);
        }

        $data = $request->validate([
            'title' => 'required|string|min:3',
        ]);

        $group->update($data);
        $notifications->create(
            " ویرایش گروه ",
            "گروه  {$group->title} در سیستم ویرایش  شد",
            "notification_car",
            ['group' => $group->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'گروه  با موفقیت ویرایش شد',
            'data'    => $group
        ]);
    }

    // Delete group
    public function destroy($id, NotificationService $notifications)
    {
        $group = SpecificationGroup::find($id);

        if (!$group) {
            return response()->json([
                'success' => false,
                'message' => 'گروه  پیدا نشد',
            ], 404);
        }
        $items = Specification::where('group_id', $group->id)->exists();
        if ($items) {
            return response()->json([
                'success' => true,
                'message' => 'این گروه  قابل حذف نیست و برای آن آیتم ثبت شده است',
            ], 403);
        }
        // Delete image if exists
        $notifications->create(
            " حذف گروه ",
            "گروه  {$group->title} از سیستم حذف  شد",
            "notification_car",
            ['group' => null]
        );
        $group->delete();

        return response()->json([
            'success' => true,
            'message' => 'گروه  با موفقیت حذف شد',
        ]);
    }
}
