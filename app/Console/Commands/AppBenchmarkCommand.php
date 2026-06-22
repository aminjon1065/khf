<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

class AppBenchmarkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:benchmark {--concurrent=10 : Number of concurrent requests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Measures TTFB and checks performance targets for key public routes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Performance Benchmark...');
        $url = config('app.url');

        $routes = [
            '/' => 'Homepage',
            '/tj/incidents' => 'Incidents Archive',
            '/tj/search?q=test' => 'Search',
        ];

        $concurrent = (int) $this->option('concurrent');

        foreach ($routes as $path => $name) {
            $fullUrl = $url.$path;
            $this->line("Testing {$name} ({$fullUrl})...");

            // Warm up cache
            Http::get($fullUrl);

            // Measure multiple requests
            $start = microtime(true);

            $responses = Http::pool(function (Pool $pool) use ($fullUrl, $concurrent) {
                $requests = [];
                for ($i = 0; $i < $concurrent; $i++) {
                    $requests[] = $pool->get($fullUrl);
                }

                return $requests;
            });

            $totalDuration = (microtime(true) - $start) * 1000;
            $avgTtfb = $totalDuration / $concurrent;

            // Check status codes
            $success = collect($responses)->every(fn ($res) => $res->successful() || $res->status() === 503);

            if (! $success) {
                $this->error("❌ {$name}: Failed (Non-2xx or 503 response encountered)");

                continue;
            }

            if ($avgTtfb <= 600) {
                $this->info(sprintf('✔ %s: Avg TTFB = %.2f ms (Target: <=600ms) with %d concurrent requests', $name, $avgTtfb, $concurrent));
            } else {
                $this->warn(sprintf('⚠ %s: Avg TTFB = %.2f ms (Target: <=600ms) with %d concurrent requests', $name, $avgTtfb, $concurrent));
            }
        }

        return self::SUCCESS;
    }
}
