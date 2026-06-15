<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Events\Dispatcher;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;

/**
 * Records authentication and two-factor security events into the activity log (ТЗ §12.7), so the
 * audit trail covers not just CMS content changes but who signed in, failed to sign in, signed out,
 * and toggled two-factor — each stamped with the request IP and user agent. The log is append-only
 * (no update/delete routes), which keeps it tamper-resistant for review.
 */
class LogAuthenticationActivity
{
    public function handleLogin(Login $event): void
    {
        $this->record($event->user, 'login', 'Вход в систему');
    }

    public function handleLogout(Logout $event): void
    {
        $this->record($event->user, 'logout', 'Выход из системы');
    }

    public function handleFailed(Failed $event): void
    {
        activity('auth')
            ->withProperties($this->context([
                'email' => $event->credentials['email'] ?? null,
            ]))
            ->event('login_failed')
            ->log('Неудачная попытка входа');
    }

    public function handleLockout(Lockout $event): void
    {
        activity('auth')
            ->withProperties($this->context())
            ->event('lockout')
            ->log('Блокировка входа из-за превышения числа попыток');
    }

    public function handleTwoFactorEnabled(TwoFactorAuthenticationEnabled $event): void
    {
        $this->record($event->user, 'two_factor_enabled', 'Двухфакторная аутентификация включена');
    }

    public function handleTwoFactorConfirmed(TwoFactorAuthenticationConfirmed $event): void
    {
        $this->record($event->user, 'two_factor_confirmed', 'Двухфакторная аутентификация подтверждена');
    }

    public function handleTwoFactorDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        $this->record($event->user, 'two_factor_disabled', 'Двухфакторная аутентификация отключена');
    }

    /**
     * Map the security events this subscriber handles to their handler methods.
     *
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            Lockout::class => 'handleLockout',
            TwoFactorAuthenticationEnabled::class => 'handleTwoFactorEnabled',
            TwoFactorAuthenticationConfirmed::class => 'handleTwoFactorConfirmed',
            TwoFactorAuthenticationDisabled::class => 'handleTwoFactorDisabled',
        ];
    }

    private function record(?Authenticatable $user, string $event, string $description): void
    {
        activity('auth')
            ->causedBy($user)
            ->withProperties($this->context())
            ->event($event)
            ->log($description);
    }

    /**
     * Request context attached to every security entry.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function context(array $extra = []): array
    {
        return array_merge([
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $extra);
    }
}
