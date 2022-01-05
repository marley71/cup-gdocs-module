<?php namespace Modules\CupGdocs\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GCommon extends Command
{

    protected $client = null;
    protected $blockKeys = [];
    protected $body = '';

    protected function save($fileId, $filePath)
    {
        $service = new \Google_Service_Drive($this->client);
        $content = $service->files->export($fileId, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        Storage::disk('storage')->put($filePath, $content->getBody());
    }

    protected function export($body)
    {
        $service = new \Google_Service_Drive($this->client);
        $additionalParams = [
            'data' => $body,
            'mimeType' => 'text/html',
            'uploadType' => 'multipart',
        ];
        $fileMetadata = new \Google_Service_Drive_DriveFile(array(
            'title' => 'Documento' . date('Y-m-d'),
            'mimeType' => 'application/vnd.google-apps.document',
            'name' => 'Prova' . date('Y-m-d H:i:s')));
        return $service->files->create($fileMetadata, $additionalParams);
    }

    protected function trasform($data)
    {
        \Log::info('found Blocks....');
        while ($this->foundBlock($data)) {}
        $data_dot = array_dot($data);
        file_put_contents(storage_temp_path('blocchi_srotolati.html'), $this->body . "\n");
        \Log::info('replaceKeys ....');
        return $this->replaceKeys($data_dot);
    }


    protected function foundBlock($data)
    {
        $body = $this->body;
        if (preg_match('/{{{[^\/].+?}}}/', $body, $matches)) {
            $key = $matches[0];
            $realKey = str_replace('}}}', '', str_replace('{{{', '', $key));
            $endKey = str_replace('{{{', '{{{/', $key);
            $posStart = strpos($body, $key) + strlen($key);
            $posEnd = strpos($body, $endKey);

            if ($posEnd === FALSE) {
                \Log::info("chiave $realKey non bilanciata.");
                throw new \Exception("chiave $realKey non bilanciata.");
            }

            $subBody = substr($body, $posStart, $posEnd - $posStart);

            $isTable = strpos($subBody, '<table') === FALSE ? false : true;
            $isList = strpos($subBody, '<ol') === FALSE ? false : true;
            $isList |= strpos($subBody, '<ul') === FALSE ? false : true;
            // e' una tabella
            if ($isTable) {
                $modifiedBody = $this->adjustRowTable($subBody);
                // controllo prima che non sia una tabella di un sottoblocco annidatato
                if ($modifiedBody === null) { // NON e' una tabella annidata
                    $simulatedBody = $key . $subBody . $endKey;
                    $modifiedBody = $this->adjustRowTable($simulatedBody);
                    if ($modifiedBody === null) {
                        throw new \Exception('Errore generale');
                    }
//                  ---vecchio codice
//                    $body = str_replace($key,'',$body);
//                    $body = str_replace($endKey,'',$body);


//                    $modifiedBody = str_replace($key,'',$modifiedBody);
//                    $modifiedBody = str_replace($endKey,'',$modifiedBody);

                    $parte1 = substr($body,0,$posEnd+strlen($endKey));
                    $parte2 = substr($body,$posEnd+strlen($endKey));

                    $parte1 = str_replace($key,'',$parte1);
                    $parte1 = str_replace($endKey,'',$parte1);

                    $body = $parte1 . $parte2;

//                    $body = substr_replace($key,'',$body);
//                    $body = substr_replace($endKey,'',$body);




                    $body = str_replace($subBody,$modifiedBody,$body);
                    // mi riposiziono sulle nuove keys {{{  spostati dalla table al tr
                    $posInternalStart = strpos($modifiedBody, $key) + strlen($key);
                    $posInternalEnd = strpos($modifiedBody, $endKey);
                    $subBody = substr($modifiedBody, $posInternalStart, $posInternalEnd - $posInternalStart);


                } else {
                    $subBody = $modifiedBody;
                }
            }

            $subBodies = $this->getSubBodies($data,$realKey,$subBody);
            $part1 = substr($body, 0, strpos($body, $key));
            $part2 = implode("", $subBodies);
            // ricalcolo il posEnd in caso di tabelle non nidificato il body potrebbe essere cambiato
            $posEnd = strpos($body, $endKey);
            $part3 = substr($body, $posEnd + strlen($endKey), strlen($body) - ($posEnd + strlen($endKey)));
            $body = $part1 . $part2 . $part3;
            $this->body = $body;

            return true;
        }
        return false;
    }


    protected function getSubBodies ($data,$realKey,$subBody) {
        $subBodies = [];
        $values = $this->_get($data, $realKey);
        $size = count(array_keys($values));
        $i = 0;
        foreach ($this->_get($data, $realKey) as $k => $v) {
            $tmp = $subBody;
            $tmp = str_replace($realKey, $realKey . '.' . $k, $tmp);

            // ricerco caratteri speciali da ripetere
            preg_match_all('/{{\$(.+?)\$}}/', $tmp, $matches);
            if (count($matches) > 0) {
                //print_r($matches);
                for ($j=0;$j<count($matches[0]);$j++) {
                    if ($i == $size-1)  // se e' l'ultimo
                        $tmp = preg_replace('/{{\$(.+?)\$}}/','',$tmp);
                    else
                        $tmp = str_replace($matches[0][$j],$matches[1][$j],$tmp);
                }

            }

            $i++;
            $subBodies[] = $tmp;
        }
        return $subBodies;
    }

    protected function replaceKeys($data)
    {
        $stop = false;
        $offset = 0;
        $body = $this->body;
        while (!$stop) {
            //echo "offset $offset\n";
            if (preg_match('/{{.+?}}/', $body, $matches, 0, $offset)) {
                $key = $matches[0];
                $offset = strpos($body, $key);
                $realKey = str_replace('}}', '', str_replace('{{', '', $key));
                \Log::info("key $key realKey $realKey");
                $body = str_replace($key, $data[$realKey], $body);
            } else {
                $stop = true;
            }
        }
        return $body;
    }

    protected function adjustRowTable($body)
    {
        // ci sono due casi o siamo in un blocco con tabella di primo livello o un blocco con tabella all'interno di un'altro blocco
        $stop = false;
        $offset = 0;
        while (!$stop) {
            $tableStart = strpos($body, '<table', $offset);
            if ($tableStart === FALSE) {
                $stop = true;
                continue;
            }

            //  prendo il sottobody per evitare di trovare altri blocchi.
            $subBody = substr($body, $offset, $tableStart - $offset);
            $blockStart = strrpos($subBody, '{{{');

            /// caso in cui il sottoblocco {{{ non esiste
            if ($blockStart === FALSE) {
                return null;
            }


            $blockEnd = strpos($subBody, '}}}', $blockStart) + 3;

            $blockName = substr($body, $blockStart, $blockEnd - $blockStart);
            $blockNameEnd = '{{{/' . substr($blockName, 3);


            $blockStart = strpos($body, $blockName, $offset);

            $tableBody = substr($body,$tableStart,strpos($body,'</table>',$offset)+8-$tableStart);


            $trStart = strrpos($tableBody, '<tr') + $tableStart;

            $part1 = substr($body, $offset, $trStart);
            $part1 = str_replace($blockName, '', $part1);
            $part2 = $blockName;
            $part3 = substr($body, $trStart);
            $body = $part1 . $part2 . $part3;

            $trEnd = strpos($body, '</tr>', $trStart);

            $part1 = substr($body, 0, $trEnd + 5);
            $part2 = $blockNameEnd;
            $part3 = substr($body, $trEnd + 5);
            $part3 = str_replace($blockNameEnd, '', $part3);
            $body = $part1 . $part2 . $part3;
            $offset = $trEnd + 5;

        }

        return $body;
    }

    protected function adjustRowTableOld($body)
    {
        // ci sono due casi o siamo in un blocco con tabella di primo livello o un blocco con tabella all'interno di un'altro blocco
        $stop = false;
        $offset = 0;
        while (!$stop) {
            $tableStart = strpos($body, '<table', $offset);
            if ($tableStart === FALSE) {
                $stop = true;
                continue;
            }
            //  prendo il sottobody per evitare di trovare altri blocchi.
            $subBody = substr($body, $offset, $tableStart - $offset);
            $blockStart = strrpos($subBody, '{{{');
            $blockEnd = strpos($subBody, '}}}', $blockStart) + 3;

            $blockName = substr($body, $blockStart, $blockEnd - $blockStart);
            $blockNameEnd = '{{{/' . substr($blockName, 3);


            $blockStart = strpos($body, $blockName, $offset);

            $tableBody = substr($body,$tableStart,strpos($body,'</table>',$offset)+8-$tableStart);


            $trStart = strrpos($tableBody, '<tr') + $tableStart;

            $part1 = substr($body, $offset, $trStart);
            $part1 = str_replace($blockName, '', $part1);
            $part2 = $blockName;
            $part3 = substr($body, $trStart);
            $body = $part1 . $part2 . $part3;

            $trEnd = strpos($body, '</tr>', $trStart);

            $part1 = substr($body, 0, $trEnd + 5);
            $part2 = $blockNameEnd;
            $part3 = substr($body, $trEnd + 5);
            $part3 = str_replace($blockNameEnd, '', $part3);
            $body = $part1 . $part2 . $part3;
            $offset = $trEnd + 5;

        }

        return $body;
    }





    protected function trasformInlineTable($tableBody, $data, $key)
    {
        //trovo la prima occorrenza e l'ultima di key
        $posStart = strpos($tableBody, '{{' . $key);
        $posEnd = strrpos($tableBody, '{{' . $key);
        //echo "start $posStart end $posEnd\n";
        if ($posStart === FALSE || $posEnd === FALSE) {
            echo "start: $posStart end : $posEnd marcatori non trovati\n";
            return $tableBody;
        }
        //trovo il tag tr e </tr>
        $posStartRow = strrpos(substr($tableBody, 0, $posStart), '<tr ');
        $posEndRow = strpos($tableBody, '</tr>', $posEnd);
        //echo "start row $posStartRow end row $posEndRow\n";
        if ($posStartRow === FALSE || $posEndRow === FALSE) {
            echo "start: $posStartRow end : $posEndRow marcatori di riga non trovati\n";
            return $tableBody;
        }
        // prendo il blocco da ripetere e sostituisco le occorrenze
        $rigaDaRipetere = substr($tableBody, $posStartRow, $posEndRow - $posStartRow + 5);
        //echo "daRiptere\n $rigaDaRipetere\n";
        //die();
        $subBody = "";
        foreach ($data[$key] as $values) {
            $tmpRiga = $rigaDaRipetere;
            foreach ($values as $subKey => $value) {
                $searchKey = '{{' . $key . '.' . $subKey . '}}';
                //echo "searchKey $searchKey  value $value\n";
                $tmpRiga = str_replace($searchKey, $value, $tmpRiga);
            }
            $subBody .= $tmpRiga;
            //print_r($values);
        }
        // elimino la parte template e la sostituisco con la stringa valorizzata
        $part1 = substr($tableBody, 0, $posStartRow);
        $part2 = substr($tableBody, $posEndRow + 5);
        $tableBody = $part1 . $subBody . $part2;
        //echo $subBody . "\n";
        //echo $body . "\n";
        return $tableBody;
    }

    protected function trasformInlineList($listBody, $data, $key)
    {
        //trovo la prima occorrenza e l'ultima di key
        $posStart = strpos($listBody, '{{' . $key);
        $posEnd = strrpos($listBody, '{{' . $key);
        //echo "start $posStart end $posEnd\n";
        if ($posStart === FALSE || $posEnd === FALSE) {
            echo "start: $posStart end : $posEnd marcatori non trovati\n";
            return $listBody;
        }
        //trovo il tag tr e </tr>
        $posStartRow = strrpos(substr($listBody, 0, $posStart), '<li ');
        $posEndRow = strpos($listBody, '</li>', $posEnd);
        //echo "start row $posStartRow end row $posEndRow\n";
        if ($posStartRow === FALSE || $posEndRow === FALSE) {
            echo "start: $posStartRow end : $posEndRow marcatori di riga non trovati\n";
            return $listBody;
        }
        // prendo il blocco da ripetere e sostituisco le occorrenze
        $rigaDaRipetere = substr($listBody, $posStartRow, $posEndRow - $posStartRow + 5);
        //echo "daRiptere\n $rigaDaRipetere\n";
        //die();
        $subBody = "";
        foreach ($data[$key] as $values) {
            $tmpRiga = $rigaDaRipetere;
            foreach ($values as $subKey => $value) {
                $searchKey = '{{' . $key . '.' . $subKey . '}}';
                //echo "searchKey $searchKey  value $value\n";
                $tmpRiga = str_replace($searchKey, $value, $tmpRiga);
            }
            $subBody .= $tmpRiga;
            //print_r($values);
        }
        // elimino la parte template e la sostituisco con la stringa valorizzata
        $part1 = substr($listBody, 0, $posStartRow);
        $part2 = substr($listBody, $posEndRow + 5);
        $listBody = $part1 . $subBody . $part2;
        //echo $subBody . "\n";
        //echo $body . "\n";
        return $listBody;
    }


    protected function getDocumentBody($googleFileId)
    {
        $service = new \Google_Service_Drive($this->client);
        $content = $service->files->export($googleFileId, 'text/html');
        return $content->getBody();
    }

    protected function getClientOAuth()
    {
        $client = new \Google_Client();
        $client->setApplicationName('Affitti');
        $client->setScopes([\Google_Service_Drive::DRIVE, \Google_Service_Drive::DRIVE_FILE, \Google_Service_Drive::DRIVE_READONLY]);
        $client->setAuthConfig(env('OAUTH_JSON'));
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Load previously authorized token from a file, if it exists.
        $tokenPath = env('GDOCS_TOKEN_PATH','').'token.json';
        if (file_exists($tokenPath)) {
//            \Log::info("TROVATO TOKEN::: " . $tokenPath);
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        $this->client = $client;
        return $client;
    }

    protected function _isAssoc(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    protected function _get($arr, $key)
    {
        $keys = explode('.', $key);
        print_r($keys);
        foreach ($keys as $subKey)
            $arr = array_get($arr, $subKey, []);
        return $arr;
    }

    protected function deleteGoogleDoc($fileId)
    {
        $service = new \Google_Service_Drive($this->client);
        $service->files->delete($fileId);
    }
}
