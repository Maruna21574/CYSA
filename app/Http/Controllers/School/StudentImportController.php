<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\StudentImportRequest;
use App\Models\Classroom;
use App\Models\User;
use App\Services\Import\CsvImportException;
use App\Services\Import\StudentImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentImportController extends Controller
{
    public function create(Request $request): View
    {
        Gate::authorize('create', User::class);

        $classrooms = Classroom::visibleTo($request->user())
            ->orderByDesc('school_year')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Classroom $classroom): array => [$classroom->id => $classroom->name.' ('.$classroom->school_year.')'])
            ->all();

        return view('school.students.import', ['classrooms' => $classrooms]);
    }

    public function store(StudentImportRequest $request, StudentImporter $importer): RedirectResponse
    {
        $classroom = $request->filled('classroom_id')
            ? Classroom::visibleTo($request->user())->findOrFail($request->integer('classroom_id'))
            : null;

        try {
            $result = $importer->import(
                (string) file_get_contents($request->file('file')->getRealPath()),
                $request->user()->school,
                $classroom,
                $request->boolean('send_invitation'),
            );
        } catch (CsvImportException $exception) {
            return back()->withInput()->withErrors(['file' => $exception->getMessage()]);
        }

        if ($result->failed()) {
            return back()->withInput()
                ->with('import_errors', $result->errors)
                ->with('error', __('Import sa nevykonal – opravte chyby v súbore a nahrajte ho znova.'));
        }

        return redirect()->route('users.index', ['role' => 'student'])
            ->with('success', trans_choice('{1} Bol importovaný :count študent.|[2,4] Boli importovaní :count študenti.|[5,*] Bolo importovaných :count študentov.', $result->created, ['count' => $result->created]));
    }
}
