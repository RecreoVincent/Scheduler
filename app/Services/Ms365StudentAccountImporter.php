<?php

namespace App\Services;

use App\Models\Ms365StudentAccount;
use Illuminate\Support\Carbon;
use RuntimeException;

class Ms365StudentAccountImporter
{
    public function import(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) throw new RuntimeException('The CSV file could not be opened.');

        $headers = fgetcsv($handle);
        if (! $headers) throw new RuntimeException('The CSV file is empty.');
        $headers = array_map(fn ($value) => trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B"), $headers);
        $required = ['User principal name', 'Display name'];
        foreach ($required as $column) if (! in_array($column, $headers, true)) throw new RuntimeException("Missing required CSV column: {$column}");

        $imported = 0; $skipped = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $row = array_pad($row, count($headers), null);
            $data = array_combine($headers, array_slice($row, 0, count($headers)));
            $email = strtolower(ltrim(trim((string) ($data['User principal name'] ?? '')), '`'));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) { $skipped++; continue; }

            Ms365StudentAccount::updateOrCreate(['email'=>$email], [
                'display_name'=>$this->clean($data['Display name'] ?? null),
                'first_name'=>$this->clean($data['First name'] ?? null),
                'last_name'=>$this->clean($data['Last name'] ?? null),
                'object_id'=>$this->clean($data['Object Id'] ?? null),
                'license'=>$this->clean($data['Licenses'] ?? null),
                'is_blocked'=>strtolower(trim((string) ($data['Block credential'] ?? 'false'))) === 'true',
                'soft_deleted_at'=>$this->date($data['Soft deletion time stamp'] ?? null),
                'ms365_created_at'=>$this->date($data['When created'] ?? null),
                'last_imported_at'=>now(),
            ]);
            $imported++;
        }
        fclose($handle);
        return compact('imported','skipped');
    }

    private function clean(mixed $value): ?string
    {
        $value = ltrim(trim((string) $value), '`');
        return $value === '' ? null : $value;
    }

    private function date(mixed $value): ?Carbon
    {
        $value = $this->clean($value);
        return $value ? Carbon::parse($value) : null;
    }
}
