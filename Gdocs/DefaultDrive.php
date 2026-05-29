<?php namespace Modules\CupGdocs\Gdocs;


use Modules\CupGdocs\Contracts\IDrive;

class DefaultDrive {

    public static function drive(): IDrive
    {
        $drive = config('cupparis-gdocs.drive_class')[config('cupparis-gdocs.drive_type')];
        return new $drive();
    }
}