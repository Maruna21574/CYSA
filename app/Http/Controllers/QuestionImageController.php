<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Services\Files\MaterialStorage;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class QuestionImageController extends Controller
{
    public function __invoke(Question $question, MaterialStorage $storage): Response
    {
        Gate::authorize('view', $question);
        abort_if($question->image_path === null, 404);

        return $storage->imageResponse($question->image_path);
    }
}
