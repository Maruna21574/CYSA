<?php

namespace App\Http\Controllers;

use App\Services\Search\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request, SearchService $search): View
    {
        $term = Str::limit(trim((string) $request->query('q')), 100, '');

        return view('search', [
            'term' => $term,
            'sections' => $search->search($request->user(), $term),
        ]);
    }
}
