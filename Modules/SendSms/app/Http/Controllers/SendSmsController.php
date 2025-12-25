<?php

namespace Modules\SendSms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\Services\NotificationService;
use Modules\SendSms\Models\SendSms;
use Modules\Users\Models\User;

class SendSmsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sendList = SendSms::with(['role', 'sender'])->orderBy('created_at')->get();
        return response()->json([
            'message' => 'لیست پیام های ارسالی',
            'data' => $sendList
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('sendsms::create');
    }

    public function store(Request $request,NotificationService $notifications)
    {
        $user = $request->user();
        $data = $request->validate([
            'subject'   => 'required|string|min:3|max:255',
            'role_id'   => 'required|integer|exists:roles,id',
            'body'      => 'required|string|min:10',
        ]);
        $data['sender_id'] = $user->id;
        $message = SendSms::create($data);
        $targetRoleId = $data['role_id'];
        $recipients = User::whereHas('roles', function ($query) use ($targetRoleId) {
            $query->where('roles.id', $targetRoleId);
        })
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->pluck('mobile')
            ->unique()
            ->toArray();

        $smsText = $data['body'];
        if (mb_strlen($smsText) > 450) {
            $smsText = mb_substr($smsText, 0, 447) . '...';
        }
        $smsService = new SmsService();
        foreach ($recipients as $mobile) {
            try {
                $smsService->sendText($mobile, $smsText);
                Log::info("پیامک با موفقیت ارسال شد به: {$mobile}");
            } catch (\Exception $e) {
                Log::error("خطا در ارسال پیامک به {$mobile}: " . $e->getMessage());
            }
        }
        $notifications->create(
            "ارسال پیام همگانی",
            "یک  پیام همگانی در سیستم ارسال شد",
            "notification_users",
            ['send_sms' => null]
        );
        return response()->json([
            'message' => 'پیام با موفقیت ثبت شد و به گیرندگان اطلاع‌رسانی شد.',
            'data'    => $message
        ], 201);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('sendsms::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('sendsms::edit');
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
