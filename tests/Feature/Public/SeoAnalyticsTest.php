<?php

use App\Enums\ContentStatus;
use App\Models\Gallery;
use App\Models\Post;
use App\Models\Tender;
use App\Models\Vacancy;

it('generates a valid sitemap.xml', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Published, 'published_at' => now()]);
    $post->translations()->create(['locale' => 'tj', 'title' => 'Test', 'slug' => 'test-tj', 'body' => 'body', 'excerpt' => 'excerpt']);

    $response = $this->get('/sitemap.xml');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');

    // Check if it contains the basic xml structure and standard routes
    $response->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"', false);
    $response->assertSee('<loc>'.url('/en').'</loc>', false);

    // Check if it contains the post route
    $response->assertSee('href="'.url('/tj/news/test-tj').'"', false);
});

it('has a robots.txt file', function () {
    expect(file_exists(public_path('robots.txt')))->toBeTrue();
    $content = file_get_contents(public_path('robots.txt'));
    expect($content)->toContain('User-agent: *');
    expect($content)->toContain('Sitemap: /sitemap.xml');
});

it('redirects legacy URLs via middleware', function () {
    // Override config for testing
    config()->set('redirects', [
        'tj/node/123' => '/tj/news/old-post',
    ]);

    $response = $this->get('/tj/node/123');

    $response->assertRedirect('/tj/news/old-post');
    $response->assertStatus(301);
});

it('adds schema.org json-ld to the app layout', function () {
    // For the home page
    $response = $this->get('/tj');

    $response->assertStatus(200);
    $response->assertSee('application/ld+json', false);
    $response->assertSee('GovernmentOrganization', false);
});

it('adds news article schema to single posts', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Published, 'published_at' => now()]);
    $post->translations()->create(['locale' => 'tj', 'title' => 'Test', 'slug' => 'test-tj', 'body' => 'body', 'excerpt' => 'excerpt']);

    $response = $this->get('/tj/news/test-tj');

    $response->assertStatus(200);
    $response->assertSee('application/ld+json', false);
    $response->assertSee('NewsArticle', false);
    $response->assertSee('Test', false); // Title in schema
});

it('includes published vacancies in the sitemap', function () {
    $vacancy = Vacancy::factory()->create(['status' => ContentStatus::Published, 'published_at' => now()]);
    $vacancy->translations()->create(['locale' => 'tj', 'title' => 'Vacancy', 'slug' => 'vac-sitemap']);

    $response = $this->get('/sitemap.xml');

    $response->assertStatus(200);
    $response->assertSee('href="'.url('/tj/vacancies/vac-sitemap').'"', false);
});

it('includes published tenders in the sitemap', function () {
    $tender = Tender::factory()->create(['status' => ContentStatus::Published, 'published_at' => now()]);
    $tender->translations()->create(['locale' => 'tj', 'title' => 'Tender', 'slug' => 'tnd-sitemap']);

    $response = $this->get('/sitemap.xml');

    $response->assertStatus(200);
    $response->assertSee('href="'.url('/tj/tenders/tnd-sitemap').'"', false);
});

it('includes the leadership and structure pages in the sitemap', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertStatus(200);
    $response->assertSee('href="'.url('/tj/leadership').'"', false);
    $response->assertSee('href="'.url('/tj/structure').'"', false);
});

it('includes the gallery, faq pages and published galleries in the sitemap', function () {
    $gallery = Gallery::factory()->create(['status' => ContentStatus::Published]);
    $gallery->translations()->create(['locale' => 'tj', 'title' => 'Album', 'slug' => 'gal-sitemap']);

    $response = $this->get('/sitemap.xml');

    $response->assertStatus(200);
    $response->assertSee('href="'.url('/tj/gallery').'"', false);
    $response->assertSee('href="'.url('/tj/faq').'"', false);
    $response->assertSee('href="'.url('/tj/gallery/gal-sitemap').'"', false);
});

it('includes the statistics page in the sitemap', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertStatus(200);
    $response->assertSee('href="'.url('/tj/statistics').'"', false);
});
