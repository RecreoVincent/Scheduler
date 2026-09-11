<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\StudentRoster;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentIdLookupController extends Controller
{
    /**
     * Look up a Student ID against the official roster and report whether
     * an account already exists for it, so the login page can route the
     * student to either the sign-in form or registration.
     *
     * Validation is handled manually (rather than via $request->validate())
     * because this app only auto-renders validation failures as JSON for
     * /api/* routes (see bootstrap/app.php); everywhere else a failed
     * validate() call redirects instead, which breaks this JSON endpoint.
     */
    public function check(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'string', 'max:30'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'invalid',
                'message' => $validator->errors()->first('student_id'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $studentId = trim($validator->validated()['student_id']);

        if (! StudentRoster::query()->where('student_id', $studentId)->exists()) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'This Student ID was not found in the official student roster.',
            ], 404);
        }

        $user = User::query()->where('role', 'student')->where('student_id', $studentId)->first();

        if ($user) {
            return response()->json([
                'status' => 'registered',
                'email' => $user->email,
            ]);
        }

        return response()->json(['status' => 'unregistered']);
    }
}
