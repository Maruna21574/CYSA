<?php

namespace App\Services\Import;

use RuntimeException;

/**
 * The uploaded file cannot be processed at all (wrong header, too many rows, ...).
 * The message is shown to the user.
 */
class CsvImportException extends RuntimeException {}
