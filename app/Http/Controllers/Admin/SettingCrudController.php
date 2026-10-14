<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Library\Widget;
use Backpack\Settings\app\Http\Controllers\SettingCrudController as BaseSettingCrudController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Extends the backpack/settings CRUD to render the OwnerRez master on/off toggle
 * on the Settings page. The toggle is NOT a settings-table row — it is backed by
 * the OWNERREZ_ENABLED env var: flipping it rewrites .env and clears the config
 * cache so config('ownerrez.enabled') reflects the change without a deploy.
 */
class SettingCrudController extends BaseSettingCrudController
{
    public function setupListOperation(): void
    {
        parent::setupListOperation();

        Widget::add([
            'type' => 'view',
            'view' => 'admin.settings.ownerrez_toggle',
        ])->to('before_content');
    }

    /**
     * Flip OWNERREZ_ENABLED from the Settings page and make it take effect now.
     */
    public function toggleOwnerRez(Request $request): RedirectResponse
    {
        // Access is already limited to authenticated admins by backpack_middleware()
        // on the route — the same level that guards the Settings page itself.
        $enabled = $request->boolean('enabled');

        $this->writeEnv('OWNERREZ_ENABLED', $enabled ? 'true' : 'false');

        // Reflect immediately for this request.
        config(['ownerrez.enabled' => $enabled]);

        // Make it stick for every subsequent request and queue worker: drop the cached
        // config so the new .env value is read fresh, and signal workers to restart.
        try {
            Artisan::call('config:clear');
            Artisan::call('queue:restart');
        } catch (\Throwable $e) {
            Log::warning('OwnerRez toggle: post-write refresh failed', ['error' => $e->getMessage()]);
        }

        \Alert::success(__($enabled ? 'cms.ownerrez_toggle_enabled' : 'cms.ownerrez_toggle_disabled'))->flash();

        return redirect()->back();
    }

    /**
     * Rewrite (or append) a single KEY=value line in the project .env file.
     */
    private function writeEnv(string $key, string $value): void
    {
        $path = base_path('.env');

        if (! is_file($path)) {
            Log::warning('OwnerRez toggle: .env file not found', ['path' => $path]);

            return;
        }

        $content = (string) file_get_contents($path);
        $pattern = '/^'.preg_quote($key, '/').'=.*/m';

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $key.'='.$value, $content);
        } else {
            $content = rtrim($content, "\n")."\n".$key.'='.$value."\n";
        }

        file_put_contents($path, $content, LOCK_EX);
    }
}
