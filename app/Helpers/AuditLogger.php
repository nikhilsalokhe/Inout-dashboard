<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Create an audit log entry.
     *
     * @param string $module
     * @param string $action
     * @param array|null $oldData
     * @param array|null $newData
     * @return AuditLog
     */
    public static function log($module, $action, $oldData = null, $newData = null)
    {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'module' => $module,
            'action' => $action,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => Request::ip(),
            'device_info' => Request::header('User-Agent'),
        ]);
    }
}
