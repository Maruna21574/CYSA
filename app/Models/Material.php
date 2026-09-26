<?php

namespace App\Models;

use App\Enums\MaterialType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Number;

#[Fillable(['type', 'title', 'disk', 'path', 'original_name', 'mime_type', 'size', 'url', 'position', 'uploaded_by'])]
class Material extends Model
{
    use SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MaterialType::class,
            'size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Chapter, $this>
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by')->withTrashed();
    }

    public function isFile(): bool
    {
        return $this->type->isFile();
    }

    /**
     * Files the browser can show safely by itself; everything else is downloaded.
     */
    public function isInline(): bool
    {
        return in_array($this->type, [MaterialType::Image, MaterialType::Video, MaterialType::Audio], true)
            || $this->mime_type === 'application/pdf';
    }

    public function youtubeId(): ?string
    {
        return $this->type === MaterialType::YouTube ? static::parseYoutubeId((string) $this->url) : null;
    }

    public function humanSize(): ?string
    {
        return $this->size ? Number::fileSize($this->size, precision: 1) : null;
    }

    /**
     * Extracts the 11-character video ID from the common YouTube URL formats.
     */
    public static function parseYoutubeId(string $url): ?string
    {
        $pattern = '~^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})(?:[?&#/].*)?$~';

        return preg_match($pattern, trim($url), $matches) ? $matches[1] : null;
    }
}
