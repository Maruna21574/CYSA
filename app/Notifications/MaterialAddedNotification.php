<?php

namespace App\Notifications;

use App\Models\Material;

class MaterialAddedNotification extends CysaNotification
{
    public function __construct(public Material $material) {}

    public function title(): string
    {
        return __('Nový študijný materiál');
    }

    public function body(): string
    {
        return __('V kapitole „:chapter“ pribudol materiál „:title“.', [
            'chapter' => $this->material->chapter->title,
            'title' => $this->material->title,
        ]);
    }

    public function url(): string
    {
        return route('chapters.show', [$this->material->chapter->course_id, $this->material->chapter_id]);
    }

    public function icon(): string
    {
        return 'document';
    }
}
