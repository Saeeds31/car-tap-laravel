<?php

namespace Modules\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Notifications\Services\NotificationService;
use Modules\Wallet\Http\Requests\WalletStoreRequest;
use Modules\Wallet\Http\Requests\WalletUpdateRequest;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletTransaction;

class WalletController extends Controller
{
    public function userWalletIncrease(Request $request,NotificationService $notifications)
    {

        $user = $request->user();
        $request->validate([
            'amount' => 'required|integer|min:1000'
        ]);
        $wallet = $user->wallet;
        $wallet->balance += $request->amount;
        $wallet->save();
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => $request->amount,
            'description' => "افزایش کیف پول",
        ]);
        $notifications->create(
            "افزایش کیف پول",
            "کیف پول {$user->full_name}  به ملبغ {$request->amount} شارژ شد",
            "notification_finance",
            ['users' => $user->id]
        );
        return response()->json([
            'message' => "کیف پول کاربر",
            'success' => true,
            'wallet' => $user->wallet,
        ]);
    }
    public function userWallet(Request $request)
    {
        $user = $request->user();
        $wallet = $user->wallet()->first();
        $transactions = WalletTransaction::where('wallet_id', $wallet->id)->get();
        return response()->json([
            'message' => "کیف پول کاربر",
            'success' => true,
            'wallet' => $user->wallet,
            'transactions' => $transactions
        ]);
    }
    /**
     * لیست کیف پول‌ها
     */
    public function index(Request $request)
    {
        $query = Wallet::with(['user', 'transactions']);

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
        if (!is_null($balance = $request->get('balance'))) {
            $query->where('balance', '>=', $balance);
        }
        $wallets = $query->paginate(20);
        return response()->json($wallets);
    }


    /**
     * ایجاد کیف پول جدید برای کاربر
     */
    public function store(WalletStoreRequest $request)
    {
        $data = $request->validated();

        $wallet = Wallet::create([
            'user_id' => $data['user_id'],
            'balance' => $data['balance'] ?? 0,
        ]);
        return response()->json([
            'message' => 'Wallet created successfully',
            'wallet' => $wallet->load(['user', 'transactions']),
        ], 201);
    }

    /**
     * نمایش جزئیات کیف پول
     */
    public function show(Wallet $wallet)
    {
        return response()->json($wallet->load(['user', 'transactions']));
    }

    /**
     * ویرایش کیف پول
     */
    public function update(WalletUpdateRequest $request, Wallet $wallet)
    {
        $data = $request->validated();

        $wallet->update($data);

        return response()->json([
            'message' => 'Wallet updated successfully',
            'wallet' => $wallet->load(['user', 'transactions']),
        ]);
    }

    /**
     * حذف کیف پول
     */
    public function destroy(Wallet $wallet)
    {
        $wallet->delete();

        return response()->json([
            'message' => 'Wallet deleted successfully',
        ]);
    }
}
