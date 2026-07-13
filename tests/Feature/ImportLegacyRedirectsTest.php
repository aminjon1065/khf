<?php

use App\Models\Redirect;
use App\Support\RedirectResolver;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    RedirectResolver::clearCache();
    Cache::flush();

    $this->csvPath = storage_path('framework/testing/legacy-redirects.csv');
    File::ensureDirectoryExists(dirname($this->csvPath));
});

afterEach(function () {
    if (isset($this->csvPath) && File::exists($this->csvPath)) {
        File::delete($this->csvPath);
    }
});

it('imports redirects from csv and applies them publicly', function () {
    File::put($this->csvPath, <<<'CSV'
from_path,to_url,status_code,notes
tj/node/42,/tj/news/imported-post,301,Imported
/ru/old-about,/ru/pages/about,301,
CSV);

    $exitCode = Artisan::call('redirects:import', [
        'path' => $this->csvPath,
    ]);

    expect($exitCode)->toBe(0)
        ->and(Redirect::query()->where('from_path', 'tj/node/42')->exists())->toBeTrue()
        ->and(Redirect::query()->where('from_path', 'ru/old-about')->value('to_url'))->toBe('/ru/pages/about');

    $this->get('/tj/node/42')
        ->assertRedirect('/tj/news/imported-post')
        ->assertStatus(301);
});

it('updates existing from_path rows by default', function () {
    Redirect::factory()->create([
        'from_path' => 'tj/legacy',
        'to_url' => '/tj/news/old',
    ]);

    File::put($this->csvPath, <<<'CSV'
from_path,to_url,status_code,notes
tj/legacy,/tj/news/new,301,Updated via import
CSV);

    Artisan::call('redirects:import', ['path' => $this->csvPath]);

    expect(Redirect::query()->where('from_path', 'tj/legacy')->value('to_url'))->toBe('/tj/news/new')
        ->and(Redirect::query()->where('from_path', 'tj/legacy')->count())->toBe(1);
});

it('skips existing rows when --skip-existing is set', function () {
    Redirect::factory()->create([
        'from_path' => 'tj/keep',
        'to_url' => '/tj/news/original',
    ]);

    File::put($this->csvPath, <<<'CSV'
from_path,to_url
tj/keep,/tj/news/should-not-apply
CSV);

    Artisan::call('redirects:import', [
        'path' => $this->csvPath,
        '--skip-existing' => true,
    ]);

    expect(Redirect::query()->where('from_path', 'tj/keep')->value('to_url'))->toBe('/tj/news/original');
});

it('dry-runs without writing rows', function () {
    File::put($this->csvPath, <<<'CSV'
from_path,to_url
tj/dry-run,/tj/news/dry
CSV);

    Artisan::call('redirects:import', [
        'path' => $this->csvPath,
        '--dry-run' => true,
    ]);

    expect(Redirect::query()->where('from_path', 'tj/dry-run')->exists())->toBeFalse();
});

it('rejects csv files without required columns', function () {
    File::put($this->csvPath, <<<'CSV'
source,destination
a,b
CSV);

    $exitCode = Artisan::call('redirects:import', [
        'path' => $this->csvPath,
    ]);

    expect($exitCode)->toBe(1);
});

it('imports the bundled example csv', function () {
    $exitCode = Artisan::call('redirects:import', [
        'path' => 'database/data/legacy-redirects.example.csv',
    ]);

    expect($exitCode)->toBe(0)
        ->and(Redirect::query()->count())->toBe(3);

    $this->get('/tj/node/123')
        ->assertRedirect('/tj/news/example-slug')
        ->assertStatus(301);
});
