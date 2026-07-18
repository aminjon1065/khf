<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeployEnvCheckCommand extends Command
{
    protected $signature = 'deploy:env-check
                            {--env= : Environment to validate (defaults to APP_ENV)}
                            {--strict : Fail on warnings as well as errors}';

    protected $description = 'Validate that required secrets and settings are present for staging/production deploy (ТЗ §16.1)';

    /**
     * Map env-var names to config paths (never call env() outside config/).
     *
     * @var array<string, string>
     */
    private const SECRET_CONFIG_MAP = [
        'APP_KEY' => 'app.key',
        'APP_URL' => 'app.url',
        'DB_DATABASE' => 'database.connections.mysql.database',
        'DB_USERNAME' => 'database.connections.mysql.username',
        'DB_PASSWORD' => 'database.connections.mysql.password',
        'VAPID_SUBJECT' => 'webpush.vapid.subject',
        'VAPID_PUBLIC_KEY' => 'webpush.vapid.public_key',
        'VAPID_PRIVATE_KEY' => 'webpush.vapid.private_key',
        'HEALTH_CHECK_TOKEN' => 'deployment.health_check_token',
        'MAIL_MAILER' => 'mail.default',
        'MAIL_FROM_ADDRESS' => 'mail.from.address',
        'MAIL_HOST' => 'mail.mailers.smtp.host',
    ];

    public function handle(): int
    {
        $environment = (string) ($this->option('env') ?: config('app.env'));
        $errors = [];
        $warnings = [];

        if (! in_array($environment, config('deployment.environments'), true)) {
            $errors[] = "Unknown APP_ENV «{$environment}». Expected: ".implode(', ', config('deployment.environments'));
        }

        if (in_array($environment, ['staging', 'production'], true)) {
            if (config('app.debug')) {
                $errors[] = 'APP_DEBUG must be false on staging and production.';
            }

            foreach (config("deployment.required_secrets.{$environment}", []) as $key) {
                $configPath = self::SECRET_CONFIG_MAP[$key] ?? null;
                $value = $configPath !== null ? (string) (config($configPath) ?? '') : '';

                if ($this->isPlaceholder($value)) {
                    $errors[] = "Missing or placeholder value for {$key}.";
                }
            }

            if (! str_starts_with((string) config('app.url'), 'https://')) {
                $errors[] = 'APP_URL must use https:// on staging and production (TLS, §16.3).';
            }

            if (in_array((string) config('mail.default'), ['array', 'log'], true)) {
                $errors[] = 'MAIL_MAILER must deliver externally on staging and production.';
            }

            if (! config('session.encrypt')) {
                $errors[] = 'SESSION_ENCRYPT must be true on staging and production.';
            }

            if (! config('session.secure')) {
                $errors[] = 'SESSION_SECURE_COOKIE must be true on staging and production.';
            }

            if (config('session.driver') === 'array') {
                $errors[] = 'SESSION_DRIVER must persist sessions on staging and production.';
            }

            if (config('queue.default') === 'sync') {
                $errors[] = 'QUEUE_CONNECTION must not be sync on staging and production.';
            }

            if (config('cache.default') === 'array') {
                $errors[] = 'CACHE_STORE must not be array on staging and production.';
            }

            $publicKey = (string) config('webpush.vapid.public_key', '');
            $privateKey = (string) config('webpush.vapid.private_key', '');

            if ($publicKey !== '' && strlen($publicKey) < 80) {
                $warnings[] = 'VAPID_PUBLIC_KEY looks too short — run `php artisan webpush:vapid`.';
            }

            if ($privateKey !== '' && strlen($privateKey) < 40) {
                $warnings[] = 'VAPID_PRIVATE_KEY looks too short — run `php artisan webpush:vapid`.';
            }

            $subject = (string) config('webpush.vapid.subject', '');

            if ($subject !== '' && ! filter_var($subject, FILTER_VALIDATE_URL) && ! str_starts_with($subject, 'mailto:')) {
                $warnings[] = 'VAPID_SUBJECT should be a valid https:// URL or mailto: address (Safari requirement).';
            }
        } else {
            $this->components->info("Skipping strict secret checks for «{$environment}» environment.");

            return self::SUCCESS;
        }

        foreach ($warnings as $warning) {
            $this->components->warn($warning);
        }

        foreach ($errors as $error) {
            $this->components->error($error);
        }

        if ($errors !== []) {
            $this->newLine();
            $this->line('Fix the issues above, then re-run <fg=cyan>php artisan deploy:env-check</>.');

            return self::FAILURE;
        }

        if ($warnings !== [] && $this->option('strict')) {
            $this->components->error('Strict mode: warnings treated as failures.');

            return self::FAILURE;
        }

        $this->components->info("Environment «{$environment}» passed deploy checks.");

        return self::SUCCESS;
    }

    private function isPlaceholder(string $value): bool
    {
        $value = trim($value);

        foreach (config('deployment.placeholder_patterns', []) as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                return true;
            }
        }

        return false;
    }
}
