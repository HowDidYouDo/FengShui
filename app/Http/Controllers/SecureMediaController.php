<?php

namespace App\Http\Controllers;

use App\Models\FloorPlan;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SecureMediaController extends Controller
{
    use AuthorizesRequests;

    public function show(FloorPlan $floorPlan, Media $media)
    {
        // 1. Security Check: Darf der User den FloorPlan sehen?
        // Wir nutzen die ProjectPolicy (via FloorPlan -> Project)
        $this->authorize('view', $floorPlan->project);

        // 2. Check: Gehört das Media-File auch wirklich zum FloorPlan?
        if ($media->model_id !== $floorPlan->id) {
            abort(404);
        }

        // 3. File Streamen
        return response()->file($media->getPath());
    }
}
