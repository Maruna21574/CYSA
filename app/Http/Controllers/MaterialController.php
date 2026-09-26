<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use App\Services\Files\MaterialStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * The only way to reach private files: every request is authorized against the chapter / course.
 */
class MaterialController extends Controller
{
    public function __construct(private MaterialStorage $storage) {}

    public function show(Request $request, Material $material): Response
    {
        Gate::authorize('view', $material);
        abort_unless($material->isFile(), 404);

        return $this->storage->materialResponse($material, $request->boolean('download'));
    }

    public function cover(Course $course): Response
    {
        Gate::authorize('view', $course);
        abort_if($course->cover_path === null, 404);

        return $this->storage->coverResponse($course);
    }
}
