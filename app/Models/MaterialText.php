<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Plain text extracted from a study material file (cached input for AI).
 */
#[Fillable(['material_id', 'content', 'summary', 'keywords', 'extracted_at'])]
class MaterialText extends Model
{
    protected $primaryKey = 'material_id';

    public $incrementing = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['keywords' => 'array', 'extracted_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
