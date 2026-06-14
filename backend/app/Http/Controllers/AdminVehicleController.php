<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Helpers\NotificationHelper;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminVehicleController extends Controller
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

    public function getPendingVehicles(Request $request)
    {
        $vehicles = Vehicle::with(['owner', 'verifier'])
            ->where('verification_status', 'pending')
            ->latest()
            ->paginate(20);

        return response()->json($vehicles);
    }

    public function getVehicle(Vehicle $vehicle)
    {
        $vehicle->load(['owner', 'verifier']);
        return response()->json($vehicle);
    }

    public function approveVehicle(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->verification_status !== 'pending') {
            return response()->json([
                'message' => "Этот автомобиль уже обработан. Текущий статус: '{$vehicle->verification_status}'."
            ], 400);
        }

        DB::transaction(function () use ($vehicle, $request) {
            $vehicle->update([
                'verification_status' => 'approved',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'verification_notes' => $request->input('notes', ''),
            ]);
        });

        $vehicle->refresh();
        $vehicle->load(['owner', 'verifier']);

        // Создаем уведомление
        if ($vehicle->owner) {
            NotificationHelper::vehicleApproved($vehicle->owner, $vehicle);
        }

        AuditLogger::log($request->user()->id, 'vehicle.verify.approve', [
            'entity_type' => 'Vehicle',
            'entity_id' => $vehicle->id,
        ], $request);

        return response()->json([
            'message' => 'Автомобиль одобрен.',
            'vehicle' => $vehicle,
        ]);
    }

    public function rejectVehicle(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->verification_status !== 'pending') {
            return response()->json([
                'message' => "Этот автомобиль уже обработан. Текущий статус: '{$vehicle->verification_status}'."
            ], 400);
        }

        DB::transaction(function () use ($vehicle, $request) {
            $vehicle->update([
                'verification_status' => 'rejected',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'verification_notes' => $request->input('notes', ''),
            ]);
        });

        $vehicle->refresh();
        $vehicle->load(['owner', 'verifier']);

        // Создаем уведомление
        if ($vehicle->owner) {
            NotificationHelper::vehicleRejected(
                $vehicle->owner,
                $vehicle,
                $request->input('notes')
            );
        }

        AuditLogger::log($request->user()->id, 'vehicle.verify.reject', [
            'entity_type' => 'Vehicle',
            'entity_id' => $vehicle->id,
            'meta' => ['notes' => $request->input('notes')],
        ], $request);

        return response()->json([
            'message' => 'Автомобиль отклонен.',
            'vehicle' => $vehicle,
        ]);
    }

    public function getDocument(Request $request, Vehicle $vehicle, string $type)
    {
        // Проверяем права доступа (только админ или менеджер)
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Не авторизован.'], 401);
        }
        
        if (!in_array($user->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'Доступ запрещен.'], 403);
        }

        $path = null;
        
        if ($type === 'ownership') {
            $path = $vehicle->ownership_document_path;
        } elseif ($type === 'license') {
            $path = $vehicle->license_document_path;
        } else {
            return response()->json(['message' => 'Неверный тип документа.'], 400);
        }

        if (!$path || !Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'Файл не найден.'], 404);
        }

        $mimeType = Storage::disk('public')->mimeType($path);
        
        // Для прямого доступа через браузер возвращаем файл с правильными заголовками
        return Storage::disk('public')->response($path, null, [
            'Content-Type' => $mimeType ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }
}

