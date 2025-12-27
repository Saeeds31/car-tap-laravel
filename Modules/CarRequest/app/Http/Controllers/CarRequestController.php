<?php

namespace Modules\CarRequest\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CarRequest\Models\CarRequest;
use Modules\Cars\Models\Car;

class CarRequestController extends Controller
{
    public function storeRequest(Request $request, $saleId)
    {
        $user = $request->user();
        $data = $request->validate([
            'car_id' => 'required|integer',
        ]);
        $car = Car::findOrFail($data['car_id']);
        $ex=CarRequest::where('car_id',$car->id)->where();
        $saleRequest = CarRequest::create([
            'sale_plan_id' => $saleId,
            'user_id' => $user->id,
            'car_id' => $car->id,
            'price' => $car->price,
            'status' => 'pending'
        ]);
        return response()->json([
            'message'=>'درخواست شما با موفقیت ثبت شد و به زودی با شما ارتباط حاصل خواهد شد',
            'success'=>true
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('carrequest::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('carrequest::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('carrequest::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('carrequest::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
