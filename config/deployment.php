<?php

return [
    'application_path' => env('DEPLOYMENT_APPLICATION_PATH', base_path()),
    'git_binary' => env('APPLICATION_UPDATE_GIT_BINARY'),
    'git_binary_candidates' => array_values(array_filter([
        env('APPLICATION_UPDATE_GIT_BINARY'),
        'git',
        '/usr/bin/git',
        '/usr/local/bin/git',
    ])),
];
