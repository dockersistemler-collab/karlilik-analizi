<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

class EInvoiceRepair extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'einvoice:repair';

    /**
     * The console command description.
     */
    protected $description = 'Repairs e-invoice migrations and ensures e_* tables exist';

    public function handle(): int
    {
        $migrationFiles = File::files(database_path('migrations'));
        $einvoiceMigrations = collect($migrationFiles)
            ->map(fn ($file) => $file->getFilename())
            ->filter(function (string $filename) {
                return str_starts_with($filename, '2026_01_31_1600')
                    && str_contains($filename, 'einvoice');
            })
            ->map(fn (string $filename) => pathinfo($filename, PATHINFO_FILENAME))
            ->values()
            ->all();

        if (count($einvoiceMigrations) === 0) {
            $this->warn('No matching einvoice migrations found (2026_01_31_1600xx_*).');
        } else {
            $deleted = DB::table('migrations')
                ->whereIn('migration', $einvoiceMigrations)
                ->delete();

            $this->info("Deleted {$deleted} migration row(s) from migrations table.");
            Log::info('einvoice.repair.deleted_migrations', [
                'deleted' => $deleted,
                'migrations' => $einvoiceMigrations,
            ]);
        }
$this->info('Running: migrate --path=database/migrations --force');

        try {
            Artisan::call('migrate', [
                '--path' => 'database/migrations',
                '--force' => true,
            ]);
        } catch (Throwable $e) {
            $this->error('Migration failed: '.$e->getMessage());
            Log::error('einvoice.repair.migrate_failed', ['message' => $e->getMessage()]);
            return self::FAILURE;
        }
$this->line(trim((string) Artisan::output()));

        try {
            $result = DB::select("SHOW TABLES LIKE 'e_invoices'");
            $this->info('SHOW TABLES LIKE \'e_invoices\': '.json_encode($result, JSON_UNESCAPED_UNICODE));
            Log::info('einvoice.repair.show_tables', ['result' => $result]);
        } catch (Throwable $e) {
            $this->error("Failed to run SHOW TABLES LIKE 'e_invoices': ".$e->getMessage());
            Log::error('einvoice.repair.show_tables_failed', ['message' => $e->getMessage()]);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

