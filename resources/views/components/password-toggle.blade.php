@props(['name', 'id' => null, 'autocomplete' => 'new-password'])
<div class="admin-profile-password-wrap">
    <input {{ $attributes->merge(['class' => 'input']) }} @if($id) id="{{ $id }}" @endif type="password" name="{{ $name }}" autocomplete="{{ $autocomplete }}">
    <button type="button" class="admin-profile-password-toggle" data-password-eye-toggle aria-label="Show password">
        <span data-eye-icon><x-icon name="eye" /></span>
        <span data-eye-off-icon hidden><x-icon name="eye-off" /></span>
    </button>
</div>
