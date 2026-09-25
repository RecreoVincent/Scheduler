<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->usesPortalId()) {
            return [
                'portal_id' => ['required', 'string', 'max:30'],
                'password' => ['required', 'string'],
                'role' => ['required', Rule::in(['instructor', 'student'])],
            ];
        }

        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'role' => ['nullable', Rule::in(['admin', 'dean', 'gec', 'instructor', 'student'])],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): string
    {
        $this->ensureIsNotRateLimited();
        $guard = $this->guardName();

        $credentials = $this->usesPortalId()
            ? [$this->portalIdColumn() => trim($this->string('portal_id')->toString()), 'password' => $this->input('password')]
            : $this->only('email', 'password');

        if (! Auth::guard($guard)->attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                $this->credentialField() => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Auth::shouldUse($guard);

        return $guard;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            $this->credentialField() => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string($this->credentialField())).'|'.$this->guardName().'|'.$this->ip());
    }

    public function guardName(): string
    {
        $role = strtolower($this->string('role')->toString());

        return in_array($role, ['admin', 'dean', 'gec', 'instructor', 'student'], true) ? $role : 'web';
    }

    private function usesPortalId(): bool
    {
        return in_array(strtolower($this->string('role')->toString()), ['instructor', 'student'], true);
    }

    private function portalIdColumn(): string
    {
        return strtolower($this->string('role')->toString()) === 'instructor'
            ? 'instructor_id'
            : 'student_id';
    }

    private function credentialField(): string
    {
        return $this->usesPortalId() ? 'portal_id' : 'email';
    }
}
