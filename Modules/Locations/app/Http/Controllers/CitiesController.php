<?php

namespace Modules\Locations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Locations\Http\Requests\CityStoreRequest;
use Modules\Locations\Http\Requests\CityUpdateRequest;
use Modules\Locations\Http\Requests\ProvinceStoreRequest;
use Modules\Locations\Http\Requests\ProvinceUpdateRequest;
use Modules\Locations\Models\City;
use Modules\Locations\Models\Province;
use Modules\Notifications\Services\NotificationService;

class CitiesController extends Controller
{
    /**
     * Display a listing of the cities with pagination.
     */
    public function index(Request $request)
    {

        $query = City::with('province');
        if ($province_id = $request->get('province_id')) {
            $query->where('province_id', $province_id);
        }
        $cities = $query->paginate(50);

        return response()->json([
            'success' => true,
            'message' => 'لیست شهرها',
            'data'    => $cities
        ]);
    }
    public function cityAll(Request $request)
    {

        $cities = City::with('province')->orderBy('id')->get();
        return response()->json([
            'success' => true,
            'message' => 'لیست شهرها',
            'data'    => $cities
        ]);
    }
    public function provinceAll(Request $request)
    {

        $provinces = Province::orderBy('id')->get();
        return response()->json([
            'success' => true,
            'message' => 'لیست استان ها',
            'data'    => $provinces
        ]);
    }

    /**
     * Store a newly created city in storage.
     */
    public function store(CityStoreRequest $request, NotificationService $notifications)
    {
        $data = $request->validated();

        $city = City::create($data);

        $notifications->create(
            "ثبت شهر",
            "شهر {$city->name}  در سیستم ثبت شد",
            "notification_users",
            ['city' => $city->id]
        );
        return response()->json([
            'success' => true,
            'message' => 'شهر با موفقیت ذخیره شد',
            'data'    => $city->load('province')
        ], 201);
    }

    /**
     * Display the specified city.
     */
    public function show(City $city)
    {
        return response()->json([
            'success' => true,
            'message' => 'جزئیات یک شهر',
            'data'    => $city->load('province')
        ]);
    }

    /**
     * Update the specified city in storage.
     */
    public function update(CityUpdateRequest $request, City $city, NotificationService $notifications)
    {
        $data = $request->validated();

        $city->update($data);

        $notifications->create(
            "ویرایش شهر",
            "شهر {$city->name}  در سیستم ویرایش شد",
            "notification_users",
            ['city' => $city->id]
        );
        return response()->json([
            'success' => true,
            'message' => 'شهر با موفقیت ویرایش شد',
            'data'    => $city->load('province')
        ]);
    }

    /**
     * Remove the specified city from storage.
     */
    public function destroy(City $city, NotificationService $notifications)
    {
        $city->delete();

        $notifications->create(
            "حذف شهر",
            "شهر {$city->name}  از سیستم حذف شد",
            "notification_users",
            ['city' => $city->id]
        );
        return response()->json([
            'success' => true,
            'message' => 'شهر با موفقیت حذف شد'
        ]);
    }
}
