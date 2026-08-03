<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('admin')->check()) {
            Auth::shouldUse('admin');
        } elseif (Auth::guard('web')->check()) {
            Auth::shouldUse('web');
        } else {
            return redirect()->route('login', ['role' => 'admin']);
        }

        if ($request->user()?->role !== 'admin') {
            abort(403, 'You are not authorized to access the admin portal.');
        }

        return $next($request);
    }
}
