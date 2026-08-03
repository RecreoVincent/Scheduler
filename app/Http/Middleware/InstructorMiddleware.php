<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class InstructorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('instructor')->check()) {
            Auth::shouldUse('instructor');
        } elseif (Auth::guard('web')->check()) {
            Auth::shouldUse('web');
        } else {
            return redirect()->route('login', ['role' => 'instructor']);
        }

        $user = $request->user();

        abort_unless(
            $user && $user->role === 'instructor' && $user->account_status === 'active',
            403,
            'You are not authorized to access the instructor portal.',
        );

        return $next($request);
    }
}
