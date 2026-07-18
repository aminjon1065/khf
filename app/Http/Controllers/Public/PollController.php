<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePollVoteRequest;
use App\Models\Poll;
use App\Models\PollVote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PollController extends Controller
{
    /**
     * Public polls listing (ТЗ §8, §20 «к»).
     */
    public function index(): Response
    {
        $locale = app()->getLocale();
        $voterHash = $this->voterHash(request());

        $polls = Poll::published()
            ->with(['translations', 'options.translations'])
            ->withCount('votes')
            ->orderByDesc('starts_at')
            ->orderBy('sort_order')
            ->get();
        $votedPollIds = PollVote::query()
            ->whereIn('poll_id', $polls->pluck('id'))
            ->where('voter_hash', $voterHash)
            ->pluck('poll_id')
            ->flip();

        $polls = $polls
            ->map(function (Poll $poll) use ($locale, $votedPollIds): ?array {
                $translation = $poll->translation($locale);

                if ($translation === null || blank($translation->title)) {
                    return null;
                }

                return [
                    'id' => $poll->id,
                    'slug' => $translation->slug,
                    'title' => $translation->title,
                    'description' => $translation->description,
                    'type' => $poll->type->value,
                    'type_label' => $poll->type->label(),
                    'is_active' => $poll->isAcceptingVotes(),
                    'has_ended' => $poll->hasEnded(),
                    'has_voted' => $votedPollIds->has($poll->id),
                    'starts_at' => $poll->starts_at?->toIso8601String(),
                    'ends_at' => $poll->ends_at?->toIso8601String(),
                    'total_votes' => $poll->votes_count,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return Inertia::render('public/polls/index', [
            'polls' => $polls,
        ]);
    }

    /**
     * Single poll page with voting form or results.
     */
    public function show(Request $request, string $locale, string $slug): Response
    {
        $poll = Poll::published()
            ->whereHas('translations', fn ($query) => $query->where('locale', $locale)->where('slug', $slug))
            ->with(['translations', 'options.translations'])
            ->firstOrFail();

        $translation = $poll->translation($locale);
        $voterHash = $this->voterHash($request);
        $hasVoted = PollVote::query()
            ->where('poll_id', $poll->id)
            ->where('voter_hash', $voterHash)
            ->exists();

        $voteCounts = $poll->voteCounts();
        $totalVotes = $poll->totalVotes();
        $showResults = $poll->resultsVisible($hasVoted);

        $options = $poll->options
            ->map(function ($option) use ($locale, $voteCounts, $totalVotes, $showResults) {
                $label = $option->translation($locale)?->label;

                if (blank($label)) {
                    return null;
                }

                $votes = $voteCounts[$option->id] ?? 0;

                return [
                    'id' => $option->id,
                    'label' => $label,
                    'votes' => $showResults ? $votes : null,
                    'percentage' => $showResults && $totalVotes > 0
                        ? round(($votes / $totalVotes) * 100, 1)
                        : null,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return Inertia::render('public/polls/show', [
            'poll' => [
                'id' => $poll->id,
                'slug' => $translation?->slug,
                'title' => $translation?->title,
                'description' => $translation?->description,
                'type' => $poll->type->value,
                'type_label' => $poll->type->label(),
                'is_active' => $poll->isAcceptingVotes(),
                'has_ended' => $poll->hasEnded(),
                'has_voted' => $hasVoted,
                'show_results' => $showResults,
                'starts_at' => $poll->starts_at?->toIso8601String(),
                'ends_at' => $poll->ends_at?->toIso8601String(),
                'total_votes' => $showResults ? $totalVotes : null,
                'options' => $options,
            ],
        ]);
    }

    public function vote(StorePollVoteRequest $request, string $locale, string $slug): RedirectResponse
    {
        $poll = Poll::published()
            ->whereHas('translations', fn ($query) => $query->where('locale', $locale)->where('slug', $slug))
            ->with('options')
            ->firstOrFail();

        if (! $poll->isAcceptingVotes()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('ui.polls.closed')]);

            return back();
        }

        $voterHash = $this->voterHash($request);
        $optionId = (int) $request->validated('poll_option_id');

        if (! $poll->options->contains('id', $optionId)) {
            abort(422);
        }

        $vote = PollVote::query()->firstOrCreate(
            [
                'poll_id' => $poll->id,
                'voter_hash' => $voterHash,
            ],
            [
                'poll_option_id' => $optionId,
                'created_at' => now(),
            ],
        );

        if (! $vote->wasRecentlyCreated) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('ui.polls.already_voted')]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('ui.polls.vote_recorded')]);

        return back();
    }

    private function voterHash(Request $request): string
    {
        return hash('sha256', $request->ip().'|'.$request->userAgent());
    }
}
