<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Users\Models\User;

class DashboardController extends Controller
{

    public function dashboard()
    {

        return response()->json(
            [
                'message' => 'dashboard content',
                'success' => true,
                'data' => [
                    'users'    => User::dashboardReport(),
                ]
            ]
        );
    }
}
