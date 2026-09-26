<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Study materials
    |--------------------------------------------------------------------------
    | "disk" must be a disk from config/filesystems.php. The upload limit is also bounded
    | by PHP's upload_max_filesize and post_max_size on the server.
    */
    // Where messages from the public contact form are delivered.
    'contact_email' => env('CONTACT_EMAIL', env('MAIL_FROM_ADDRESS')),

    'materials' => [
        'disk' => env('MATERIALS_DISK', 'materials'),
        'max_upload_kb' => (int) env('MATERIALS_MAX_UPLOAD_KB', 51200),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI question generation
    |--------------------------------------------------------------------------
    | Suggestions are always drafts - a teacher must review and approve them.
    | The feature is hidden when no API key is configured. Only the text of study
    | materials is sent to the provider, never personal data of students.
    */
    'ai' => [
        'provider' => env('AI_PROVIDER', 'anthropic'),
        'model' => env('AI_MODEL', 'claude-opus-5'),
        // low | medium | high | xhigh | max - thoroughness vs. cost of every request
        'effort' => env('AI_EFFORT', 'high'),
        'timeout' => (int) env('AI_TIMEOUT', 300),
        'max_input_chars' => (int) env('AI_MAX_INPUT_CHARS', 120000),
        'max_questions' => 15,
        'daily_limit_per_teacher' => (int) env('AI_DAILY_LIMIT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    | On by default in production. Keep it off while running the Vite dev server
    | (npm run dev), which serves scripts from another origin.
    */
    'csp' => (bool) env('CSP_ENABLED', env('APP_ENV') === 'production'),

];
