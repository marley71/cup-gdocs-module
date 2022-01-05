<?php namespace App\Console\Commands\GDocuments;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Storage;


class GTest extends GCommon
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gdocs:test';

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
        $client = $this->getClientOAuth();
        $this->save($client,'1-s10nfzdyu5IGVjB5FAf_03ZLgb268_c5Ypo5WorH_s');
        return ;





        // documento google 00000000000004_CON_07-10-2019
        $fileId = '1q1KDn54As5J-qm_BjMkV9PDQbQNGtR8l6r6uaQfHTa8';
        $body = $this->getDocumentBody($client,$fileId);
        $data = [
            'proprietari' => [
                'Gabaldo Armando nato a Agugliaro (VI) il 28/04/1943 residente in Via Roma 26/2 40051 Malalbergo (BO) - Codice fiscale GBLRND43D28A093N',
                'Gabaldo Cinzia nata a Bologna (BO) il 24/10/1971 residente in Via Malaguti 2 40100 Bologna (BO) - Codice fiscale GBLCNZ71R64A944M',
                'Gabaldo Giuliano nato a Bologna (BO) il 09/07/1968 residente in Via Paolo Borsellino 21 40010 Sala Bolognese (BO) - Codice fiscale GBLGLN68L09A944V'
            ],
            'nome_ditta' => 'Ditta Mia',
            'indirizzo_ditta' => 'Via Confalonieri, 9',
            'citta_ditta' => 'Roma'
        ];
        $content = $this->trasform($body,$data);
        $fileRisultato = $this->export($client,$content);
        $this->save($client,$fileRisultato->id);
        //$this->docExample();
        //$this->exportExample();
        //$this->downloadExample();

        //$this->oauthExample($client);
        //$this->downloadExample($client);
        //$this->exportExample($client);
    }





    //----- funzioni di esempio ----

    public function docExample() {
        $client = new \Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->addScope(\Google_Service_Docs::DOCUMENTS_READONLY);
        $service = new \Google_Service_Docs($client);
        //$documentId = "1Tp7w9rsrcQVkN6KOvLHjc-xhmOWN79L1";
        $documentId = "1cygfHCNs5N5t9LcGXOJ4DHuBjbJkNP6aq5BUHuN8da0";
        $doc = $service->documents->get($documentId);
        print_r($doc->getBody());
    }

    public function exportExample($client=null) {
        if (!$client) {
            $client = new \Google_Client();
            $client->useApplicationDefaultCredentials();
            $client->addScope(\Google_Service_Drive::DRIVE);
        }
        $service = new \Google_Service_Drive($client);
        $serviceDoc = new \Google_Service_Docs($client);
        //$fileId = '1cygfHCNs5N5t9LcGXOJ4DHuBjbJkNP6aq5BUHuN8da0';
        $fileId = '1q1KDn54As5J-qm_BjMkV9PDQbQNGtR8l6r6uaQfHTa8';
        $content = $service->files->export($fileId,'text/html');
        $body = $content->getBody();
        $data = array_dot([
            'nome_ditta' => 'Ditta Mia',
            'indirizzo_ditta' => 'Via Confalonieri, 9',
            'citta_ditta' => 'Roma'
        ]);
        foreach ($data as $key => $value) {
            echo "key $key\n";
            $body = str_replace('{{' . $key . '}}',$value,$body);
        }

        //echo $body;
        $additionalParams = [
            'data' => $body,
            //'mimeType' => 'application/vnd.google-apps.document',
            //'mimeType' => 'application/vnd.google-apps.spreadsheet',
            //'mimeType' => 'application/pdf',
            //'mimeType' => 'application/docx',
            'mimeType' => 'text/html',
            'uploadType' => 'multipart',
        ];
        $fileMetadata = new \Google_Service_Drive_DriveFile(array(
            'title' => 'Documento'.date('Y-m-d'),
            'mimeType' => 'application/vnd.google-apps.document',
            'name' => 'Prova' . date('Y-m-d H:i:s')));
        echo $fileMetadata->getExportLinks();
        //$this->service->files->update($fileId, $file, $additionalParams);
        $fileRisultato = $service->files->create($fileMetadata,$additionalParams);
        echo $fileRisultato->id . "\n";
        echo $fileRisultato->downloadUrl . "\n";
        //$fileExport =  $service->files->export($fileId,'text/html')->executeMediaAndDownloadTo($bytes);

        //$fileExport->executeMediaAndDownloadTo($bytes);
        Storage::disk('local')->put('file.html', $fileRisultato->id);

            //application/vnd.openxmlformats-officedocument.wordprocessingml.document
//        $snappy = new Pdf(env('WKHTMLTOPDF_PATH'));
//        $filename = '/tmp/bill-123.pdf';
//        @unlink($filename);

        //$snappy->generateFromHtml($body, '/tmp/bill-123.pdf');
    }

    public function downloadExample($client=null) {
        if (!$client) {
            $client = new \Google_Client();
            $client->useApplicationDefaultCredentials();
            $client->addScope(\Google_Service_Drive::DRIVE);
        }

        $service = new \Google_Service_Drive($client);
        $serviceCopy = new \Google_Service_Drive_DriveFile($client);
        $fileId = '1cygfHCNs5N5t9LcGXOJ4DHuBjbJkNP6aq5BUHuN8da0';

        $fileCopiato = $service->files->copy($fileId,$serviceCopy);
        echo "file copiato id " . $fileCopiato->id . "\n";
//        $content = $service->files->export($fileId,'text/html');
//        //print_r($content);
//        $body = $content->getBody();
//        $data = array_dot([
//            'nome_ditta' => 'Ditta Mia',
//            'indirizzo_ditta' => 'Via Confalonieri, 9',
//            'citta_ditta' => 'Roma'
//        ]);
//        foreach ($data as $key => $value) {
//            $body = str_replace('{{' . $key . '}}',$value,$body);
//        }
//
//        $additionalParams = [
//            'data' => $body,
//            'mimeType' => 'application/pdf',
//            'uploadType' => 'multipart',
//        ];
//        $fileMetadata = new \Google_Service_Drive_DriveFile(array(
//            'title' => 'Documento'.date('Y-m-d')));
////        //$this->service->files->update($fileId, $file, $additionalParams);
//        $fileRisultato = $service->files->create($fileMetadata,$additionalParams);
//        echo $fileRisultato->id . "\n";
//        print_r($fileRisultato);
//        echo $fileRisultato->downloadUrl . "\n";
//        //$fileExport =  $service->files->export($fileId,'text/html')->executeMediaAndDownloadTo($bytes);
//
//        //$fileExport->executeMediaAndDownloadTo($bytes);
//        Storage::disk('local')->put('file.html', $fileRisultato->id);
//
//        //application/vnd.openxmlformats-officedocument.wordprocessingml.document
////        $snappy = new Pdf(env('WKHTMLTOPDF_PATH'));
////        $filename = '/tmp/bill-123.pdf';
////        @unlink($filename);
//
//        //$snappy->generateFromHtml($body, '/tmp/bill-123.pdf');
    }


    public function oauthExample($client) {

        $service = new \Google_Service_Drive($client);

// Print the names and IDs for up to 10 files.
        $optParams = array(
            'pageSize' => 10,
            'fields' => 'nextPageToken, files(id, name)'
        );
        $results = $service->files->listFiles($optParams);

        if (count($results->getFiles()) == 0) {
            print "No files found.\n";
        } else {
            print "Files:\n";
            foreach ($results->getFiles() as $file) {
                printf("%s (%s)\n", $file->getName(), $file->getId());
            }
        }
    }
}
