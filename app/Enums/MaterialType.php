<?php

namespace App\Enums;

enum MaterialType: string
{
    case Document = 'document';
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case YouTube = 'youtube';
    case Link = 'link';

    public function label(): string
    {
        return match ($this) {
            self::Document => __('Dokument'),
            self::Image => __('Obrázok'),
            self::Video => __('Video'),
            self::Audio => __('Zvuk'),
            self::YouTube => __('YouTube video'),
            self::Link => __('Odkaz'),
        };
    }

    public function isFile(): bool
    {
        return in_array($this, [self::Document, self::Image, self::Video, self::Audio], true);
    }

    public function icon(): string
    {
        return match ($this) {
            self::Document => 'document',
            self::Image => 'photo',
            self::Video, self::YouTube => 'play',
            self::Audio => 'play',
            self::Link => 'link',
        };
    }
}
