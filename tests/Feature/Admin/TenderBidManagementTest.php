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

    $this->moderator = User::factory()->withTwoFactor()->create();
    $this->moderator->assignRole(Role::Moderator->value);
});

it('redirects guests to login', function () {
    $this->get(route('admin.tender-bids.index'))->assertRedirect(route('login'));
});

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.tender-bids.index'))
        ->assertForbidden();
});

it('renders the bids queue and show screen', function () {
    $tender = Tender::factory()->create();
    $tender->upsertTranslations(['ru' => ['title' => 'Закупка', 'slug' => 'zakupka-admin']]);
    $bid = TenderBid::factory()->create(['tender_id' => $tender->id]);

    $this->actingAs($this->moderator)
        ->get(route('admin.tender-bids.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/tender-bids/index')
            ->has('bids.data', 1)
        );

    $this->actingAs($this->moderator)
        ->get(route('admin.tender-bids.show', $bid))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/tender-bids/show')
            ->where('bid.id', $bid->id)
            ->where('bid.company_name', $bid->company_name)
            ->has('staff')
        );
});

it('updates and deletes a bid', function () {
    $bid = TenderBid::factory()->create();

    $this->actingAs($this->moderator)
        ->put(route('admin.tender-bids.update', $bid), [
            'status' => AppealStatus::InProgress->value,
            'assigned_to' => $this->moderator->id,
            'internal_note' => 'Проверка',
        ])
        ->assertRedirect(route('admin.tender-bids.show', $bid));

    expect($bid->fresh()->status)->toBe(AppealStatus::InProgress)
        ->and($bid->fresh()->assigned_to)->toBe($this->moderator->id);

    $this->actingAs($this->moderator)
        ->delete(route('admin.tender-bids.destroy', $bid))
        ->assertRedirect(route('admin.tender-bids.index'));

    expect(TenderBid::query()->find($bid->id))->toBeNull();
});

it('serves the bid document to authorised staff', function () {
    $bid = TenderBid::factory()->create();
    $bid->addMedia(UploadedFile::fake()->create('bid.pdf', 100, 'application/pdf'))
        ->toMediaCollection(TenderBid::DOCUMENT_COLLECTION);

    $this->actingAs($this->moderator)
        ->get(route('admin.tender-bids.document', $bid))
        ->assertOk();
});
