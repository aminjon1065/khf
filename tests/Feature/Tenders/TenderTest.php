<?php

use App\Enums\AppealStatus;
use App\Enums\Role;
use App\Models\Tender;
use App\Models\TenderBid;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);
    Storage::fake('local');
});

function openTender(string $slug = 'zakupka', string $locale = 'tj'): Tender
{
    $tender = Tender::factory()->create();
    $tender->upsertTranslations([$locale => ['title' => 'Закупка оборудования', 'slug' => $slug]]);

    return $tender;
}

function bidForm(array $overrides = []): array
{
    return array_merge([
        'company_name' => 'ООО «Ромашка»',
        'contact_name' => 'Иван Иванов',
        'email' => 'bid@example.com',
        'phone' => '+992900000000',
        'proposal' => 'Наше коммерческое предложение.',
        'document' => UploadedFile::fake()->create('bid.pdf', 200, 'application/pdf'),
        'website' => '',
    ], $overrides);
}

it('renders the public tenders list with open tenders', function () {
    openTender();

    $this->get(route('tenders.index', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('public/tenders/index')->has('tenders.data', 1));
});

it('lists only published, open tenders', function () {
    openTender('open-tender');

    $draft = Tender::factory()->draft()->create();
    $draft->upsertTranslations(['tj' => ['title' => 'Черновик', 'slug' => 'draft-tender']]);

    $closed = Tender::factory()->closed()->create();
    $closed->upsertTranslations(['tj' => ['title' => 'Закрыто', 'slug' => 'closed-tender']]);

    $this->get(route('tenders.index', ['locale' => 'tj']))
        ->assertInertia(fn (Assert $page) => $page->has('tenders.data', 1));
});

it('shows a tender by its localized slug', function () {
    $tender = openTender();

    $this->get(route('tenders.show', ['locale' => 'tj', 'slug' => 'zakupka']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/tenders/show')
            ->where('tender.id', $tender->id)
            ->where('tender.is_open', true)
        );
});

it('accepts an online bid with a document and assigns a reference', function () {
    $tender = openTender();

    $this->post(route('tenders.bid', ['locale' => 'tj', 'tender' => $tender->id]), bidForm())
        ->assertRedirect(route('tenders.show', ['locale' => 'tj', 'slug' => 'zakupka']))
        ->assertSessionHas('bid_reference');

    $bid = TenderBid::first();
    $document = $bid?->getFirstMedia(TenderBid::DOCUMENT_COLLECTION);

    expect($bid)->not->toBeNull()
        ->and($bid->status)->toBe(AppealStatus::New)
        ->and($bid->reference)->toStartWith('TND-')
        ->and($bid->tender_id)->toBe($tender->id)
        ->and($document)->not->toBeNull()
        ->and($document->disk)->toBe('local');
});

it('rejects a bid with the honeypot filled', function () {
    $tender = openTender();

    $this->post(route('tenders.bid', ['locale' => 'tj', 'tender' => $tender->id]), bidForm(['website' => 'http://spam']))
        ->assertSessionHasErrors('website');

    expect(TenderBid::count())->toBe(0);
});

it('validates required bid fields including the document', function () {
    $tender = openTender();

    $this->post(route('tenders.bid', ['locale' => 'tj', 'tender' => $tender->id]), bidForm([
        'company_name' => '',
        'contact_name' => '',
        'email' => '',
        'document' => null,
    ]))->assertSessionHasErrors(['company_name', 'contact_name', 'email', 'document']);
});

it('rejects a bid to a closed tender', function () {
    $closed = Tender::factory()->closed()->create();
    $closed->upsertTranslations(['tj' => ['title' => 'Закрыто', 'slug' => 'closed-tender']]);

    $this->post(route('tenders.bid', ['locale' => 'tj', 'tender' => $closed->id]), bidForm())
        ->assertNotFound();

    expect(TenderBid::count())->toBe(0);
});

it('tracks a bid by reference', function () {
    $bid = TenderBid::factory()->create();

    $this->get(route('tenders.track', ['locale' => 'tj', 'reference' => $bid->reference]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/tenders/track')
            ->where('result.found', true)
        );

    $this->get(route('tenders.track', ['locale' => 'tj', 'reference' => 'TND-2026-NOPE00']))
        ->assertInertia(fn (Assert $page) => $page->where('result.found', false));
});

it('restricts the CMS bids queue to staff with permission', function () {
    $this->get(route('admin.tender-bids.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('admin.tender-bids.index'))
        ->assertForbidden();
});

it('lets a moderator view, update and download a bid', function () {
    $moderator = User::factory()->withTwoFactor()->create();
    $moderator->assignRole(Role::Moderator->value);

    $tender = openTender('zakupka-ru', 'ru');
    $bid = TenderBid::factory()->create(['tender_id' => $tender->id]);
    $bid->addMedia(UploadedFile::fake()->create('bid.pdf', 100, 'application/pdf'))
        ->toMediaCollection(TenderBid::DOCUMENT_COLLECTION);

    $this->actingAs($moderator)->get(route('admin.tender-bids.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/tender-bids/index')->has('bids.data', 1));

    $this->actingAs($moderator)
        ->put(route('admin.tender-bids.update', $bid), [
            'status' => 'in_progress',
            'assigned_to' => $moderator->id,
            'internal_note' => 'Взято в работу',
        ])
        ->assertRedirect(route('admin.tender-bids.show', $bid));

    expect($bid->fresh()->status)->toBe(AppealStatus::InProgress)
        ->and($bid->fresh()->assigned_to)->toBe($moderator->id);

    $this->actingAs($moderator)->get(route('admin.tender-bids.document', $bid))->assertOk();
});
