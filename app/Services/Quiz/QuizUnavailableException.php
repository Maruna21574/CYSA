<?php

namespace App\Services\Quiz;

use RuntimeException;

/**
 * The attempt cannot be started or changed (closed, expired, no attempts left...).
 * The message is shown to the student.
 */
class QuizUnavailableException extends RuntimeException {}
