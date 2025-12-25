<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Notifications\Services\NotificationService;
use Modules\Users\Http\Requests\UserStoreRequest;
use Modules\Users\Http\Requests\UserUpdateRequest;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use Modules\Users\Models\Validity;
use Modules\Wallet\Models\Wallet;

class UsersController extends Controller
{


    public function adminInfo(Request $request)
    {
        $user = $request->user();
        $permissions = $user->permissions;
        return response()->json([
            'message' => 'اطلاعات ادمین',
            'user' => $user,
            'permissions' => $permissions
        ]);
    }
    public function index(Request $request)
    {
        $query = User::whereHas('roles', function ($q) {
            $q->where('slug', 'customer');
        });

        if ($fullName = $request->get('full_name')) {
            $query->where('full_name', 'like', "%{$fullName}%");
        }

        if ($mobile = $request->get('mobile')) {
            $query->where('mobile', 'like', "%{$mobile}%");
        }

        if ($nationalCode = $request->get('national_code')) {
            $query->where('national_code', 'like', "%{$nationalCode}%");
        }

        if ($birthDate = $request->get('birth_date')) {
            $query->whereDate('birth_date', $birthDate);
        }

        if ($provinceId = $request->get('province_id')) {
            $query->whereHas('city', function ($q) use ($provinceId) {
                $q->where('province_id', $provinceId);
            });
        }

        return response()->json($query->paginate(20));
    }



    public function userProfile(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'message' => 'پروفایل کاربر',
            'success' => true,
            'user' => $user->load(['city',  'wallet'])
        ]);
    }
    public function managerIndex()
    {
        $users = User::with(['roles', 'addresses', 'wallet'])
            ->whereHas('roles', function ($query) {
                $query->whereNotIn('slug', ['customer', 'super admin']);
            })
            ->get();
        return response()->json($users);
    }
    // ساخت کاربر جدید
    public function store(UserStoreRequest $request, NotificationService $notifications)
    {
        $data = $request->validated();
        $customerRoleId = Role::where('slug', 'customer')->value('id');
        if (!$customerRoleId) {
            return response()->json([
                'message' => 'نقش پیشفرض مشتری وجود ندارد لطفا این نقش را در دیتابیس تعریف کنید'
            ], 422);
        }
        $user = User::create($data);
        Wallet::create([
            'user_id' => $user->id,
            'balance' =>  0,
        ]);
        $user->roles()->sync([$customerRoleId]);
        $notifications->create(
            "ساخت کاربر",
            "یک کاربر در سیستم ثبت شد {$user->full_name}",
            "notification_users",
            ['users' => null]
        );
        return response()->json($user->load(['roles', 'addresses', 'wallet']), 201);
    }

    // نمایش یک کاربر
    public function show(User $user)
    {
        return response()->json($user->load(['roles', 'wallet', 'city.province']));
    }

    // ویرایش کاربر
    public function update(UserUpdateRequest $request, User $user)
    {
        $data = $request->validated();
        if (isset($data['mobile'])) {
            unset($data['mobile']);
        }
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);
        return response()->json($user->load(['roles', 'addresses', 'wallet']));
    }

    // حذف کاربر
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
