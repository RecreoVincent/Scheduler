<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AcademicSection;
use App\Models\StudentRoster;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentRosterLoginController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'string', 'max:30'],
            'last_name' => ['required', 'string', 'max:100'],
        ]);

        $studentId = trim($validated['student_id']);
        $lastName = trim($validated['last_name']);
        $rosterEntry = StudentRoster::query()->where('student_id', $studentId)->first();

        if (! $rosterEntry || ! $this->lastNameMatches($rosterEntry->full_name, $lastName)) {
            throw $this->verificationFailed();
        }

        $student = User::withTrashed()->where('student_id', $studentId)->first();

        if ($student && $student->role !== 'student') {
            throw $this->verificationFailed();
        }

        if ($student?->trashed()) {
            $student->restore();
        }

        if (! $student) {
            $student = $this->createStudentAccount($rosterEntry, $lastName);
        } else {
            // The official roster is the access authority for student accounts,
            // including the section that supplies the student's course and year.
            $this->syncRosterAssignment($student, $rosterEntry);
        }

        Auth::guard('student')->login($student);
        Auth::shouldUse('student');
        $request->session()->regenerate();

        return redirect()->route('student.login-transition');
    }

    private function createStudentAccount(StudentRoster $rosterEntry, string $verifiedLastName): User
    {
        [$firstName, $middleName, $lastName] = $this->nameParts($rosterEntry->full_name, $verifiedLastName);
        $section = $this->matchingSection($rosterEntry);

        return User::create([
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            // Student roster records do not include an email address. This internal,
            // non-deliverable address only satisfies the existing account schema.
            'email' => 'student-'.substr(hash('sha256', $rosterEntry->student_id), 0, 24).'@roster.mcc.local',
            'password' => Hash::make(Str::random(64)),
            'role' => 'student',
            'course' => $section?->course,
            'year_level' => $section?->year_level,
            'academic_section_id' => $section?->id,
            'student_id' => $rosterEntry->student_id,
            'account_status' => 'active',
        ]);
    }

    private function matchingSection(StudentRoster $rosterEntry): ?AcademicSection
    {
        if (blank($rosterEntry->section)) {
            return null;
        }

        $sectionKey = $this->sectionLookupKey($rosterEntry->section);

        return AcademicSection::query()
            ->whereRaw("LOWER(REPLACE(REPLACE(name, ' ', ''), '-', '')) = ?", [$sectionKey])
            ->orderByDesc('academic_year')
            ->first();
    }

    private function sectionLookupKey(string $section): string
    {
        $section = preg_replace('/[\s-]+/', '', trim($section)) ?? '';

        return Str::lower($section);
    }

    private function syncRosterAssignment(User $student, StudentRoster $rosterEntry): void
    {
        $updates = ['account_status' => 'active'];
        $section = $this->matchingSection($rosterEntry);

        // Do not erase an existing assignment if an administrator imports a
        // roster row whose section has not been created in the system yet.
        if ($section) {
            $updates += [
                'course' => $section->course,
                'year_level' => $section->year_level,
                'academic_section_id' => $section->id,
            ];
        }

        $student->fill($updates);

        if ($student->isDirty()) {
            $student->save();
        }
    }

    /** @return array{0:string, 1:?string, 2:string} */
    private function nameParts(string $fullName, string $verifiedLastName): array
    {
        $fullName = Str::squish($fullName);

        if (str_contains($fullName, ',')) {
            [$lastName, $remainingNames] = array_map('trim', explode(',', $fullName, 2));
            $parts = preg_split('/\s+/', $remainingNames) ?: [];

            return [
                $parts[0] ?? $lastName,
                count($parts) > 2 ? implode(' ', array_slice($parts, 1, -1)) : null,
                $lastName,
            ];
        }

        $parts = preg_split('/\s+/', $fullName) ?: [];
        $suffix = count($parts) > 2 && in_array(strtolower(rtrim((string) end($parts), '.')), ['jr', 'sr', 'ii', 'iii', 'iv', 'v'], true)
            ? array_pop($parts)
            : null;

        $verifiedParts = preg_split('/\s+/', Str::squish($verifiedLastName)) ?: [];
        $surnamePartCount = min(count($verifiedParts), count($parts));
        $lastNameParts = array_slice($parts, -$surnamePartCount);
        $lastName = implode(' ', $lastNameParts) ?: $verifiedLastName;
        $givenNames = array_slice($parts, 0, max(0, count($parts) - $surnamePartCount));
        $firstName = array_shift($givenNames) ?: $verifiedLastName;
        $middleName = $givenNames === [] ? null : implode(' ', $givenNames);

        return [$firstName, $middleName, trim(implode(' ', array_filter([$lastName, $suffix])))];
    }

    private function lastNameMatches(string $fullName, string $lastName): bool
    {
        $expected = $this->normalize($lastName);
        $fullName = $this->normalize($fullName);

        if ($expected === '' || $fullName === '') {
            return false;
        }

        if (str_contains($fullName, ',')) {
            [$surname] = explode(',', $fullName, 2);
            if ($expected === trim($surname)) {
                return true;
            }
        }

        $fullName = preg_replace('/(?:,?\s+(?:jr\.?|sr\.?|ii|iii|iv|v))$/', '', $fullName) ?? $fullName;

        return $fullName === $expected || str_ends_with($fullName, ' '.$expected);
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::squish($value));
    }

    private function verificationFailed(): ValidationException
    {
        return ValidationException::withMessages([
            'student_id' => 'We could not verify that student number and last name. Check the official roster details and try again.',
        ]);
    }
}
