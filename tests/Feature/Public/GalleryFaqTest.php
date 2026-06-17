<?php

use App\Models\Faq;
use App\Models\Gallery;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the public gallery list with published galleries', function () {
    $gallery = Gallery::factory()->create();
    $gallery->upsertTranslations(['tj' => ['title' => 'Чорабинӣ', 'slug' => 'chorabini']]);

    $this->get(route('gallery.index', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('public/gallery/index')->has('galleries', 1));
});

it('does not show draft galleries publicly', function () {
    $gallery = Gallery::factory()->draft()->create();
    $gallery->upsertTranslations(['tj' => ['title' => 'Черновик', 'slug' => 'draft-gal']]);

    $this->get(route('gallery.index', ['locale' => 'tj']))
        ->assertInertia(fn (Assert $page) => $page->has('galleries', 0));
});

it('shows a published gallery by its localized slug', function () {
    $gallery = Gallery::factory()->create();
    $gallery->upsertTranslations(['tj' => ['title' => 'Чорабинӣ', 'slug' => 'chorabini']]);

    $this->get(route('gallery.show', ['locale' => 'tj', 'slug' => 'chorabini']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('public/gallery/show')->where('gallery.title', 'Чорабинӣ'));
});

it('renders the public faq page with published questions', function () {
    $faq = Faq::factory()->create();
    $faq->upsertTranslations(['tj' => ['question' => 'Савол?', 'answer' => 'Ҷавоб']]);

    $this->get(route('faq.index', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('public/faq/index')->has('faqs', 1));
});

it('does not show draft faqs publicly', function () {
    $faq = Faq::factory()->draft()->create();
    $faq->upsertTranslations(['tj' => ['question' => 'Черновик?', 'answer' => 'X']]);

    $this->get(route('faq.index', ['locale' => 'tj']))
        ->assertInertia(fn (Assert $page) => $page->has('faqs', 0));
});
