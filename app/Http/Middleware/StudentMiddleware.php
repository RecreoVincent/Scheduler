<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StudentMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('student')->check()) {
            Auth::shouldUse('student');
        } elseif (Auth::guard('web')->check()) {
            Auth::shouldUse('web');
        } else {
            return redirect()->route('login', ['role' => 'student']);
        }

        $user = $request->user();

        abort_unless(
            $user && $user->role === 'student' && $user->account_status === 'active',
            403,
            'You are not authorized to access the student portal.',
        );

        return $next($request);
    }
}
