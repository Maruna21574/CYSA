<?php

namespace App\Services\AI;

use RuntimeException;

/**
 * AI request failed. The message is written for the teacher (Slovak, no technical details
 * or secrets); the original exception is kept as the previous one for the log.
 */
class AiException extends RuntimeException {}
