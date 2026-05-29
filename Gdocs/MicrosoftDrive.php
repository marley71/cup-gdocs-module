<?php namespace Modules\CupGdocs\Gdocs;

use App\Msdocs\MSDrive;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Modules\CupGdocs\Contracts\IDrive;
use PhpOffice\PhpWord\TemplateProcessor;

class MicrosoftDrive implements IDrive
{
    public function createFolder(string $name, ?string $folderId = null): string
    {
        return MSGraph::createFolder($name, $folderId);
    }

    public function getFolders(string $path): array
    {
        return MSGraph::getFolders($path);
    }

    public function getFolderId(string $path): string
    {
        return MSGraph::getFolderId($path);
        /*
        $folders = $this->getFolders($path);
        Log::info("getFolderId: $path");
        Log::info(print_r($folders,true));
        return $folders[0]['id'];*/
    }

    public function deleteItem(string $itemId): void
    {
        MSGraph::deleteItem($itemId);
    }
    public function getUrl(string $itemId): string
    {
        return MSGraph::getUrl($itemId);
    }

    public function salva(string $file): void
    {
        echo "Salvataggio '$file' su Microsoft Drive\n";
    }

    public function carica(string $file): void
    {
        echo "Caricamento '$file' da Microsoft Drive\n";
    }

    public function duplicate(string $file): void
    {
        echo "Duplicazione '$file' su Microsoft Drive\n";
    }

    public function saveFromModel(string $name, string $template, array $data, ?string $folderId = null): string|null
    {
        Log::info("Salvataggio '$name' su Microsoft Drive");
        // Log::info("Template: $template");
        // Log::info(print_r($data,true));
        Log::info("FolderId: $folderId");
        $tempPath = storage_path('app/template.docx');

        file_put_contents($tempPath, $template);
        $template = new TemplateProcessor($tempPath);

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $template->setValue($key, $value);
            } else {
                $template->setValue($key, json_encode($value));
            }
            
        }
        $filenameId = rand(1,1000000);
        $outputPath = storage_path('app/output'.$filenameId.'.docx');
        $template->saveAs($outputPath);
        $content = file_get_contents($outputPath);
        try {
            $result = (array)MSDrive::uploadDocxDocument($name, $content, $folderId);
            Log::info("saveFromModel: result: " . print_r($result,true));
            unlink($outputPath);
            return Arr::get( $result, 'id',null);
        } catch (\Exception $e) {
            Log::error("saveFromModel: error: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
        return null;
    }

    public function getDocumentBody(string $itemId): string
    {
        $contentPath = MSGraph::driveBasePath() . "/items/{$itemId}/content";
        $response = MSGraph::request('GET', $contentPath);

        if (!$response->successful()) {
            throw new \RuntimeException('Errore download documento: ' . $response->body());
        }

        return $response->body();
    }

    public function getPdf(string $itemId): string
    {
        return MSGraph::getPdf($itemId);
    }

}