<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class KioskMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!auth()->user()->hasRole('security')) {
            abort(403, 'Only security personnel can access the kiosk.');
        }

        // Check if security officer is on shift
        if (!auth()->user()->isOnShift() && !$request->is('kiosk/start-shift')) {
            return redirect()->route('profile.shift-history')
                ->with('warning', 'Please start your shift before accessing the kiosk.');
        }

        return $next($request);
    }
}
