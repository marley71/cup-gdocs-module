<?php

namespace Modules\CupGdocs\Contracts;


interface GdocsInterface
{
    function __construct($params=[]);
    function loadData();
    function export($googleId,$filepath);
}
