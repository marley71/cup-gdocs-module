<?php namespace Modules\CupGdocs\Gdocs;

use Modules\CupGdocs\Contracts\GdocsInterface;

abstract class DefaultDoc implements GdocsInterface {

    protected $params = [];
    protected $structure = [];
    protected $data = [];
    public function __construct($params = [])
    {
        $this->params = $params;
    }

}

