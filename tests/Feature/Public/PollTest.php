<?php

use App\Enums\ContentStatus;
use App\Enums\PollType;
use App\Models\Poll;
use App\Models\PollVote;
use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
});

function createPublishedPoll(array $overrides = []): Poll
{
    $poll = Poll::factory()->create(array_merge([
        'status' => ContentStatus::Published,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
        'show_results' => true,
    ], $overrides));

    $poll->upsertTranslations([
        'ru' => [
            'title' => 'Гражданский опрос',
            'description' => '<p>Примите участие</p>',
            'slug' => 'grazhdanskij-opros-ru',
        ],
    ]);

    $yes = $poll->options()->create(['sort_order' => 0]);
    $yes->upsertTranslations(['ru' => ['label' => 'Поддерживаю']]);

    $no = $poll->options()->create(['sort_order' => 1]);
    $no->upsertTranslations(['ru' => ['label' => 'Не поддерживаю']]);

    return $poll->load('options');
}

it('renders the public polls index', function () {
    createPublishedPoll();

    $this->get(route('polls.index', ['locale' => 'ru']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/polls/index')
            ->has('polls', 1)
            ->where('polls.0.title', 'Гражданский опрос'));
});

it('renders a poll and records a vote', function () {
    $poll = createPublishedPoll(['type' => PollType::AntiCorruptionExpertise]);
    $optionId = $poll->options->first()->id;

    $this->get(route('polls.show', ['locale' => 'ru', 'slug' => 'grazhdanskij-opros-ru']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/polls/show')
            ->where('poll.title', 'Гражданский опрос')
            ->where('poll.type', PollType::AntiCorruptionExpertise->value)
            ->has('poll.options', 2));

    $this->post(route('polls.vote', ['locale' => 'ru', 'slug' => 'grazhdanskij-opros-ru']), [
        'poll_option_id' => $optionId,
    ])->assertRedirect();

    expect(PollVote::query()->count())->toBe(1);

    $this->post(route('polls.vote', ['locale' => 'ru', 'slug' => 'grazhdanskij-opros-ru']), [
        'poll_option_id' => $optionId,
    ])->assertRedirect();

    expect(PollVote::query()->count())->toBe(1);
});

it('rejects votes when the poll is closed', function () {
    $poll = createPublishedPoll(['ends_at' => now()->subHour()]);
    $optionId = $poll->options->first()->id;

    $this->post(route('polls.vote', ['locale' => 'ru', 'slug' => 'grazhdanskij-opros-ru']), [
        'poll_option_id' => $optionId,
    ])->assertRedirect();

    expect(PollVote::query()->count())->toBe(0);
});

it('rejects honeypot submissions', function () {
    $poll = createPublishedPoll();

    $this->post(route('polls.vote', ['locale' => 'ru', 'slug' => 'grazhdanskij-opros-ru']), [
        'poll_option_id' => $poll->options->first()->id,
        'website' => 'spam',
    ])->assertSessionHasErrors('website');
});
