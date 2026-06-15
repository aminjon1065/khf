<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ApiTokenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A bearer token for the internal read API (ТЗ §10.9). The plaintext token is shown once at
 * creation and never stored — only its SHA-256 hash is persisted, so a database leak cannot reveal
 * usable tokens. Tokens are minted with the `api:token` command and can carry an optional expiry.
 *
 * @property int $id
 * @property string $name
 * @property string $token
 * @property Carbon|null $last_used_at
 * @property Carbon|null $expires_at
 */
class ApiToken extends Model
{
    /** @use HasFactory<ApiTokenFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'token',
        'last_used_at',
        'expires_at',
    ];

    /** @var list<string> */
    protected $hidden = [
        'token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Mint a new token, returning the model and the one-time plaintext to hand to the integrator.
     *
     * @return array{token: ApiToken, plainText: string}
     */
    public static function generate(string $name, ?CarbonInterface $expiresAt = null): array
    {
        $plainText = Str::random(48);

        $token = static::create([
            'name' => $name,
            'token' => hash('sha256', $plainText),
            'expires_at' => $expiresAt,
        ]);

        return ['token' => $token, 'plainText' => $plainText];
    }

    /**
     * Resolve a live (non-expired) token from its plaintext bearer value, or null.
     */
    public static function findByPlainText(string $plainText): ?ApiToken
    {
        $token = static::query()->where('token', hash('sha256', $plainText))->first();

        if ($token === null || $token->isExpired()) {
            return null;
        }

        return $token;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function markAsUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->save();
    }
}
