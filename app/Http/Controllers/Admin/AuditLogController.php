<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->paginate(30);

        return view('admin.audit_logs.index', compact('logs'));
    }

    public function show(AuditLog $auditLog)
    {
        return view('admin.audit_logs.show', compact('auditLog'));
    }

    public function clear()
    {
        AuditLog::truncate();

        return redirect()->route('admin.audit-logs.index')->with('success', 'Audit log history cleared.');
    }
}
