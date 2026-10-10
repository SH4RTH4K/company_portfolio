<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotFoundLog;

class NotFoundLogController extends Controller
{
    public function index()
    {
        $logs = NotFoundLog::orderBy('hits', 'desc')->orderBy('last_hit_at', 'desc')->paginate(50);

        return view('admin.not_found_logs.index', compact('logs'));
    }

    public function destroy(NotFoundLog $notFoundLog)
    {
        $notFoundLog->delete();

        return redirect()->route('admin.not-found-logs.index')->with('success', 'Log deleted successfully.');
    }

    public function clear()
    {
        NotFoundLog::truncate();

        return redirect()->route('admin.not-found-logs.index')->with('success', 'All 404 logs cleared.');
    }
}
