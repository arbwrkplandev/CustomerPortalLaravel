<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminAuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with(['user', 'tenant']);

        if ($s = $request->search) {
            $query->where(function ($q) use ($s) {
                $q->where('action', 'like', "%$s%")
                  ->orWhere('module', 'like', "%$s%")
                  ->orWhere('description', 'like', "%$s%");
            });
        }
        if ($m = $request->module) $query->where('module', $m);
        if ($f = $request->date_from) $query->whereDate('created_at', '>=', $f);
        if ($t = $request->date_to) $query->whereDate('created_at', '<=', $t);

        $logs = $query->latest()->paginate(20);
        $modules = AuditLog::distinct()->pluck('module');
        return view('admin.audit.index', compact('logs', 'modules'));
    }
}
