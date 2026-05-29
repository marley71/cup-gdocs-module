<?php

namespace Modules\CupGdocs\Contracts;


interface GdocsInterface
{
    public function __construct($params=[]);
    public function loadData();
    public function export(string $itemId, string $filepath) : void;
    public function exportFromHtml(string $body, string $filepath) : void;
}
