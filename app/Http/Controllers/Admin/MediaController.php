<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $mediaFiles = MediaFile::with('media')
            ->latest()
            ->paginate(24);

        return Inertia::render('admin/media/index', [
            'mediaFiles' => $mediaFiles,
        ]);
    }

    /**
     * API endpoint to get media for the modal.
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $mediaFiles = MediaFile::with('media')
            ->latest()
            ->paginate(24);

        return response()->json($mediaFiles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB max
        ]);

        $mediaFile = MediaFile::create([
            'user_id' => $request->user()->id,
            'name' => $request->file('file')->getClientOriginalName(),
        ]);

        $mediaFile->addMediaFromRequest('file')->toMediaCollection('default');

        if ($request->wantsJson()) {
            return response()->json($mediaFile->load('media'));
        }

        return redirect()->back()->with('success', 'Файл успешно загружен');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MediaFile $mediaFile)
    {
        $mediaFile->delete(); // This will trigger Spatie media library to delete files if configured or cascading.

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Файл удален');
    }
}
