<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = AuditLog::query()->orderByDesc('id');

        if ($request->filled('user_id')) {
            $q->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('from')) {
            $q->where('created_at', '>=', $request->date('from')->startOfDay());
        }

        if ($request->filled('to')) {
            $q->where('created_at', '<=', $request->date('to')->endOfDay());
        }

        if ($request->filled('path')) {
            $q->where('path', 'like', '%'.$request->string('path').'%');
        }

        if ($request->filled('method')) {
            $q->where('method', strtoupper($request->string('method')));
        }

        $perPage = min($request->integer('per_page', 50), 100);

        return response()->json($q->paginate($perPage));
    }
}
