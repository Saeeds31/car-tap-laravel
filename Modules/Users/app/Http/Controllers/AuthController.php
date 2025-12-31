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
        $response = Http::get("https://api.kavenegar.com/v1/2B456D34746B54555A55796D5542655A694E693753694D2B47524C6E4F556A69584551735A7143346357733D/verify/lookup.json", [
            'receptor' => $mobile,
            'token'    => $token,
            'template' => "verify"
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
    public function update(Request $request)
    {
        $user = $request->user();

        $validated_data = $request->validate([
            'national_code' => ['nullable', 'string', 'size:10'],
            'birth_date'    => ['nullable', 'date'],
            'full_name' => 'required|string|min:3',
            'birth_certificate_number' => 'required|string',
            'marital_status' => 'required',
            'image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'address' => 'required|string',
            'city_id' => 'required|integer|exists:cities,id',
            'postal_code' => 'required|string',
        ]);

        // اگر فایل تصویر آپلود شده بود
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('users/images', 'public');
            $validated_data['image'] = $path;
        }

        // بروزرسانی اطلاعات کاربر
        $user->update($validated_data);

        return response()->json([
            'message' => 'اطلاعات کاربر با موفقیت بروزرسانی شد',
            'user'    => $user,
        ]);
    }
}
