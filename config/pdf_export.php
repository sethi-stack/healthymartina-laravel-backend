<?php

return [
    'enabled' => (bool) env('PDF_EXPORT_ASYNC_ENABLED', false),
    'base_url' => env('PDF_EXPORT_SERVICE_URL', 'http://localhost:4300'),
    'shared_secret' => env('PDF_EXPORT_SHARED_SECRET', ''),
    'http_timeout_seconds' => (int) env('PDF_EXPORT_HTTP_TIMEOUT_SECONDS', 20),
];
