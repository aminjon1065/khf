<?php

it('skips strict checks for the local testing environment', function () {
    $this->artisan('deploy:env-check')
        ->expectsOutputToContain('Skipping strict secret checks')
        ->assertSuccessful();
});

it('fails production checks when required secrets are missing', function () {
    config([
        'app.debug' => false,
        'app.key' => '',
        'app.url' => 'http://localhost',
        'database.connections.mysql.database' => '',
        'database.connections.mysql.username' => '',
        'database.connections.mysql.password' => '',
        'webpush.vapid.subject' => '',
        'webpush.vapid.public_key' => '',
        'webpush.vapid.private_key' => '',
        'mail.from.address' => 'hello@example.com',
        'mail.mailers.smtp.host' => '',
    ]);

    $this->artisan('deploy:env-check', ['--env' => 'production'])
        ->assertFailed();
});

it('passes production checks when secrets are configured', function () {
    config([
        'app.debug' => false,
        'app.key' => 'base64:'.base64_encode(random_bytes(32)),
        'app.url' => 'https://khf.tj',
        'database.connections.mysql.database' => 'khf_prod',
        'database.connections.mysql.username' => 'khf_user',
        'database.connections.mysql.password' => 'strong-password',
        'webpush.vapid.subject' => 'https://khf.tj',
        'webpush.vapid.public_key' => str_repeat('A', 88),
        'webpush.vapid.private_key' => str_repeat('B', 44),
        'deployment.health_check_token' => str_repeat('H', 64),
        'mail.default' => 'smtp',
        'mail.from.address' => 'noreply@khf.tj',
        'mail.mailers.smtp.host' => 'smtp.khf.tj',
        'session.driver' => 'database',
        'session.encrypt' => true,
        'session.secure' => true,
        'queue.default' => 'database',
        'cache.default' => 'database',
    ]);

    $this->artisan('deploy:env-check', ['--env' => 'production'])
        ->expectsOutputToContain('passed deploy checks')
        ->assertSuccessful();
});

it('fails when APP_DEBUG is true on staging', function () {
    config(['app.debug' => true]);

    $this->artisan('deploy:env-check', ['--env' => 'staging'])
        ->assertFailed();
});

it('fails when production uses non-delivery mail or unencrypted sessions', function () {
    config([
        'app.debug' => false,
        'mail.default' => 'log',
        'session.encrypt' => false,
    ]);

    $this->artisan('deploy:env-check', ['--env' => 'production'])
        ->assertFailed();
});
