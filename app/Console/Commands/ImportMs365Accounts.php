<?php

namespace App\Console\Commands;

use App\Services\Ms365StudentAccountImporter;
use Illuminate\Console\Command;

class ImportMs365Accounts extends Command
{
    protected $signature = 'ms365:import {file : Absolute or project-relative CSV path}';
    protected $description = 'Import the Microsoft 365 student account registry from CSV';

    public function handle(Ms365StudentAccountImporter $importer): int
    {
        $path = $this->argument('file');
        if (! is_file($path)) $path = base_path($path);
        if (! is_file($path)) { $this->error('CSV file not found.'); return self::FAILURE; }
        $result = $importer->import($path);
        $this->info("Imported {$result['imported']} accounts; skipped {$result['skipped']} rows.");
        return self::SUCCESS;
    }
}
