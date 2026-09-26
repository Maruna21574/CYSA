<?php

namespace App\Services\Import;

final readonly class StudentImportResult
{
    /**
     * @param  array<int, list<string>>  $errors  line number => messages
     */
    public function __construct(
        public int $created = 0,
        public array $errors = [],
    ) {}

    public function failed(): bool
    {
        return $this->errors !== [];
    }
}
