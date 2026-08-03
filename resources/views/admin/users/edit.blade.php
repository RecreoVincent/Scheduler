@extends('layouts.admin')

@section('title', 'Edit Account')
@section('page-title', 'Edit User Account')

@push('styles')
<style>
    .form-card { max-width: 720px; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .form-group { margin-bottom: 18px; }
    label { display: block; margin-bottom: 7px; font-size: 14px; font-weight: 700; color: #334155; }
    .input { width: 100%; padding: 12px 13px; font-size: 14px; border: 1px solid #cbd5e1; border-radius: 9px; outline: none; }
    .input:focus { border-color: #60a5fa; box-shadow: 0 0 0 3px rgba(96, 165, 250, .14); }
    .error { margin-top: 6px; font-size: 12px; color: #dc2626; }
    .form-actions { display: flex; gap: 10px; margin-top: 5px; }
    @media (max-width: 650px) { .form-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="page-header">
    <div><h2>Edit Account</h2><p>Update {{ $user->name }}'s account information.</p></div>
</div>

<div class="card form-card">
    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="form-grid">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input id="first_name" type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" class="input" autocomplete="given-name" required>
                @error('first_name')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="middle_name">Middle Name <span style="font-weight:400;">(optional)</span></label>
                <input id="middle_name" type="text" name="middle_name" value="{{ old('middle_name', $user->middle_name) }}" class="input" autocomplete="additional-name">
                @error('middle_name')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input id="last_name" type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" class="input" autocomplete="family-name" required>
                @error('last_name')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="suffix">Suffix <span style="font-weight:400;">(optional)</span></label>
                <input id="suffix" type="text" name="suffix" value="{{ old('suffix', $user->suffix) }}" class="input" placeholder="Jr., Sr., III">
                @error('suffix')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="input" required>
                @error('email')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" class="input" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
                @error('role')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="course">Course</label>
                <select id="course" name="course" class="input" required>
                    @foreach ($courses as $course)
                        <option value="{{ $course }}" @selected(old('course', $user->course) === $course)>{{ $course }}</option>
                    @endforeach
                </select>
                @error('course')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="employment_type">Instructor Employment Type</label>
                <select id="employment_type" name="employment_type" class="input">
                    <option value="">Not applicable</option>
                    <option value="full_time" @selected(old('employment_type', $user->employment_type) === 'full_time')>Full time (30 units maximum)</option>
                    <option value="industry_part_time" @selected(old('employment_type', $user->employment_type) === 'industry_part_time')>Industry Part-Time (15 units maximum)</option>
                    <option value="flexible_part_time" @selected(in_array(old('employment_type', $user->employment_type), ['flexible_part_time', 'part_time'], true))>Flexible Part-Time (15 units maximum)</option>
                </select>
                @error('employment_type')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="outside_work_end_time">Industry Outside-Work End Time</label>
                <input id="outside_work_end_time" type="time" name="outside_work_end_time" class="input" value="{{ old('outside_work_end_time', $user->outside_work_end_time ? substr($user->outside_work_end_time, 0, 5) : '17:00') }}">
                <small>Used only for Industry Part-Time instructors.</small>
                @error('outside_work_end_time')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="year_level">Student Year Level</label>
                <select id="year_level" name="year_level" class="input">
                    <option value="">Not applicable</option>
                    @for ($level = 1; $level <= 4; $level++)
                        <option value="{{ $level }}" @selected(old('year_level', $user->year_level) == $level)>Year {{ $level }}</option>
                    @endfor
                </select>
                @error('year_level')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="academic_section_id">Student Section</label>
                <select id="academic_section_id" name="academic_section_id" class="input">
                    <option value="">Not assigned yet</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}" @selected(old('academic_section_id', $user->academic_section_id) == $section->id)>{{ $section->course }} · Year {{ $section->year_level }} · {{ $section->name }} · {{ $section->academic_year }}</option>
                    @endforeach
                </select>
                @error('academic_section_id')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="account_status">Account Status</label>
                <select id="account_status" name="account_status" class="input" required>
                    <option value="active" @selected(old('account_status', $user->account_status) === 'active')>Active</option>
                    <option value="pending" @selected(old('account_status', $user->account_status) === 'pending')>Pending</option>
                </select>
                @error('account_status')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="password">New Password <span style="font-weight:400;">(optional)</span></label>
                <input id="password" type="password" name="password" class="input" autocomplete="new-password">
                @error('password')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm New Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="input" autocomplete="new-password">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="button">Save Changes</button>
            <a href="{{ route('admin.users.index') }}" class="button button-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
