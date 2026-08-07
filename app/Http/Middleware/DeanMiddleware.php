<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DeanMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('dean')->check()) {
            Auth::shouldUse('dean');
        } elseif (Auth::guard('web')->check()) {
            Auth::shouldUse('web');
        } else {
            return redirect()->route('home')->withFragment('portals');
        }

        $user = $request->user();

        abort_unless($user && $user->role === 'dean' && filled($user->course), 403, 'You are not authorized to access the dean portal.');

        return $next($request);
    }
}
