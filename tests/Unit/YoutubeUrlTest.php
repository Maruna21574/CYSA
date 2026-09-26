<?php

namespace Tests\Unit;

use App\Models\Material;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class YoutubeUrlTest extends TestCase
{
    /**
     * @return array<string, array{string, string|null}>
     */
    public static function urlProvider(): array
    {
        return [
            'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'watch with params' => ['https://youtube.com/watch?feature=share&v=dQw4w9WgXcQ&t=42', 'dQw4w9WgXcQ'],
            'short link' => ['https://youtu.be/dQw4w9WgXcQ?si=abc', 'dQw4w9WgXcQ'],
            'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'channel' => ['https://www.youtube.com/@channel', null],
            'lookalike domain' => ['https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ', null],
            'other site' => ['https://vimeo.com/123456', null],
        ];
    }

    #[DataProvider('urlProvider')]
    public function test_video_id_is_parsed(string $url, ?string $expected): void
    {
        $this->assertSame($expected, Material::parseYoutubeId($url));
    }
}
