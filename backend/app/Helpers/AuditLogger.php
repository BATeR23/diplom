<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogger
{
    public static function log(?int $userId, string $action, array $context = [], ?Request $request = null): void
    {
        try {
            $entityType = $context['entity_type'] ?? null;
            $entityId = $context['entity_id'] ?? null;
            $meta = $context['meta'] ?? null;

            $ip = null;
            $userAgent = null;

            if ($request) {
                $ip = $request->ip();
                $userAgent = substr((string) $request->userAgent(), 0, 500);
            }

            AuditLog::create([
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'meta' => $meta,
            ]);
        } catch (\Throwable $e) {
            // Не мешаем основному потоку приложения из-за сбоя аудита
        }
    }
}

