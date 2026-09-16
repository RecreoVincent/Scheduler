@php
    $portalRoleLabel = $portalRoleLabel ?? ucfirst((string) auth()->user()->role);
    $portalRoleValue = strtolower((string) auth()->user()->role);
    $portalProfileInitial = strtoupper(substr(auth()->user()->first_name ?: auth()->user()->name ?: 'U', 0, 1));
@endphp
<div class="portal-profile-menu">
    <div class="profile"><span class="portal-profile-trigger">
        <span class="portal-profile-avatar">@if(auth()->user()->profile_photo_path)<img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}" alt="{{ auth()->user()->name }} profile photo">@else{{ $portalProfileInitial }}@endif</span>
        <span class="portal-profile-copy"><strong>{{ auth()->user()->name }}</strong><small>{{ $portalRoleLabel }} · {{ auth()->user()->course }}@if($portalRoleValue === 'student' && auth()->user()->academicSection) · {{ auth()->user()->academicSection->name }}@endif</small></span>
    </span>
    </div>
</div>

@push('portal-profile-overlay')
<div class="admin-profile-modal" data-portal-profile-modal hidden>
    <section class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="portalProfileTitle">
        <header class="admin-profile-header"><div><h2 id="portalProfileTitle">Edit Profile</h2><p>Update your {{ strtolower($portalRoleLabel) }} account's basic information.</p></div><button class="admin-profile-close" type="button" data-close-portal-profile aria-label="Close profile form">&times;</button></header>
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">@csrf @method('PATCH')<input type="hidden" name="profile_modal" value="1">
            <div class="admin-profile-form-grid">
                <div class="admin-profile-photo"><div class="admin-profile-photo-preview" data-profile-photo-preview>@if(auth()->user()->profile_photo_path)<img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}" alt="Current profile photo">@else{{ $portalProfileInitial }}@endif</div><div class="admin-profile-photo-copy"><strong>Profile picture</strong><p>Upload a JPG, PNG, or WebP image up to 50 MB.</p><label class="admin-profile-photo-button">Choose Image<input class="admin-profile-photo-input" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" data-profile-photo-input></label>@error('profile_photo')<span class="admin-profile-error">{{ $message }}</span>@enderror</div></div>
                <div class="admin-profile-field"><label>First name</label><input class="input" name="first_name" value="{{ old('first_name',auth()->user()->first_name) }}" required>@error('first_name')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label>Middle name</label><input class="input" name="middle_name" value="{{ old('middle_name',auth()->user()->middle_name) }}">@error('middle_name')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label>Last name</label><input class="input" name="last_name" value="{{ old('last_name',auth()->user()->last_name) }}" required>@error('last_name')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label>Suffix</label><input class="input" name="suffix" value="{{ old('suffix',auth()->user()->suffix) }}" placeholder="e.g. Jr., Sr., III">@error('suffix')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label>Email address</label><input class="input" type="email" name="email" value="{{ old('email',auth()->user()->email) }}" required>@error('email')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label>Account role</label><input class="input" value="{{ $portalRoleLabel }}" readonly></div>
                <div class="admin-profile-field full admin-profile-divider"><strong>Change Password</strong><p>Leave these blank to keep your current password.</p></div>
                <div class="admin-profile-field full"><label>Current password</label><x-password-toggle name="current_password" autocomplete="current-password" />@error('current_password')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label>New password</label><x-password-toggle name="password" />@error('password')<span class="admin-profile-error">{{ $message }}</span>@enderror</div>
                <div class="admin-profile-field"><label>Confirm new password</label><x-password-toggle name="password_confirmation" /></div>
            </div>
            <footer class="admin-profile-actions"><button class="button button-secondary" type="button" data-close-portal-profile>Cancel</button><button class="button" type="submit">Save Changes</button></footer>
        </form>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded',()=>{const modal=document.querySelector('[data-portal-profile-modal]'),menu=document.querySelector('.portal-profile-menu'),open=document.querySelector('[data-open-portal-profile]');if(!modal||!open)return;const close=()=>{modal.hidden=true;document.body.classList.remove('modal-open')},show=()=>{menu?.removeAttribute('open');modal.hidden=false;document.body.classList.add('modal-open')};open.addEventListener('click',show);modal.querySelectorAll('[data-close-portal-profile]').forEach(button=>button.addEventListener('click',close));modal.addEventListener('click',event=>{if(event.target===modal)close()});document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!modal.hidden)close()});const input=modal.querySelector('[data-profile-photo-input]'),preview=modal.querySelector('[data-profile-photo-preview]');input?.addEventListener('change',()=>{const file=input.files?.[0];if(!file)return;const reader=new FileReader();reader.onload=()=>{preview.innerHTML=`<img src="${reader.result}" alt="Selected profile photo">`};reader.readAsDataURL(file)});modal.querySelectorAll('[data-password-eye-toggle]').forEach(button=>{button.addEventListener('click',()=>{const passwordInput=button.previousElementSibling,eyeIcon=button.querySelector('[data-eye-icon]'),eyeOffIcon=button.querySelector('[data-eye-off-icon]');if(!passwordInput)return;const showing=passwordInput.type==='text';passwordInput.type=showing?'password':'text';eyeIcon.hidden=!showing;eyeOffIcon.hidden=showing;button.setAttribute('aria-label',showing?'Show password':'Hide password')})});if(@json((bool) old('profile_modal')))show()});
</script>
@endpush
