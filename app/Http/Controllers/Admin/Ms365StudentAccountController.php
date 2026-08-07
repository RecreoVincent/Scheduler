<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ms365StudentAccount;
use App\Services\Ms365StudentAccountImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class Ms365StudentAccountController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $accounts = Ms365StudentAccount::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('email','like',"%{$search}%")->orWhere('display_name','like',"%{$search}%")))
            ->orderBy('display_name')->paginate(20)->withQueryString();
        $statistics = ['total'=>Ms365StudentAccount::count(),'eligible'=>Ms365StudentAccount::where('is_blocked',false)->whereNull('soft_deleted_at')->count(),'blocked'=>Ms365StudentAccount::where('is_blocked',true)->count()];
        return view('admin.ms365-accounts.index', compact('accounts','statistics','search'));
    }

    public function import(Request $request, Ms365StudentAccountImporter $importer): RedirectResponse
    {
        $request->validate(['csv_file'=>['required','file','mimes:csv,txt','max:51200']]);
        try { $result = $importer->import($request->file('csv_file')->getRealPath()); }
        catch (RuntimeException $exception) { return back()->with('error',$exception->getMessage()); }
        return back()->with('success',"MS365 registry updated: {$result['imported']} imported, {$result['skipped']} skipped.");
    }
}
