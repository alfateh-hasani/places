<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\WebNotificationService;
use App\Traits\generateSeoTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Customer-facing in-app notifications. Backed by the polymorphic
 * `web_notifications` store (separate from the mobile FCM `notifications` table);
 * all persistence/scoping lives in WebNotificationService.
 */
class CustomerNotificationController extends Controller
{
    use generateSeoTrait;

    public function __construct(private WebNotificationService $notifications)
    {
    }

    public function index(): View
    {
        $customer = Auth::guard('customer')->user();

        $this->generateSeo(
            __('customer.notifications_title').' | '.__('site.seo_title'),
            __('customer.notifications_title'),
            route('customer.notifications')
        );

        return view('customer.notifications', [
            'customer' => $customer,
            'notifications' => $this->notifications->paginate($customer),
            'unread_count' => $this->notifications->unreadCount($customer),
        ]);
    }

    /**
     * Open a notification: mark it read, then go to its action target (or back to
     * the list). GET is intentional — "opening" a notification is the natural click
     * action and marking-read on open is idempotent.
     */
    public function open(int $id): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        $notification = $this->notifications->find($customer, $id);

        if (! $notification) {
            abort(404);
        }

        $notification->markAsRead();

        $target = $this->safeInternalUrl($notification->data['action_url'] ?? null);

        return $target
            ? redirect()->to($target)
            : redirect()->route('customer.notifications');
    }

    public function markAllAsRead(): RedirectResponse
    {
        $this->notifications->markAllAsRead(Auth::guard('customer')->user());

        return redirect()->route('customer.notifications');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->notifications->delete(Auth::guard('customer')->user(), $id);

        return redirect()->route('customer.notifications');
    }

    /**
     * Guard against open redirects: only follow a notification's action_url when it
     * is a relative path or points at this same application host.
     */
    private function safeInternalUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $urlHost = parse_url($url, PHP_URL_HOST);

        return ($urlHost && $appHost && $urlHost === $appHost) ? $url : null;
    }
}
