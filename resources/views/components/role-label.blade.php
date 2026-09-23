@props(['role'])

@php
    $normalizedRole = strtolower((string) $role);
@endphp

{{ $normalizedRole === 'dean' ? 'Dean / Program Head' : ($normalizedRole === 'gec' ? 'GEC' : ucfirst($normalizedRole)) }}
