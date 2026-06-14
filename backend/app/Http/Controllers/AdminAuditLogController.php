<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()->with('user:id,name,email,role');

        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->string('entity_type')->toString());
        }

        if ($request->filled('entity_id')) {
            $query->where('entity_id', (int) $request->input('entity_id'));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->string('from')->toString());
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->string('to')->toString());
        }

        return response()->json(
            $query->orderByDesc('id')->paginate((int) $request->input('per_page', 20))
        );
    }
}

