<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Locations\Models\City;
use Modules\Users\Models\Permission;

class PermissionController extends Controller
{

    public function CityIndex()
    {
        $permissions = Permission::where('name', 'like', 'citymanager_%')->get();
        return response()->json([
            'success' => true,
            'message' => "لیست دسترسی شهری",
            'data' => $permissions
        ]);
    }


    public function CityStore(Request $request)
    {
        $validated_data = $request->validate([
            'city_id' => 'required|integer|exists:cities,id'
        ]);

        $city = City::find($validated_data['city_id']);

        // چک کردن تکراری نبودن
        $permissionName = "citymanager_{$city->id}";

        $exists = Permission::where('name', $permissionName)->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'این دسترسی قبلاً ایجاد شده است'
            ], 422);
        }

        // ساخت دسترسی جدید
        $citypermission = Permission::create([
            'name' => $permissionName,
            'label' => "دسترسی شهر {$city->name}"
        ]);

        return response()->json([
            'success' => true,
            'message' => 'با موفقیت ذخیره شد',
            'data' => $citypermission
        ]);
    }

    public function CityDelete($id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'پرمیژن مورد نظر پیدا نشد'
            ], 404);
        }
        if (!str_starts_with($permission->name, 'citymanager_')) {
            return response()->json([
                'success' => false,
                'message' => 'این پرمیژن قابل حذف نیست'
            ], 422);
        }
        $permission->delete();
        return response()->json([
            'success' => true,
            'message' => 'با موفقیت حذف شد'
        ]);
    }
}
