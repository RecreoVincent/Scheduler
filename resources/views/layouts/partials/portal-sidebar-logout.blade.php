@php $portalLogoutRole = strtolower((string) auth()->user()->role); @endphp
<form class="portal-sidebar-logout" method="POST" action="{{ route('logout') }}">
    @csrf
    <input type="hidden" name="role" value="{{ $portalLogoutRole }}">
    <button class="portal-sidebar-logout-button" type="submit"><x-icon name="logout" /><span>Logout</span></button>
</form>
