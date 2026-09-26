<?php

namespace App\Services\Import;

use App\Enums\AuditAction;
use App\Enums\ClassroomRole;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\School;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Users\UserManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Creates student accounts from a CSV file. The import is all-or-nothing:
 * if any row is invalid, nothing is created and all problems are reported at once.
 */
class StudentImporter
{
    public function __construct(
        private StudentCsvParser $parser,
        private UserManager $users,
        private AuditLogger $audit,
    ) {}

    /**
     * @throws CsvImportException
     */
    public function import(string $contents, School $school, ?Classroom $classroom = null, bool $sendInvitations = true): StudentImportResult
    {
        $rows = $this->parser->parse($contents);

        $errors = $this->validate($rows);

        if ($errors !== []) {
            return new StudentImportResult(errors: $errors);
        }

        $created = DB::transaction(function () use ($rows, $school, $classroom): array {
            $created = [];

            foreach ($rows as $row) {
                $user = $this->users->create([
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'email' => $row['email'],
                    'role' => UserRole::Student,
                    'school_id' => $school->id,
                ], sendInvitation: false);

                $classroom?->members()->attach($user->id, ['role' => ClassroomRole::Student->value]);
                $created[] = $user;
            }

            $this->audit->log(AuditAction::UsersImported, $classroom ?? $school, newValues: [
                'count' => count($created),
                'classroom_id' => $classroom?->id,
            ]);

            return $created;
        });

        // Only after the commit, so no invitation is sent for a rolled-back import.
        if ($sendInvitations) {
            foreach ($created as $user) {
                $this->users->invite($user);
            }
        }

        return new StudentImportResult(created: count($created));
    }

    /**
     * @param  list<array{line: int, first_name: string, last_name: string, email: string}>  $rows
     * @return array<int, list<string>>
     */
    private function validate(array $rows): array
    {
        $errors = [];
        $seen = [];

        $existing = User::withTrashed()
            ->whereIn('email', array_column($rows, 'email'))
            ->pluck('email')
            ->flip();

        foreach ($rows as $row) {
            $validator = Validator::make($row, [
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'string', 'email', 'max:255'],
            ]);

            $messages = $validator->errors()->all();

            if ($row['email'] !== '' && isset($existing[$row['email']])) {
                $messages[] = __('Účet s e-mailom :email už existuje.', ['email' => $row['email']]);
            }

            if ($row['email'] !== '' && isset($seen[$row['email']])) {
                $messages[] = __('E-mail :email je v súbore viackrát (riadok :line).', ['email' => $row['email'], 'line' => $seen[$row['email']]]);
            }

            $seen[$row['email']] ??= $row['line'];

            if ($messages !== []) {
                $errors[$row['line']] = $messages;
            }
        }

        return $errors;
    }
}
