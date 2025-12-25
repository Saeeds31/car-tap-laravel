<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\Services\NotificationService;
use Modules\Users\Models\Otp;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;
use Modules\Wallet\Models\Wallet;

class AuthController extends Controller
{
    public function checkMobile(Request $request)
    {
        $request->validate([
            'mobile' => [
                'required',
                'regex:/^09\d{9}$/'
            ],
        ], [
            'mobile.required' => 'شماره موبایل الزامی است.',
            'mobile.regex' => 'شماره موبایل معتبر نیست. شماره باید با 09 شروع شده و 11 رقم باشد.',
        ]);
        $user = User::where('mobile', $request->mobile)->first();
        $this->sendOtp($request->mobile);
        if ($user) {
            return response()->json([
                'status' => 'login',
                "success" => true
            ]);
        } else {
            return response()->json([
                'status' => 'register',
                "success" => true
            ]);
        }
    }
    public function sendOtp($mobile)
    {
        $mobile = trim($mobile);
        $token = rand(100000, 999999);
        Otp::updateOrCreate(
            ['mobile' => $mobile],
            ['token' => $token, 'expires_at' => now()->addMinutes(5)]
        );
        $response = Http::get("https://api.kavenegar.com/v1/71705A6858737476417466345933356B3578614C44396154756F6B384833424A3646674B4F4F585577764D3D/verify/lookup.json", [
            'receptor' => $mobile,
            'token'    => $token,
            'template' => "verifycode"
        ]);
        Log::info('Kavenegar response: ' . $response->body());

        return true;
    }
    public function adminSendToken(Request $request)
    {
        $validated = $request->validate([
            'mobile' => 'required|string|size:11'
        ]);
        $user = User::where('mobile', $validated['mobile'])->first();
        if ($user) {
            if ($user->roles()->where('slug', 'customer')->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'شما مجاز به انجام این عملیات نیستید.'
                ], 403);
            } else {
                $this->sendOtp($request->mobile);
                return response()->json([
                    'success' => true,
                    'message' => 'کد یکبار مصرف ارسال شد.'
                ]);
            }
        }
        return response()->json([
            'success' => false,
            'message' => 'شما مجاز به انجام این عملیات نیستید.'
        ], 403);
    }
    public  function sendOtpAgain(Request $request)
    {
        $request->validate(['mobile' => 'required|digits:11']);
        $this->sendOtp($request->mobile);
        return response()->json([
            'message' => 'OTP sent',
            'success' => true,
        ]);
    }
    // 4) بررسی OTP
    public function verifyOtp(Request $request, NotificationService $notifications)
    {
        $data = $request->validate([
            'mobile' => 'required|digits:11',
            'token'  => 'required|digits:6',
        ]);
        $mobile = trim($data['mobile']);
        $otp = Otp::where('mobile', $mobile)
            ->where('token', $data['token'])
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return response()->json(
                [
                    'message' => 'کد اعتبار خود را از دست داده است مجدد تلاش کنید',
                    'success' => false
                ],
                422
            );
        }

        $user = User::where('mobile', $mobile)->first();
        if ($user) {
            $token = $user->createToken('auth_token')->plainTextToken;
            $otp->delete();
            return response()->json([
                'user' => $user,
                'token' => $token,
                'status' => 'login',
                "success" => true
            ]);
        } else {
            $user = User::create([
                'mobile'    => $mobile,
                'full_name' => " "
            ]);
            $customerRoleId = Role::where('slug', 'customer')->value('id');
            $user->roles()->sync([$customerRoleId]);

            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
            ]);

            $otp->delete(); // حذف OTP بعد از ثبت‌نام
            $token = $user->createToken('auth_token')->plainTextToken;
            $notifications->create(
                "ثبت کاربر",
                "کاربر با شماره {$user->mobile}  در سیستم عضو شد",
                "notification_users",
                ['user' => $user->id]
            );
            return response()->json([
                'user'  => $user,
                'token' => $token,
                'status' => 'register',
                "success" => true
            ]);
        }
    }


    public function adminLogin(Request $request)
    {

        $data = $request->validate([
            'mobile' => 'required|digits:11',
            'token'  => 'required|digits:6',
        ]);
        $mobile = trim($data['mobile']);
        $otp = Otp::where('mobile', $mobile)
            ->where('token', $data['token'])
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return response()->json(
                [
                    'message' => 'کد اعتبار خود را از دست داده است مجدد تلاش کنید',
                    'success' => false
                ],
                422
            );
        }

        $user = User::where('mobile', $mobile)->first();
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'user' => $user,
            'token' => $token,
            "success" => true,
            'message' => 'خوش آمدید'
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out',
            "success" => true
        ]);
    }
}
