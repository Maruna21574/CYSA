<?php

namespace App\Enums;

enum CourseStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Koncept'),
            self::Published => __('Publikovaný'),
            self::Archived => __('Archivovaný'),
        };
    }

    /**
     * Badge color used in the UI.
     */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'amber',
            self::Published => 'green',
            self::Archived => 'slate',
        };
    }
}
