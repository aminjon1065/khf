<?php

use App\Enums\AppealCategory;
use App\Enums\AppealStatus;
use App\Models\Appeal;
use App\Models\Tender;
use App\Models\TenderBid;
use App\Models\TouristGroup;
use App\Models\Vacancy;
use App\Models\VacancyApplication;
use Illuminate\Support\Facades\DB;

it('encrypts vacancy application personal data at rest', function () {
    $application = VacancyApplication::factory()->create([
        'full_name' => 'Ali Valiev',
        'email' => 'ali@example.tj',
        'phone' => '+992900000000',
        'cover_letter' => 'Sensitive application details.',
        'internal_note' => 'Private staff note.',
    ]);

    $raw = DB::table('vacancy_applications')->find($application->id);

    expect($raw->full_name)->toBe('Ali Valiev')
        ->and($raw->email)->not->toBe('ali@example.tj')
        ->and($raw->phone)->not->toBe('+992900000000')
        ->and($raw->cover_letter)->not->toContain('Sensitive application details')
        ->and($raw->internal_note)->not->toContain('Private staff note')
        ->and($application->fresh()->email)->toBe('ali@example.tj')
        ->and($application->fresh()->cover_letter)->toBe('Sensitive application details.')
        ->and($application->fresh()->internal_note)->toBe('Private staff note.');
});

it('encrypts tender bid personal and commercial data at rest', function () {
    $bid = TenderBid::factory()->create([
        'company_name' => 'Rescue Systems LLC',
        'contact_name' => 'Zarina Karimova',
        'email' => 'bid@example.tj',
        'phone' => '+992911111111',
        'proposal' => 'Confidential commercial proposal.',
        'internal_note' => 'Internal evaluation.',
    ]);

    $raw = DB::table('tender_bids')->find($bid->id);

    expect($raw->company_name)->toBe('Rescue Systems LLC')
        ->and($raw->contact_name)->not->toBe('Zarina Karimova')
        ->and($raw->email)->not->toBe('bid@example.tj')
        ->and($raw->phone)->not->toBe('+992911111111')
        ->and($raw->proposal)->not->toContain('Confidential commercial proposal')
        ->and($raw->internal_note)->not->toContain('Internal evaluation')
        ->and($bid->fresh()->contact_name)->toBe('Zarina Karimova')
        ->and($bid->fresh()->proposal)->toBe('Confidential commercial proposal.')
        ->and($bid->fresh()->internal_note)->toBe('Internal evaluation.');
});

it('encrypts tourist group route and contact data at rest', function () {
    $group = TouristGroup::factory()->create([
        'leader_name' => 'Rustam Saidov',
        'leader_phone' => '+992922222222',
        'leader_email' => 'leader@example.tj',
        'route' => 'Confidential mountain route.',
        'equipment' => 'Satellite phone and rescue gear.',
        'start_latitude' => 38.5598,
        'start_longitude' => 68.7870,
        'internal_note' => 'Operational staff note.',
    ]);

    $raw = DB::table('tourist_groups')->find($group->id);
    $fresh = $group->fresh();

    expect($raw->leader_name)->toBe('Rustam Saidov')
        ->and($raw->leader_phone)->not->toBe('+992922222222')
        ->and($raw->leader_email)->not->toBe('leader@example.tj')
        ->and($raw->route)->not->toContain('Confidential mountain route')
        ->and($raw->equipment)->not->toContain('Satellite phone')
        ->and($raw->start_latitude)->not->toBe('38.5598')
        ->and($raw->start_longitude)->not->toBe('68.787')
        ->and($raw->internal_note)->not->toContain('Operational staff note')
        ->and($fresh->leader_phone)->toBe('+992922222222')
        ->and($fresh->route)->toBe('Confidential mountain route.')
        ->and($fresh->start_latitude)->toBe(38.5598)
        ->and($fresh->start_longitude)->toBe(68.787)
        ->and($fresh->internal_note)->toBe('Operational staff note.');
});

it('encrypts plaintext records that existed before the migration', function () {
    $migration = require database_path('migrations/2026_07_16_092557_encrypt_sensitive_submission_fields.php');
    $migration->down();

    $vacancy = Vacancy::factory()->create();
    $tender = Tender::factory()->create();
    $timestamp = now();

    $appealId = DB::table('appeals')->insertGetId([
        'reference' => 'OBR-2026-LEGACY',
        'category' => AppealCategory::General->value,
        'name' => 'Legacy Citizen',
        'email' => 'legacy-appeal@example.tj',
        'phone' => null,
        'subject' => 'Legacy appeal',
        'message' => 'Legacy sensitive message.',
        'status' => AppealStatus::New->value,
        'internal_note' => null,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);

    $applicationId = DB::table('vacancy_applications')->insertGetId([
        'reference' => 'VAC-2026-LEGACY',
        'vacancy_id' => $vacancy->id,
        'full_name' => 'Legacy Applicant',
        'email' => 'legacy-vacancy@example.tj',
        'phone' => null,
        'cover_letter' => 'Legacy cover letter.',
        'status' => AppealStatus::New->value,
        'internal_note' => null,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);

    $bidId = DB::table('tender_bids')->insertGetId([
        'reference' => 'TND-2026-LEGACY',
        'tender_id' => $tender->id,
        'company_name' => 'Legacy Company',
        'contact_name' => 'Legacy Contact',
        'email' => 'legacy-tender@example.tj',
        'phone' => null,
        'proposal' => 'Legacy proposal.',
        'status' => AppealStatus::New->value,
        'internal_note' => null,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);

    $groupId = DB::table('tourist_groups')->insertGetId([
        'reference' => 'TUR-2026-LEGACY',
        'leader_name' => 'Legacy Leader',
        'leader_phone' => '+992933333333',
        'leader_email' => null,
        'participants_count' => 4,
        'route' => 'Legacy route.',
        'equipment' => null,
        'start_date' => '2026-08-01',
        'end_date' => '2026-08-03',
        'region_id' => null,
        'start_latitude' => 38.5,
        'start_longitude' => 68.8,
        'status' => AppealStatus::New->value,
        'assigned_to' => null,
        'internal_note' => null,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);

    $migration->up();

    expect(DB::table('appeals')->find($appealId)->email)
        ->not->toBe('legacy-appeal@example.tj')
        ->and(DB::table('vacancy_applications')->find($applicationId)->email)
        ->not->toBe('legacy-vacancy@example.tj')
        ->and(DB::table('tender_bids')->find($bidId)->contact_name)
        ->not->toBe('Legacy Contact')
        ->and(DB::table('tourist_groups')->find($groupId)->route)
        ->not->toBe('Legacy route.')
        ->and(Appeal::findOrFail($appealId)->message)->toBe('Legacy sensitive message.')
        ->and(VacancyApplication::findOrFail($applicationId)->email)->toBe('legacy-vacancy@example.tj')
        ->and(TenderBid::findOrFail($bidId)->contact_name)->toBe('Legacy Contact')
        ->and(TouristGroup::findOrFail($groupId)->start_latitude)->toBe(38.5);
});
