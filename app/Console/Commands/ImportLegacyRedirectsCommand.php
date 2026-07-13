<?php

namespace App\Console\Commands;

use App\Services\Admin\LegacyRedirectImporter;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class ImportLegacyRedirectsCommand extends Command
{
    protected $signature = 'redirects:import
                            {path : Path to a CSV file (from_path,to_url[,status_code][,notes])}
                            {--update : Update existing from_path rows (default)}
                            {--skip-existing : Skip rows whose from_path already exists}
                            {--dry-run : Validate and count without writing}';

    protected $description = 'Import legacy 301/302 redirects from CSV into the redirects table (ТЗ §15.1)';

    public function handle(LegacyRedirectImporter $importer): int
    {
        $path = (string) $this->argument('path');

        if (! str_starts_with($path, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
            $path = base_path($path);
        }

        $updateExisting = ! (bool) $this->option('skip-existing');
        $dryRun = (bool) $this->option('dry-run');

        try {
            $result = $importer->importFromCsv($path, $updateExisting, $dryRun);
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $prefix = $dryRun ? '[dry-run] ' : '';

        $this->components->info(
            "{$prefix}Redirects import finished: {$result['created']} created, {$result['updated']} updated, {$result['skipped']} skipped ({$result['total']} rows).",
        );

        return self::SUCCESS;
    }
}
