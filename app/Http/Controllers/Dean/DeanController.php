<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class DeanController extends Controller
{
    protected function course(Request $request): string
    {
        return strtoupper((string) $request->user()->course);
    }

    protected function ensureCourse(Request $request, object $model): void
    {
        abort_unless(strtoupper((string) $model->course) === $this->course($request), 404);
    }
}
