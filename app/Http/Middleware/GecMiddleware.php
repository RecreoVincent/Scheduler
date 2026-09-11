<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class GecMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('gec')->check()) {
            Auth::shouldUse('gec');
        } elseif (Auth::guard('web')->check()) {
            Auth::shouldUse('web');
        } else {
            return redirect()->route('login', ['role' => 'gec']);
        }

        if ($request->user()?->role !== 'gec') {
            abort(403, 'You are not authorized to access the GEC portal.');
        }

        return $next($request);
    }
}
