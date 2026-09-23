<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ms365StudentAccount;
use App\Services\Ms365StudentAccountImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class Ms365StudentAccountController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $accounts = Ms365StudentAccount::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('email','like',"%{$search}%")
                ->orWhere('student_number','like',"%{$search}%")
                ->orWhere('display_name','like',"%{$search}%")))
            ->orderBy('display_name')->paginate($this->perPage($request))->withQueryString();
        $statistics = ['total'=>Ms365StudentAccount::count(),'eligible'=>Ms365StudentAccount::where('is_blocked',false)->whereNull('soft_deleted_at')->count(),'blocked'=>Ms365StudentAccount::where('is_blocked',true)->count()];
        $editingAccount = $request->filled('edit')
            ? Ms365StudentAccount::find($request->integer('edit'))
            : null;

        return view('admin.ms365-accounts.index', compact('accounts', 'statistics', 'search', 'editingAccount'));
    }

    public function store(Request $request): RedirectResponse
    {
        Ms365StudentAccount::create($this->validated($request));

        return redirect()->route('admin.ms365-accounts.index')->with('success', 'MS365 registry record created successfully.');
    }

    public function update(Request $request, Ms365StudentAccount $ms365Account): RedirectResponse
    {
        $ms365Account->update($this->validated($request, $ms365Account));

        return redirect()->route('admin.ms365-accounts.index')->with('success', 'MS365 registry record updated successfully.');
    }

    public function destroy(Ms365StudentAccount $ms365Account): RedirectResponse
    {
        $ms365Account->delete();

        return redirect()->route('admin.ms365-accounts.index')
            ->with('success', 'MS365 registry record removed. The Microsoft 365 account itself was not changed.');
    }

    public function import(Request $request, Ms365StudentAccountImporter $importer): RedirectResponse
    {
        $request->validate(['csv_file'=>['required','file','mimes:csv,txt','max:51200']]);
        try { $result = $importer->import($request->file('csv_file')->getRealPath()); }
        catch (RuntimeException $exception) { return back()->with('error',$exception->getMessage()); }
        return back()->with('success',"MS365 registry updated: {$result['imported']} imported, {$result['skipped']} skipped.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Ms365StudentAccount $account = null): array
    {
        $request->merge([
            'email' => Str::lower(Str::squish((string) $request->input('email'))),
            'student_number' => ($studentNumber = Str::upper(Str::squish((string) $request->input('student_number')))) === '' ? null : $studentNumber,
            'display_name' => Str::squish((string) $request->input('display_name')),
            'first_name' => ($firstName = Str::squish((string) $request->input('first_name'))) === '' ? null : $firstName,
            'last_name' => ($lastName = Str::squish((string) $request->input('last_name'))) === '' ? null : $lastName,
            'license' => ($license = Str::squish((string) $request->input('license'))) === '' ? null : $license,
        ]);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('ms365_student_accounts', 'email')->ignore($account?->id)],
            'student_number' => ['nullable', 'string', 'max:30'],
            'display_name' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'license' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['eligible', 'blocked', 'soft_deleted'])],
        ]);

        $status = $validated['status'];
        unset($validated['status']);

        return [
            ...$validated,
            'is_blocked' => $status === 'blocked',
            'soft_deleted_at' => $status === 'soft_deleted'
                ? ($account?->soft_deleted_at ?? now())
                : null,
        ];
    }
}
