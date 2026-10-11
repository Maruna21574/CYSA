<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Material;
use App\Models\MaterialProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MaterialProgressController extends Controller
{
    public function store(Request $request, Material $material): JsonResponse
    {
        Gate::authorize('view', $material);
        abort_unless($request->user()->role === UserRole::Student && $material->isPdf(), 403);

        $page = (int) $request->validate([
            'page' => ['required', 'integer', 'min:1', 'max:'.$material->pageCount()],
        ])['page'];

        $progress = MaterialProgress::firstOrNew([
            'user_id' => $request->user()->id,
            'material_id' => $material->id,
        ]);
        abort_if($page > max(1, (int) $progress->current_page + 1), 422, __('Prejdi strany postupne od začiatku.'));
        $progress->page_count = $material->pageCount();
        $progress->current_page = max((int) $progress->current_page, $page);
        $progress->completed_at = $progress->current_page >= $progress->page_count
            ? ($progress->completed_at ?? now())
            : null;
        $progress->save();

        return response()->json([
            'current_page' => $progress->current_page,
            'page_count' => $progress->page_count,
            'completed' => $progress->completed_at !== null,
        ]);
    }
}
