<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalLoginTransitionController extends Controller
{
    /** @var array<string, string> */
    private const PORTAL_LABELS = [
        'admin' => 'Administration',
        'dean' => 'Dean / Program Head',
        'gec' => 'General Education Course',
        'instructor' => 'Instructor',
        'student' => 'Student',
    ];

    public function show(Request $request): View
    {
        $portal = (string) $request->route('portal');
        abort_unless(array_key_exists($portal, self::PORTAL_LABELS), 404);

        return $this->transitionView($portal, route("{$portal}.dashboard"));
    }

    public function showLogout(Request $request): View
    {
        $portal = (string) $request->route('portal');

        return $this->transitionView($portal, route('home'), true);
    }

    private function transitionView(string $portal, string $destinationUrl, bool $isLogout = false): View
    {
        abort_unless(array_key_exists($portal, self::PORTAL_LABELS), 404);

        return view('auth.portal-login-transition', [
            'portalLabel' => self::PORTAL_LABELS[$portal],
            'destinationUrl' => $destinationUrl,
            'isLogout' => $isLogout,
        ]);
    }
}
