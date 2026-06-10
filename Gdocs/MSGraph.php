<?php

namespace Modules\CupGdocs\Gdocs;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MSGraph
{
    protected static ?string $accessToken = null;

    public static function getAccessToken(): string
    {
        if (self::$accessToken) {
            return self::$accessToken;
        }

        $config = config('cupparis-msdocs');
        $tokenPath = $config['token_path'];

        if ($config['auth_mode'] === 'application') {
            self::$accessToken = self::fetchClientCredentialsToken($config);
            return self::$accessToken;
        }

        if (file_exists($tokenPath)) {
            $stored = json_decode(file_get_contents($tokenPath), true);
            if ($stored && isset($stored['expires_at']) && $stored['expires_at'] > time() + 60) {
                self::$accessToken = $stored['access_token'];
                return self::$accessToken;
            }
            if (!empty($stored['refresh_token'])) {
                self::$accessToken = self::refreshToken($config, $stored['refresh_token']);
                return self::$accessToken;
            }
        }

        throw new \RuntimeException(
            'Token Microsoft Graph non trovato o scaduto. Eseguire: php artisan msdocs:auth'
        );
    }

    public static function saveDelegatedToken(array $tokenResponse): void
    {
        $tokenPath = config('cupparis-msdocs.token_path');
        $dir = dirname($tokenPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $tokenResponse['expires_at'] = time() + (int) ($tokenResponse['expires_in'] ?? 3600);
        file_put_contents($tokenPath, json_encode($tokenResponse, JSON_PRETTY_PRINT));
        self::$accessToken = $tokenResponse['access_token'];
    }

    public static function getAuthorizationUrl(): string
    {
        $config = config('cupparis-msdocs');
        $params = http_build_query([
            'client_id' => $config['client_id'],
            'response_type' => 'code',
            'redirect_uri' => $config['redirect_uri'] ?? 'http://localhost',
            'response_mode' => 'query',
            'scope' => 'offline_access Files.ReadWrite User.Read',
        ]);

        return "https://login.microsoftonline.com/{$config['tenant_id']}/oauth2/v2.0/authorize?{$params}";
    }

    public static function exchangeAuthCode(string $authCode): array
    {
        $config = config('cupparis-msdocs');
        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/{$config['tenant_id']}/oauth2/v2.0/token",
            [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'code' => $authCode,
                'redirect_uri' => $config['redirect_uri'] ?? 'http://localhost',
                'grant_type' => 'authorization_code',
            ]
        );

        if (!$response->successful()) {
            throw new \RuntimeException('Errore token Microsoft: ' . $response->body());
        }

        $data = $response->json();
        self::saveDelegatedToken($data);

        return $data;
    }

    public static function request(string $method, string $path, array $options = []): \Illuminate\Http\Client\Response
    {
        $url = str_starts_with($path, 'https://')
            ? $path
            : 'https://graph.microsoft.com/v1.0' . $path;

        $request = Http::withToken(self::getAccessToken())
            ->timeout(120)
            ->acceptJson();

        if (!empty($options['headers'])) {
            $request = $request->withHeaders($options['headers']);
        }

        $body = $options['body'] ?? null;

        return match (strtoupper($method)) {
            'GET' => $request->get($url, $options['query'] ?? []),
            'POST' => $body !== null
                ? $request->withBody($body, $options['content_type'] ?? 'application/json')->post($url)
                : $request->post($url, $options['json'] ?? []),
            'PUT' => $request->withBody($body, $options['content_type'] ?? 'application/octet-stream')->put($url),
            'PATCH' => $request->patch($url, $options['json'] ?? []),
            'DELETE' => $request->delete($url),
            default => throw new \InvalidArgumentException("Metodo HTTP non supportato: {$method}"),
        };
    }

    public static function driveBasePath(): string
    {
        $config = config('cupparis-msdocs');

        if ($config['auth_mode'] === 'application') {
            if (!empty($config['user_id'])) {
                return "/users/{$config['user_id']}/drive";
            }
            if (!empty($config['drive_id'])) {
                return "/drives/{$config['drive_id']}";
            }
            throw new \RuntimeException('Per auth application specificare MSDOCS_USER_ID o MSDOCS_DRIVE_ID');
        }

        return '/me/drive';
    }

    protected static function fetchClientCredentialsToken(array $config): string
    {
        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/{$config['tenant_id']}/oauth2/v2.0/token",
            [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ]
        );

        if (!$response->successful()) {
            throw new \RuntimeException('Errore client credentials: ' . $response->body());
        }

        return $response->json('access_token');
    }

    protected static function refreshToken(array $config, string $refreshToken): string
    {
        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/{$config['tenant_id']}/oauth2/v2.0/token",
            [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]
        );

        if (!$response->successful()) {
            Log::error('MSGraph refresh token fallito: ' . $response->body());
            throw new \RuntimeException('Refresh token Microsoft scaduto. Eseguire: php artisan msdocs:auth');
        }

        $data = $response->json();
        self::saveDelegatedToken($data);

        return $data['access_token'];
    }

    public static function getPdf(string $itemId): string {
        $token = self::getAccessToken();
        $response = Http::withToken($token)
            ->withHeaders([
                'Accept' => 'application/pdf',
            ])
            ->get(
                "https://graph.microsoft.com/v1.0/me/drive/items/{$itemId}/content?format=pdf"
            );

        if (!$response->successful()) {
            Log::error('MSGraph getPdf error: ' . $response->body());
            throw new \RuntimeException('Errore getPdf: ' . $response->body());
        }

        $pdfContent = $response->body();

        return $pdfContent;

        // file_put_contents(
        //     storage_path('app/documento.pdf'),
        //     $pdfContent
        // );
    }
    public static function createFolder(string $name, ?string $folderId = null): string {
        $token = self::getAccessToken();
        $folder = new \stdClass();
        if ($folderId) {
            $folder->id = $folderId;
        }
        $folder = json_encode($folder);
        $response = Http::withToken($token)
            ->post(
                'https://graph.microsoft.com/v1.0/me/drive/root/children',
                [
                    'name' => $name,
                    'folder' => $folder,
                    '@microsoft.graph.conflictBehavior' => 'rename',
                ]
            );

        if (!$response->successful()) {
            Log::error('MSGraph createFolder error: ' . $response->body());
            throw new \RuntimeException('Errore createFolder: ' . $response->body());
        }

        $data = $response->json();
        return $data['id'];
    }

    public static function getFolders(string $path): array {
        $token = self::getAccessToken();
        $apiPath = $path ? "https://graph.microsoft.com/v1.0/me/drive/root:/{$path}:/children" : "https://graph.microsoft.com/v1.0/me/drive/root/children";
        $response = Http::withToken($token)
            ->get(
                $apiPath
            );
        if (!$response->successful()) {
            Log::error('MSGraph getFolders error: ' . $response->body());
            throw new \RuntimeException('Errore getFolders: ' . $response->body());
        }
        $data = $response->json();
        //print_r($data);
        return $data['value'];
    }

    public static function getFolderId(string $path): string {
        $token = self::getAccessToken();
        Log::info("getFolderId: " . $path);
        $response = Http::withToken($token)
            ->get(
                "https://graph.microsoft.com/v1.0/me/drive/root:/{$path}"
            );
        if (isset($response['error'])) {
            Log::error('MSGraph getFolderId error: ' . print_r($response['error'],true));
            return '';
        }
        $folderId = $response['id'];
        return $folderId;
    }

    public static function deleteItem(string $itemId): void {
        $token = self::getAccessToken();
        $response = Http::withToken($token)
            ->delete(
                "https://graph.microsoft.com/v1.0/me/drive/items/{$itemId}"
            );
        if (!$response->successful()) {
            Log::error('MSGraph deleteItem error: ' . $response->body());
            throw new \RuntimeException('Errore deleteItem: ' . $response->body());
        }
    }   
    public static function getUrl(string $itemId): string {
        $token = self::getAccessToken();
        $response = Http::withToken($token)
            ->get(
                "https://graph.microsoft.com/v1.0/me/drive/items/{$itemId}"
            );
        $data = $response->json();
        if (isset($data['error'])) {
            Log::error('MSGraph getUrl error: ' . print_r($data['error'],true));
            return '';
        }
        return Arr::get($data,'webUrl','');
    }
}
