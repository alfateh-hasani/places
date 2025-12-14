<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class OwnerRezOAuthController extends Controller
{
    public function index()
    {
        $accessToken = config('ownerrez.oauth.access_token') ?? Cache::get('ownerrez_access_token');
        $userId = config('ownerrez.oauth.user_id') ?? Cache::get('ownerrez_user_id');
        $clientId = config('ownerrez.oauth.client_id');
        $clientSecret = config('ownerrez.oauth.client_secret');

        $isConfigured = ! empty($clientId) && ! empty($clientSecret);
        $isConnected = ! empty($accessToken) && ! empty($userId);

        return view('admin.ownerrez.oauth', compact('isConfigured', 'isConnected', 'accessToken', 'userId'));
    }

    public function authorize()
    {
        $clientId = config('ownerrez.oauth.client_id');
        $redirectUri = config('ownerrez.oauth.redirect_uri');

        if (! $clientId) {
            return redirect()->route('admin.ownerrez.oauth')
                ->with('error', 'OAuth Client ID غير مُعرّف في .env');
        }

        $state = bin2hex(random_bytes(16));
        session(['ownerrez_oauth_state' => $state]);

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'all',
            'state' => $state,
        ]);

        $authUrl = "https://secure.ownerrez.com/oauth/authorize?{$params}";

        return redirect($authUrl);
    }

    public function disconnect()
    {
        $accessToken = config('ownerrez.oauth.access_token') ?? Cache::get('ownerrez_access_token');

        if ($accessToken) {
            // Optionally revoke the token via API
            try {
                $clientId = config('ownerrez.oauth.client_id');
                $clientSecret = config('ownerrez.oauth.client_secret');

                \Illuminate\Support\Facades\Http::withBasicAuth($clientId, $clientSecret)
                    ->delete("https://api.ownerrez.com/oauth/access_token/{$accessToken}");
            } catch (\Exception $e) {
                \Log::warning('Failed to revoke OwnerRez token', ['error' => $e->getMessage()]);
            }
        }

        // Clear from cache
        Cache::forget('ownerrez_access_token');
        Cache::forget('ownerrez_user_id');

        // Clear from .env
        $this->updateEnvFile([
            'OWNERREZ_ACCESS_TOKEN' => '',
            'OWNERREZ_USER_ID' => '',
        ]);

        return redirect()->route('admin.ownerrez.oauth')
            ->with('success', 'تم قطع الاتصال بنجاح');
    }

    protected function updateEnvFile(array $data): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            if (preg_match("/^{$key}=/m", $envContent)) {
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
    }
}
