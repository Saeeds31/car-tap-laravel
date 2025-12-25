<?php

namespace Modules\Team\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Notifications\Services\NotificationService;
use Modules\Team\Http\Requests\TeamStoreRequest;
use Modules\Team\Http\Requests\TeamUpdateRequest;
use Modules\Team\Models\Team;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $teams = Team::orderBy('sort_order')->get();
        return response()->json([
            'message' => 'لیست نفرات تیم',
            'data' => $teams,
            'success' => true
        ]);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(TeamStoreRequest $request,NotificationService $notifications)
    {
        $validated_data = $request->validated();
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('teams', 'public');
            $validated_data['image'] = $path;
        }
        $member = Team::create($validated_data);
        $notifications->create(
            "ثبت عضو ",
            "عضو  {$member->full_name}  در سیستم ثبت شد",
            "notification_users",
            ['member' => $member->id]
        );
        return response()->json([
            'message' => 'با موفقیت ذخیره شد',
            'data' => $member,
            'success' => true
        ]);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $member = Team::findOrFail($id);
        return response()->json([
            'message' => 'جزئیات نفرات',
            'data' => $member,
            'success' => true
        ]);
    }


    public function update(TeamUpdateRequest $request, $id,NotificationService $notifications)
    {
        $validated_data = $request->validated();
        $member = Team::findOrFail($id);
        if ($request->hasFile('image')) {
            if ($member->image) {
                Storage::disk('public')->delete($member->image);
            }
            $path = $request->file('image')->store('teams', 'public');
            $validated_data['image'] = $path;
        }
        $member->update($validated_data);
        $notifications->create(
            "ویرایش عضو",
            "عضو {$member->full_name}  در سیستم ویرایش شد",
            "notification_users",
            ['member' => $member->id]
        );
        return response()->json([
            'message' => 'با موفقیت ویرایش شد',
            'data' => $member,
            'success' => true
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id,NotificationService $notifications)
    {
        $member = Team::find($id);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر پیدا نشد',
            ], 404);
        }

        // Delete image if exists
        if ($member->image) {
            Storage::disk('public')->delete($member->image);
        }
        $notifications->create(
            "حذف عضو",
            "عضو {$member->full_name}  از سیستم حذف شد",
            "notification_users",
            ['member' => $member->id]
        );
        $member->delete();
        return response()->json([
            'success' => true,
            'message' => 'کاربر با موفقیت حذف شد',
        ]);
    }
}
