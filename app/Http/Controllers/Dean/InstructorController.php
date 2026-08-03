<?php

namespace App\Http\Controllers\Dean;

use App\Models\ClassSchedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InstructorController extends DeanController
{
    public function index(Request $request): View
    {
        $course = $this->course($request);
        $query = User::where('course', $course)->where('role', 'instructor');

        $pendingInstructors = (clone $query)
            ->where('account_status', 'pending')
            ->orderBy('created_at')
            ->get();

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $query->where(fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }

        $instructors = (clone $query)->where('account_status', 'active');

        match ($request->input('sort', 'name')) {
            'newest' => $instructors->latest(),
            'oldest' => $instructors->oldest(),
            'employment' => $instructors->orderBy('employment_type')->orderBy('first_name')->orderBy('last_name'),
            default => $instructors->orderBy('first_name')->orderBy('middle_name')->orderBy('last_name'),
        };

        $instructors = $instructors->paginate(12)->withQueryString();

        return view('dean.instructors.index', compact('course', 'pendingInstructors', 'instructors'));
    }

    public function approve(Request $request, User $instructor): RedirectResponse
    {
        $this->ensureInstructor($request, $instructor);
        abort_unless($instructor->account_status === 'pending', 422, 'This instructor account is not pending.');

        $instructor->update(['account_status' => 'active']);

        return back()->with('success', 'Instructor account approved successfully.');
    }

    public function destroy(Request $request, User $instructor): RedirectResponse
    {
        $this->ensureInstructor($request, $instructor);
        $wasPending = $instructor->account_status === 'pending';

        Subject::where('instructor_id', $instructor->id)->update(['instructor_id' => null]);
        DB::table('subject_instructor')->where('instructor_id', $instructor->id)->delete();
        ClassSchedule::withTrashed()->where('instructor_id', $instructor->id)->forceDelete();
        $instructor->delete();

        return back()->with('success', $wasPending
            ? 'Pending instructor registration declined.'
            : 'Instructor account deleted successfully.');
    }

    private function ensureInstructor(Request $request, User $instructor): void
    {
        abort_unless($instructor->role === 'instructor' && strtoupper((string) $instructor->course) === $this->course($request), 404);
    }
}
