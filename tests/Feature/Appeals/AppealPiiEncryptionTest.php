<?php

use App\Models\Appeal;
use Illuminate\Support\Facades\DB;

it('stores appeal personal data encrypted at rest but reads it back transparently', function () {
    $appeal = Appeal::factory()->create([
        'email' => 'citizen@example.tj',
        'phone' => '+992 900 00 00 00',
        'message' => 'Sensitive personal detail in the appeal body.',
        'name' => 'Ali Valiev',
        'internal_note' => 'Private staff note.',
    ]);

    $raw = DB::table('appeals')->where('id', $appeal->id)->first();

    // Ciphertext on disk...
    expect($raw->email)->not->toBe('citizen@example.tj')
        ->and($raw->phone)->not->toBe('+992 900 00 00 00')
        ->and($raw->message)->not->toContain('Sensitive personal detail')
        ->and($raw->internal_note)->not->toContain('Private staff note')
        // ...but name stays plaintext so the moderator queue can search it.
        ->and($raw->name)->toBe('Ali Valiev');

    // Transparent decryption through the model.
    $fresh = $appeal->fresh();
    expect($fresh->email)->toBe('citizen@example.tj')
        ->and($fresh->phone)->toBe('+992 900 00 00 00')
        ->and($fresh->message)->toBe('Sensitive personal detail in the appeal body.')
        ->and($fresh->internal_note)->toBe('Private staff note.');
});
