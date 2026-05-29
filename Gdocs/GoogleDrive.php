<?php namespace Modules\CupGdocs\Gdocs;

use Modules\CupGdocs\Contracts\IDrive;

class GoogleDrive implements IDrive
{
    // public function salva(string $file): void
    // {
    //     echo "Salvataggio '$file' su Microsoft Drive\n";
    // }

    // public function carica(string $file): void
    // {
    //     echo "Caricamento '$file' da Microsoft Drive\n";
    // }

    public function duplicate(string $file): void
    {
        echo "Duplicazione '$file' su Google Drive\n";
    }

    public function saveFromModel(string $name, string $template, array $data, ?string $folderId = null): object
    {
        echo "Salvataggio '$name' su Google Drive\n";
        /*
        //$this->loadData();
        $this->getClientOAuth();
        $this->body = $this->getDocumentBody($googleId);
        
        $content = $this->trasform();
        
        $service = new \Google_Service_Drive($this->client);
        $additionalParams = [
            'data' => $content,
            'mimeType' => 'text/html',
            'uploadType' => 'multipart',
        ];
        $params = [
            'title' => 'Contratto Ordine ' . $this->data['ordine.smart_id'] . '-Versione 1 ',
            'mimeType' => 'application/vnd.google-apps.document',
            'name' => 'Contratto Ordine ' . $this->data['ordine.smart_id'] . '-Versione 1 '
        ];
        if ($folderId) {
            $params['parents'] = [$folderId];//[ ['id' => $folderId] ];
        }
        print_r($params);
        $fileMetadata = new \Google_Service_Drive_DriveFile($params);
        return $service->files->create($fileMetadata, $additionalParams);
        */
    }
}