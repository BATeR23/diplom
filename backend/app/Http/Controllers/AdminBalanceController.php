<?php

namespace App\Http\Controllers;

use App\Models\BalanceRechargeRequest;
use App\Models\BalanceTransaction;
use App\Helpers\NotificationHelper;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminBalanceController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (!in_array($user->role, ['admin', 'manager'])) {
                return response()->json(['message' => 'Доступ запрещен.'], 403);
            }
            return $next($request);
        });
    }

    public function getRechargeRequests(Request $request)
    {
        $query = BalanceRechargeRequest::with(['user', 'admin']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $requests = $query->latest()->paginate(20);

        return response()->json($requests);
    }

    public function getRechargeRequest(BalanceRechargeRequest $request)
    {
        $request->load(['user', 'admin']);
        return response()->json($request);
    }

    protected function isPending(BalanceRechargeRequest $request): bool
    {
        $status = trim($request->status ?? '');
        return strcasecmp($status, 'pending') === 0;
    }

    public function approveRechargeRequest(Request $request, BalanceRechargeRequest $rechargeRequest)
    {
        try {
            // Загружаем запрос заново из базы данных с блокировкой для предотвращения race condition
            $rechargeRequest = DB::transaction(function () use ($rechargeRequest, $request) {
                $requestModel = BalanceRechargeRequest::lockForUpdate()
                    ->findOrFail($rechargeRequest->id);

                // Проверяем статус - если null, устанавливаем в 'pending' (для старых записей)
                if (empty($requestModel->status)) {
                    $requestModel->status = 'pending';
                    $requestModel->save();
                }

                $currentStatus = trim($requestModel->status ?? '');
                
                if (empty($currentStatus) || strcasecmp($currentStatus, 'pending') !== 0) {
                    $statusLabel = !empty($currentStatus) ? $currentStatus : 'не установлен';
                    throw new \Exception("Этот запрос уже обработан или имеет неверный статус. Текущий статус: '{$statusLabel}'.");
                }

                // Загружаем связанные данные
                $requestModel->load('user');
                $user = $requestModel->user;
                
                if (!$user) {
                    throw new \Exception('Пользователь не найден для этого запроса.');
                }
                
                $balanceBefore = $user->balance ?? 0;
                $balanceAfter = $balanceBefore + $requestModel->amount;

                // Обновляем баланс пользователя
                $user->update(['balance' => $balanceAfter]);

                // Создаем транзакцию
                BalanceTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'recharge',
                    'amount' => $requestModel->amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'description' => 'Пополнение баланса (одобрено администратором)',
                ]);

                // Обновляем запрос
                $requestModel->update([
                    'status' => 'approved',
                    'admin_id' => $request->user()->id,
                    'admin_notes' => $request->input('notes', ''),
                    'processed_at' => now(),
                ]);

                // Создаем уведомление
                NotificationHelper::balanceRechargeApproved($user, $requestModel->amount);

                return $requestModel;
            });

            // Обновляем модель после транзакции
            $rechargeRequest->refresh();
            $rechargeRequest->load(['user', 'admin']);

            AuditLogger::log($request->user()->id, 'balance.recharge.approve', [
                'entity_type' => 'BalanceRechargeRequest',
                'entity_id' => $rechargeRequest->id,
                'meta' => ['amount' => $rechargeRequest->amount],
            ], $request);

            return response()->json([
                'message' => 'Запрос на пополнение одобрен.',
                'request' => $rechargeRequest,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Запрос на пополнение не найден.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function rejectRechargeRequest(Request $request, BalanceRechargeRequest $rechargeRequest)
    {
        try {
            // Загружаем запрос заново из базы данных с блокировкой
            $rechargeRequest = DB::transaction(function () use ($rechargeRequest, $request) {
                $requestModel = BalanceRechargeRequest::lockForUpdate()
                    ->findOrFail($rechargeRequest->id);

                // Проверяем статус - если null, устанавливаем в 'pending' (для старых записей)
                if (empty($requestModel->status)) {
                    $requestModel->status = 'pending';
                    $requestModel->save();
                }

                $currentStatus = trim($requestModel->status ?? '');
                
                if (empty($currentStatus) || strcasecmp($currentStatus, 'pending') !== 0) {
                    $statusLabel = !empty($currentStatus) ? $currentStatus : 'не установлен';
                    throw new \Exception("Этот запрос уже обработан или имеет неверный статус. Текущий статус: '{$statusLabel}'.");
                }

                // Сохраняем путь к файлу перед обновлением
                $receiptPath = $requestModel->receipt_path;

                // Обновляем запрос
                $requestModel->update([
                    'status' => 'rejected',
                    'admin_id' => $request->user()->id,
                    'admin_notes' => $request->input('notes', ''),
                    'processed_at' => now(),
                ]);

                // Удаляем загруженный файл
                if ($receiptPath && Storage::disk('public')->exists($receiptPath)) {
                    Storage::disk('public')->delete($receiptPath);
                }

                // Создаем уведомление
                $requestModel->load('user');
                if ($requestModel->user) {
                    NotificationHelper::balanceRechargeRejected(
                        $requestModel->user,
                        $request->input('notes')
                    );
                }

                return $requestModel;
            });

            // Обновляем модель после транзакции
            $rechargeRequest->refresh();
            $rechargeRequest->load(['user', 'admin']);

            AuditLogger::log($request->user()->id, 'balance.recharge.reject', [
                'entity_type' => 'BalanceRechargeRequest',
                'entity_id' => $rechargeRequest->id,
                'meta' => ['amount' => $rechargeRequest->amount],
            ], $request);

            return response()->json([
                'message' => 'Запрос на пополнение отклонен.',
                'request' => $rechargeRequest,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Запрос на пополнение не найден.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getReceipt(Request $request, BalanceRechargeRequest $rechargeRequest)
    {
        // Проверяем права доступа (только админ или менеджер)
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'Доступ запрещен.'], 403);
        }

        if (!$rechargeRequest->receipt_path || !Storage::disk('public')->exists($rechargeRequest->receipt_path)) {
            return response()->json(['message' => 'Файл не найден.'], 404);
        }

        $mimeType = Storage::disk('public')->mimeType($rechargeRequest->receipt_path);
        
        return Storage::disk('public')->response($rechargeRequest->receipt_path, null, [
            'Content-Type' => $mimeType ?: 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($rechargeRequest->receipt_path) . '"',
        ]);
    }
}

