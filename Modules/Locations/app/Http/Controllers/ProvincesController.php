<?php

namespace Modules\Locations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Locations\Http\Requests\ProvinceStoreRequest;
use Modules\Locations\Http\Requests\ProvinceUpdateRequest;
use Modules\Locations\Models\Province;
use Modules\Notifications\Services\NotificationService;

class ProvincesController extends Controller
{

    /**
     * Display a listing of the provinces with their cities.
     */
    public function index()
    {
        $provinces = Province::with('cities')->get();

        return response()->json([
            'success' => true,
            'message' => 'Provinces retrieved successfully',
            'data'    => $provinces
        ]);
    }

    /**
     * Store a newly created province.
     */
    public function store(ProvinceStoreRequest $request, NotificationService $notifications)
    {
        $validated = $request->validated();

        $province = Province::create($validated);

        $notifications->create(
            "ثبت استان",
            "استان {$province->name}  در سیستم ثبت شد",
            "notification_users",
            ['province' => $province->id]
        );
        return response()->json([
            'success' => true,
            'message' => 'Province created successfully',
            'data'    => $province
        ], 201);
    }

    /**
     * Display a single province with its cities.
     */
    public function show($id)
    {
        $province = Province::with('cities')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Province retrieved successfully',
            'data'    => $province
        ]);
    }

    /**
     * Update the specified province.
     */
    public function update(ProvinceUpdateRequest $request, $id, NotificationService $notifications)
    {
        $validated = $request->validated();

        $province = Province::findOrFail($id);
        $province->update($validated);

        $notifications->create(
            "ویرایش استان",
            "استان {$province->name}  در سیستم ویرایش شد",
            "notification_users",
            ['province' => $province->id]
        );
        return response()->json([
            'success' => true,
            'message' => 'Province updated successfully',
            'data'    => $province
        ]);
    }

    /**
     * Remove the specified province.
     */
    public function destroy($id, NotificationService $notifications)
    {
        $province = Province::findOrFail($id);

        $notifications->create(
            "حذف استان",
            "استان {$province->name}  از سیستم حذف شد",
            "notification_users",
            ['province' => $province->id]
        );
        $province->delete();

        return response()->json([
            'success' => true,
            'message' => 'Province deleted successfully'
        ]);
    }
}
