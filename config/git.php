<?php

use mathewparet\LaravelGitVersion\Git\GitVersion;

return [
    /**
     * Return the git version
     */
    'version' => GitVersion::version(),
    'date' => GitVersion::date(),
    'branch' => GitVersion::branch(),
];