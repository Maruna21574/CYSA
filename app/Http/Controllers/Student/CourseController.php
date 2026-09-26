<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $courses = Course::query()
            ->availableTo($request->user())
            ->with(['category:id,name', 'author:id,first_name,last_name'])
            ->withCount(['chapters' => fn ($query) => $query->where('is_published', true)])
            ->orderBy('title')
            ->get();

        return view('student.courses.index', ['courses' => $courses]);
    }
}
