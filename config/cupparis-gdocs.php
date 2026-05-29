<?php

return [
    'drive_type' => 'microsoft',
    'drive_class' => [
        'google' => \Modules\CupGdocs\Gdocs\GoogleDrive::class,
        'microsoft' => \Modules\CupGdocs\Gdocs\MicrosoftDrive::class,
    ],
    'drive_folder_id' => [
        'google' => env('GOOGLE_FOLDER_ID',null),
        'microsoft' => env('MSDOCS_FOLDER_ID',null),
    ],
    'docs_config' => [
        // nome del tipo documento, classe che lo gestice e struttura dati
        'default_doc' => [
            'className' => \App\Gdocs\DefaultDoc::class,
            'dataKeys' => [
                // chiavi dati da valorizzare nel documento
            ]
        ],
    ],
    'secret_json_path' => env('GOOGLE_OAUTH_JSON','')
];
