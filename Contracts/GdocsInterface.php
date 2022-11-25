<?php

namespace Modules\CupGdocs\Contracts;


interface GdocsInterface
{
    public function __construct($params=[]);
    public function loadData();
    public function export($googleId,$filepath);
    public function exportFromHtml($body, $filepath);
}
