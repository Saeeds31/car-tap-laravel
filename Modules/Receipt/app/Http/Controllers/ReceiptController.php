<?php

namespace Modules\Receipt\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Modules\Notifications\Services\NotificationService;
use Modules\Receipt\Http\Requests\ReceiptStoreRequest;
use Modules\Receipt\Models\Receipt;
use Modules\Wallet\Models\WalletTransaction;

class ReceiptController extends Controller
{
    public function userReceipts(Request $request)
    {
        $user = $request->user();
        $receipts = Receipt::where('user_id', $user->id)->get();
        return response()->json([
            'message' => "لیست رسید ها",
            'data' => $receipts,
            'success' => true
        ]);
    }
    public function storeUserReceipt(ReceiptStoreRequest $request,NotificationService $notifications)
    {
        $validated_data = $request->validated();
        $user = $request->user();
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('receipts', 'public');
            $validated_data['image'] = $path;
        }
        $validated_data['status'] = "pending";
        $validated_data['user_id'] = $user->id;
        $receipts = Receipt::create($validated_data);
        $notifications->create(
            "ثبت رسید",
            "یک رسید توسط کاربر {$user->full_name}  در سیستم ثبت شد",
            "notification_finance",
            ['receipts' =>$receipts->id]
        );
        return response()->json([
            'message' => "رسید با موفقیت ثبت شد و نتیجه آن به زودی به شما اعلام خواهد شد",
            'data' => $receipts,
            'success' => true
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Receipt::with(['user'])->latest('id');
        if ($fullName = $request->get('full_name')) {
            $query->whereHas('user', function ($q) use ($fullName) {
                $q->whereRaw('LOWER(full_name) like ?', ['%' . strtolower($fullName) . '%']);
            });
        }
        if ($mobile = $request->get('mobile')) {
            $query->whereHas('user', function ($q) use ($mobile) {
                $q->where('mobile', 'like', "%{$mobile}%");
            });
        }

        if (!is_null($status = $request->get('status'))) {
            $query->where('status', $status);
        }
        if (!is_null($amount = $request->get('amount'))) {
            $query->where('amount', '>=', $amount);
        }
        $receipts = $query->paginate(20);
        return response()->json($receipts);
    }
    public function acceptReceipt($receiptId,NotificationService $notifications)
    {
        $receipt = Receipt::where('id', $receiptId)->with(['user.wallet'])->first();
        if ($receipt->status != 'pending') {
            return response()->json([
                'message' => "این رسید از قبل تعیین وضعیت شده بود",
                'success' => false
            ], 422);
        }
        $user = $receipt->user;
        $wallet = $user->wallet;
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => $receipt->amount,
            'description' => "افزایش کیف پول برای رسید شماره {$receipt->id}",
        ]);
        $wallet->balance += $receipt->amount;
        $wallet->save();
        $receipt->status = 'accepted';
        $receipt->save();
        $text = "رسید شما تایید شد و به مبلغ {$receipt->amount} کیف پول شما شارژ شد \n تکین آرتا پرگاس";
        $sms = new SmsService();
        $sms->sendText($user->mobile, $text);
        $notifications->create(
            "تایید رسید",
            "رسید {$receipt->id}  از سیستم تایید شد",
            "notification_finance",
            ['receipts' => $receipt->id]
        );
        return response()->json([
            'message' => "تایید شد",
            'success' => true
        ]);
    }
    public function rejectReceipt(Request $request, $receiptId,NotificationService $notifications)
    {
        $receipt = Receipt::where('id', $receiptId)->with(['user.wallet'])->first();
        if ($receipt->status != 'pending') {
            return response()->json([
                'message' => "این رسید از قبل تعیین وضعیت شده بود",
                'success' => false
            ], 422);
        }
        $message = $request->get('message') ?? "رسید شما رد شد";
        $receipt->message = $message;
        $receipt->status = 'rejected';
        $user = $receipt->user;
        $receipt->save();
        $text = "رسید واریزی شما تایید نشد به پنل کاربری خود مراجعه کنید  \n تکین آرتا پرگاس";
        $sms = new SmsService();
        $sms->sendText($user->mobile, $text);
        $notifications->create(
            "رد رسید",
            "رسید {$receipt->id}  از سیستم رد شد",
            "notification_finance",
            ['receipts' => $receipt->id]
        );
        return response()->json([
            'message' => "رد شد",
            'success' => true
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('receipt::create');
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
        return view('receipt::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('receipt::edit');
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
