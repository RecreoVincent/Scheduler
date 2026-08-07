<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class DeanController extends Controller
{
    protected function course(Request $request): string
    {
        return strtoupper((string) ($request->user()->department?->code ?? $request->user()->course));
    }

    protected function ensureCourse(Request $request, object $model): void
    {
        $modelDepartmentId = $model->department_id ?? $model->department?->id;
        abort_unless(
            $modelDepartmentId
                ? (int) $modelDepartmentId === (int) $request->user()->department_id
                : strtoupper((string) $model->course) === $this->course($request),
            404,
        );
    }
}
