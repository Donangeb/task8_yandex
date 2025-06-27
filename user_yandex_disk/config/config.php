<?php

return [
    'base_url' => getenv('APP_URL') ?: 'http://localhost',
    'upload_dir' => __DIR__ . '/../uploads/',
    'yandex' => [
        'client_id' => getenv('YANDEX_CLIENT_ID'),
        'redirect_uri' => getenv('YANDEX_REDIRECT_URI'),
    ]
];
