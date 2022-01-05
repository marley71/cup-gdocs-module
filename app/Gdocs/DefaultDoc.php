<?php

namespace App\Console\Commands;


class DefaultDoc extends \Modules\CupGdocs\Gdocs\DefaultDoc
{

    protected $structure = [
        // tipi di dato speciali della struttura
        'esempio-inline-table' => [
            'type' => 'inline-table',
        ],
        'esempio-inline' => [
            'type' => 'inline',
            //'tagStart' => '<p ',
            //'tagEnd' => '</p>'
        ]
    ];

    function loadData()
    {
        // TODO: Implement loadData() method.
        $this->data['esempio-inline-table'] = [
            [
                'campo1' => 'campo1 a',
                'campo2' => 'campo2 a',
                'campo3' => '3452'
            ],
            [
                'campo1' => 'campo1 b',
                'campo2' => 'campo2 b',
                'campo3' => '22423'
            ],

            [
                'campo1' => 'campo1 c',
                'campo2' => 'campo2 c',
                'campo3' => '66666'
            ],
        ];

        $this->data['esempio-inline'] = [
            [
                'campo1' => 'campo1 a',
                'campo2' => 'campo2 a',
                'campo3' => '3452'
            ],
            [
                'campo1' => 'campo1 b',
                'campo2' => 'campo2 b',
                'campo3' => '22423'
            ],

            [
                'campo1' => 'campo1 c',
                'campo2' => 'campo2 c',
                'campo3' => '66666'
            ],
        ];
    }

    function export()
    {
        // TODO: Implement export() method.
    }
}
