<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('api:token {name : Human-readable label for the token (e.g. the integrator name)} {--days= : Days until the token expires (omit for no expiry)}')]
#[Description('Mint a bearer token for the internal API and print it once')]
class MintApiToken extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $days = $this->option('days');

        $expiresAt = $days !== null ? now()->addDays((int) $days) : null;

        ['plainText' => $plainText] = ApiToken::generate($name, $expiresAt);

        $this->components->info("Токен «{$name}» создан.");
        $this->line('Сохраните его сейчас — повторно он показан не будет:');
        $this->newLine();
        $this->line("  <fg=green>{$plainText}</>");
        $this->newLine();

        if ($expiresAt !== null) {
            $this->components->warn('Истекает: '.$expiresAt->toDateTimeString());
        }

        return self::SUCCESS;
    }
}
