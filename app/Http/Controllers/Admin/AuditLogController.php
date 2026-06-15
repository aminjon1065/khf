<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Category;
use App\Models\Document;
use App\Models\Guide;
use App\Models\Incident;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * Read-only viewer over the activity log (ТЗ §7.10, §12.7). The log is append-only — there are no
 * update/delete routes — so it stays tamper-resistant for security review. Covers both CMS content
 * changes (created/updated/deleted/restored) and authentication events (login/logout/failed/2FA).
 */
class AuditLogController extends Controller
{
    /**
     * Humanised model labels for the polymorphic subject (Russian CMS interface, §7.1).
     *
     * @var array<class-string, string>
     */
    private const SUBJECT_LABELS = [
        Post::class => 'Новость',
        Page::class => 'Страница',
        Category::class => 'Рубрика',
        Incident::class => 'Событие ЧС',
        Alert::class => 'Оповещение',
        Document::class => 'Документ',
        Guide::class => 'Памятка',
        User::class => 'Пользователь',
    ];

    /**
     * Humanised event labels.
     *
     * @var array<string, string>
     */
    private const EVENT_LABELS = [
        'created' => 'Создание',
        'updated' => 'Изменение',
        'deleted' => 'Удаление',
        'restored' => 'Восстановление',
        'login' => 'Вход',
        'logout' => 'Выход',
        'login_failed' => 'Неудачный вход',
        'lockout' => 'Блокировка',
        'two_factor_enabled' => '2FA включена',
        'two_factor_confirmed' => '2FA подтверждена',
        'two_factor_disabled' => '2FA отключена',
    ];

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $event = array_key_exists((string) $request->string('event'), self::EVENT_LABELS)
            ? (string) $request->string('event')
            : null;
        $log = in_array((string) $request->string('log'), ['default', 'auth'], true)
            ? (string) $request->string('log')
            : null;

        $logs = Activity::query()
            ->with('causer:id,name,email')
            ->when($event !== null, fn (Builder $query) => $query->where('event', $event))
            ->when($log !== null, fn (Builder $query) => $query->where('log_name', $log))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('description', 'like', "%{$search}%")
                ->orWhereHas('causer', fn (Builder $causer) => $causer
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))))
            ->latest()
            ->paginate(30)
            ->withQueryString()
            ->through(fn (Activity $activity): array => $this->present($activity));

        return Inertia::render('admin/audit-logs/index', [
            'logs' => $logs,
            'filters' => ['search' => $search, 'event' => $event, 'log' => $log],
            'events' => $this->eventOptions(),
        ]);
    }

    /**
     * Shape one activity row for the viewer.
     *
     * @return array<string, mixed>
     */
    private function present(Activity $activity): array
    {
        /** @var array<string, mixed> $properties */
        $properties = $activity->properties->toArray();

        return [
            'id' => $activity->id,
            'event' => $activity->event,
            'event_label' => self::EVENT_LABELS[$activity->event] ?? $activity->event,
            'log_name' => $activity->log_name,
            'subject_label' => $activity->subject_type ? (self::SUBJECT_LABELS[$activity->subject_type] ?? class_basename($activity->subject_type)) : null,
            'subject_id' => $activity->subject_id,
            'description' => $activity->description,
            'causer' => $activity->causer?->getAttribute('name'),
            'causer_email' => $activity->causer?->getAttribute('email'),
            'ip' => $properties['ip'] ?? null,
            'changes' => array_keys((array) ($properties['attributes'] ?? [])),
            'created_at' => $activity->created_at?->format('d.m.Y H:i'),
        ];
    }

    /**
     * Event filter options.
     *
     * @return list<array{value: string, label: string}>
     */
    private function eventOptions(): array
    {
        return array_map(
            fn (string $value, string $label): array => ['value' => $value, 'label' => $label],
            array_keys(self::EVENT_LABELS),
            array_values(self::EVENT_LABELS),
        );
    }
}
