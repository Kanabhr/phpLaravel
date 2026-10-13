<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Media::query();

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('original_filename', 'like', "%{$search}%")
                      ->orWhere('keywords', 'like', "%{$search}%");
                });
            }

            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            if ($request->filled('type') && $request->type === 'image') {
                $query->whereIn('mime_type', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
            }

            match ($request->input('sort', 'newest')) {
                'oldest' => $query->oldest(),
                'name'   => $query->orderBy('original_filename', 'asc'),
                default  => $query->latest(),
            };

            $media = $query->paginate(20);
            return response()->json([
                'data'      => $media->items(),
                'last_page' => $media->lastPage(),
                'total'     => $media->total(),
            ]);
        }
        return view('admin.media.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB
        ]);

        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = uniqid('media_') . '.' . $extension;

        // Ensure directories exist
        if (!Storage::disk('public')->exists('media')) {
            Storage::disk('public')->makeDirectory('media');
        }
        
        $path = 'media/' . $filename;
        $absolutePath = Storage::disk('public')->path($path);

        // Process image synchronously using Intervention Image (v4)
        $manager = new ImageManager(new Driver());
        $image = $manager->decode($file->getRealPath());
        
        // Auto-orient and constrain width to 1920px max while maintaining aspect ratio
        $image->scaleDown(width: 1920);
        $image->save($absolutePath);
        
        $size = Storage::disk('public')->size($path);

        $media = Media::create([
            'user_id' => auth()->id(),
            'disk' => 'public',
            'path' => $path,
            'filename' => $filename,
            'original_filename' => $originalFilename,
            'mime_type' => $file->getMimeType(),
            'extension' => $extension,
            'size' => $size,
            'width' => $image->width(),
            'height' => $image->height(),
            'alt_text' => str_replace(['-', '_'], ' ', pathinfo($originalFilename, PATHINFO_FILENAME)),
        ]);

        return response()->json([
            'success' => true,
            'media' => $media
        ]);
    }

    public function destroy(Media $media)
    {
        // We use soft deletes, so the file remains on disk until forced
        $media->delete();
        return response()->json(['success' => true]);
    }

    public function update(Request $request, Media $media)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'caption' => 'nullable|string',
            'keywords' => 'nullable|string',
        ]);

        $media->update([
            'title' => $request->title,
            'alt_text' => $request->alt_text,
            'caption' => $request->caption,
            'keywords' => $request->keywords,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Media metadata updated successfully',
            'media' => $media
        ]);
    }
}
