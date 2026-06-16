<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Subdivision;
use Inertia\Inertia;
use Inertia\Response;

class StructureController extends Controller
{
    /**
     * Public organisational-structure page — a hierarchical tree of subdivisions with their tasks,
     * contacts and staff counts (ТЗ §20 «б», §44 hierarchical structure).
     */
    public function index(): Response
    {
        $locale = app()->getLocale();

        $all = Subdivision::published()
            ->with('translations')
            ->orderBy('sort_order')
            ->get();

        $build = function (?int $parentId) use (&$build, $all, $locale): array {
            return $all->where('parent_id', $parentId)
                ->map(fn (Subdivision $subdivision) => [
                    'id' => $subdivision->id,
                    'name' => $subdivision->translation($locale)?->name,
                    'head' => $subdivision->translation($locale)?->head,
                    'functions' => $subdivision->translation($locale)?->functions,
                    'address' => $subdivision->translation($locale)?->address,
                    'email' => $subdivision->email,
                    'phone' => $subdivision->phone,
                    'staff_count' => $subdivision->staff_count,
                    'children' => $build($subdivision->id),
                ])
                ->values()
                ->all();
        };

        return Inertia::render('public/structure/index', [
            'tree' => $build(null),
        ]);
    }
}
