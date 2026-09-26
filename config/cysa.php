<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Study materials
    |--------------------------------------------------------------------------
    | "disk" must be a disk from config/filesystems.php. The upload limit is also bounded
    | by PHP's upload_max_filesize and post_max_size on the server.
    */
    'materials' => [
        'disk' => env('MATERIALS_DISK', 'materials'),
        'max_upload_kb' => (int) env('MATERIALS_MAX_UPLOAD_KB', 51200),
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
