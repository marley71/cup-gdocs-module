<?php namespace Modules\CupGdocs\Console\Commands;

use App\Gdocs\DefaultDoc;
use Illuminate\Support\Facades\Storage;
use Illuminate\Console\Command;
use Modules\CupGdocs\Gdocs\DefaultDrive;

class DocsGetFolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cup:docs-get-folders {path? : path della cartella}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'restituisce la lista delle cartelle nel drive';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
       
        $folderId = config('cupparis-gdocs.drive_folder_id')[config('cupparis-gdocs.drive_type')];
        $drive = DefaultDrive::drive();
        $drive->getFolders($folderId);
    }
}
