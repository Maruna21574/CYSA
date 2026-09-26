<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One condition of a course certificate: a chapter to complete or a quiz to pass.
 */
#[Fillable(['chapter_id', 'quiz_id'])]
class CertificateRequirement extends Model
{
    protected $table = 'course_certificate_requirements';

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
