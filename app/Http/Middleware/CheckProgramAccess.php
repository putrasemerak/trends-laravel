<?php

namespace App\Http\Middleware;

use App\Models\AccessLog;
use App\Models\ProgramAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckProgramAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\Employee|null $user */
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $empno = $user->EmpNo;

        // Get access record for AINCCS system
        $access = ProgramAccess::where('EmpNo', $empno)
            ->where('SystemName', 'AINCCS')
            ->where('Status', 'Active')
            ->first();

        if (!$access) {
            abort(403, 'You do not have access to this system.');
        }

        // Store access level in request for controllers/views
        $request->merge(['access_level' => $access->access_level_number]);

        // Log access
        AccessLog::create([
            'empno' => $empno,
            'title' => $request->getRequestUri(),
        ]);

        return $next($request);
    }
}
