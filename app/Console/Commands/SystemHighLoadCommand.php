<?php

namespace App\Console\Commands;

use App\Services\SystemLoadService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

class SystemHighLoadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:high-load {status : "on" to enable emergency mode, "off" to disable}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Toggle the application\'s graceful degradation mode under extreme load';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $status = $this->argument('status');

        if (! in_array($status, ['on', 'off'])) {
            $this->error('Invalid status. Use "on" or "off".');

            return Command::FAILURE;
        }

        if ($status === 'on') {
            SystemLoadService::enable();
            $this->info('High Load Mode is ENABLED. Secondary features (search, map history) are now disabled.');
        } else {
            SystemLoadService::disable();
            $this->info('High Load Mode is DISABLED. Full features are restored.');
        }

        return Command::SUCCESS;
    }
}
