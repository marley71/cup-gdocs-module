<?php

return [
    'docs_config' => [
        // nome del tipo struttura dati
        'name_struttura' => [
            'className' => \App\Gdocs\DefaultDoc::class,
        ]
    ],
    'secret_json_path' => env('GOOGLE_OAUTH_JSON','')
];
