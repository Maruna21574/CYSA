<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Course;
use App\Services\Notifications\CourseNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Course $course): View
    {
        Gate::authorize('update', $course);

        return view('teacher.courses.announcements', [
            'course' => $course,
            'announcements' => $course->announcements()->with('author:id,first_name,last_name')->latest()->get(),
        ]);
    }

    public function store(Request $request, Course $course, CourseNotifier $notifier): RedirectResponse
    {
        Gate::authorize('update', $course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $announcement = new Announcement($data);
        $announcement->course_id = $course->id;
        $announcement->author_id = $request->user()->id;
        $announcement->save();

        $notifier->announcement($announcement);

        return back()->with('success', $course->isPublished()
            ? __('Oznámenie bolo odoslané študentom kurzu.')
            : __('Oznámenie bolo uložené. Študenti ho uvidia po publikovaní kurzu.'));
    }

    public function destroy(Course $course, Announcement $announcement): RedirectResponse
    {
        Gate::authorize('update', $course);

        $announcement->delete();

        return back()->with('success', __('Oznámenie bolo odstránené.'));
    }
}
