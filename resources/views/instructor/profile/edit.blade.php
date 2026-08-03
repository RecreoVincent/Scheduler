@extends('layouts.instructor')
@section('title', 'Edit Profile')
@section('page-title', 'Edit Profile')
@section('content')
<div class="page-header"><div><h2>Personal Information</h2><p>Update your name, email address, or account password.</p></div></div>
<div class="card" style="max-width:820px"><form method="POST" action="{{ route('instructor.profile.update') }}">@csrf @method('PATCH')
    <div class="form-grid">
        <div><label>First Name</label><input class="input" name="first_name" value="{{ old('first_name',$instructor->first_name) }}" required></div>
        <div><label>Middle Name</label><input class="input" name="middle_name" value="{{ old('middle_name',$instructor->middle_name) }}"></div>
        <div><label>Last Name</label><input class="input" name="last_name" value="{{ old('last_name',$instructor->last_name) }}" required></div>
        <div><label>Suffix</label><input class="input" name="suffix" value="{{ old('suffix',$instructor->suffix) }}" placeholder="Jr., Sr., III"></div>
        <div style="grid-column:1/-1"><label>Email Address</label><input class="input" type="email" name="email" value="{{ old('email',$instructor->email) }}" required></div>
    </div>
    <div style="margin-top:28px;padding-top:22px;border-top:1px solid #e2e8f0"><h3 style="margin-bottom:6px;color:var(--navy)">Change Password</h3><p style="margin-bottom:17px;font-size:13px;color:var(--muted)">Leave these fields empty to keep your current password.</p><div class="form-grid"><div><label>Current Password</label><input class="input" type="password" name="current_password"></div><div></div><div><label>New Password</label><input class="input" type="password" name="password"></div><div><label>Confirm New Password</label><input class="input" type="password" name="password_confirmation"></div></div></div>
    <div class="form-actions"><button class="button">Save Profile</button></div>
</form></div>
@endsection
