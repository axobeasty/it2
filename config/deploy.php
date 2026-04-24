<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Проверка обновлений без .git на сервере
    |--------------------------------------------------------------------------
    |
    | Сравнивается ветка на GitHub с локальной отметкой версии (deploy.json или
    | DEPLOY_GIT_REF). Для приватного репозитория укажите DEPLOY_GITHUB_TOKEN.
    |
    */

    'github_repo' => env('DEPLOY_GITHUB_REPO', 'axobeasty/it2'),

    'github_branch' => env('DEPLOY_GITHUB_BRANCH', 'master'),

    'github_token' => env('DEPLOY_GITHUB_TOKEN', env('GITHUB_TOKEN')),

];
