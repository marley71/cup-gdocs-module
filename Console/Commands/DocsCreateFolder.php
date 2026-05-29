<?php namespace Modules\CupGdocs\Console\Commands;

use App\Gdocs\DefaultDoc;
use Illuminate\Support\Facades\Storage;
use Illuminate\Console\Command;
use Modules\CupGdocs\Gdocs\DefaultDrive;

class GTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cup:docs-create-folder {name : nome della cartella} {folderId? : crea una cartella nel drive se folderId è specificato, altrimenti crea una cartella nella root}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'crea una cartella nel drive se folderId è specificato, altrimenti crea una cartella nella root';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $folderId = $this->argument('folderId')?:null;
        $drive = DefaultDrive::drive();
        $drive->createFolder($this->argument('name'), $folderId);
    }
}
