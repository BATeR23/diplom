<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBalanceRechargeRequest;
use App\Models\BalanceRechargeRequest;
use App\Models\BalanceTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BalanceController extends Controller
{
    public function recharge(StoreBalanceRechargeRequest $request)
    {
        $data = $request->validated();

        $user = $request->user();

        // Сохраняем PDF файл
        $file = $request->file('receipt');
        $receiptPath = $file->store('receipts', 'public');

        // Создаем запрос на пополнение
        $rechargeRequest = BalanceRechargeRequest::create([
            'user_id' => $user->id,
            'amount' => $data['amount'],
            'receipt_path' => $receiptPath,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Запрос на пополнение баланса отправлен. Ожидайте подтверждения администратора.',
            'request' => $rechargeRequest,
        ], 201);
    }

    public function history(Request $request)
    {
        $transactions = BalanceTransaction::where('user_id', $request->user()->id)
            ->with(['ride', 'booking'])
            ->latest()
            ->paginate(20);

        return response()->json($transactions);
    }

    public function current(Request $request)
    {
        return response()->json([
            'balance' => $request->user()->balance,
        ]);
    }
}

