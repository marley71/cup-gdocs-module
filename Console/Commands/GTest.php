<?php namespace Modules\CupGdocs\Console\Commands;

use App\Gdocs\DefaultDoc;
use Illuminate\Support\Facades\Storage;
use Illuminate\Console\Command;

class GTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gdocs:test {google_doc_id : esegue il dump del documento google}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'google documents test';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $doc = new DefaultDoc();
        //$id = '1eX8UxLViSjBJwmbDb1vak17zxINfq7jnVAdlavq8dV0';
        $id = $this->argument('google_doc_id');
        $doc->export($id,storage_path('prova.docx'));
    }
}
