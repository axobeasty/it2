<?php

return [
    'debug' => false,
    'api_url' => 'https://platform-api.max.ru',
    'token' => env('MAX_MESSENGER_BOT_TOKEN'),
    'long-polling'  => [
        'limit' => 100,
        'timeout' => 30,
    ]
];