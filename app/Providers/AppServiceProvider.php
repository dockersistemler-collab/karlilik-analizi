<?php

namespace App\Providers;

use App\Domain\Tickets\Models\Ticket;
use App\Domain\Tickets\Policies\TicketPolicy;
use App\Models\Order;
use App\Models\EInvoice;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Observers\OrderObserver;
use App\Policies\EInvoicePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\NotificationPolicy;
use App\Policies\NotificationPreferencePolicy;
use App\Support\SupportUser;
use App\Support\CorrelationId;
use App\Domain\Profitability\ProfitabilityCalculator;
use App\Domain\Profitability\Contracts\ProductCostResolver;
use App\Domain\Profitability\Contracts\ShippingFeeResolver;
use App\Domain\Profitability\Contracts\RefundShippingResolver;
use App\Domain\Profitability\Contracts\VatRateResolver;
use App\Domain\Profitability\Resolvers\EloquentProductCostResolver;
use App\Domain\Profitability\Resolvers\MarketplaceDataShippingFeeResolver;
use App\Domain\Profitability\Resolvers\MarketplaceDataRefundShippingResolver;
use App\Domain\Profitability\Resolvers\OrderVatRateResolver;
use App\Domain\Profitability\Calculators\ProductCostCalculator;
use App\Domain\Profitability\Calculators\CommissionCalculator;
use App\Domain\Profitability\Calculators\ShippingFeeCalculator;
use App\Domain\Profitability\Calculators\PlatformServiceFeeCalculator;
use App\Domain\Profitability\Calculators\RefundShippingAdjustmentCalculator;
use App\Domain\Profitability\Calculators\SalesVatCalculator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Support/helpers.php');

        $this->app->bind(ProductCostResolver::class, EloquentProductCostResolver::class);
        $this->app->bind(ShippingFeeResolver::class, MarketplaceDataShippingFeeResolver::class);
        $this->app->bind(RefundShippingResolver::class, MarketplaceDataRefundShippingResolver::class);
        $this->app->bind(VatRateResolver::class, OrderVatRateResolver::class);

        $this->app->tag([
            ProductCostCalculator::class,
            CommissionCalculator::class,
            ShippingFeeCalculator::class,
            PlatformServiceFeeCalculator::class,
            RefundShippingAdjustmentCalculator::class,
            SalesVatCalculator::class,
        ], 'profitability.calculators');

        $this->app->bind(ProfitabilityCalculator::class, function ($app) {
            return new ProfitabilityCalculator($app->tagged('profitability.calculators'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') && config('queue.default') === 'sync') {
            throw new \RuntimeException('QUEUE_CONNECTION=sync production ortaminda kullanilamaz. Redis/Database queue + worker zorunlu.');
        }

        RateLimiter::for('api-token', function (Request $request) {
            $token = $request->user()?->currentAccessToken();
            $key = $token ? "token:{$token->id}" : "ip:{$request->ip()}";
            return Limit::perMinute(60)->by($key);
        });

        RateLimiter::for('provider-status', function (Request $request) {
            $token = $request->user()?->currentAccessToken();
            $key = $token ? "token:{$token->id}" : "ip:{$request->ip()}";
            return Limit::perMinute(20)->by($key);
        });

        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(EInvoice::class, EInvoicePolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);
        Gate::policy(NotificationPreference::class, NotificationPreferencePolicy::class);
        Order::observe(OrderObserver::class);

        Gate::before(function (User $user): ?bool {
            return $user->isSuperAdmin() ? true : null;
        });

        Queue::createPayloadUsing(function () {
            $correlationId = CorrelationId::current() ?? (string) Str::uuid();
            return ['correlation_id' => $correlationId];
        });

        Queue::before(function (JobProcessing $event): void {
            $payload = $event->job->payload();
            $correlationId = $payload['correlation_id'] ?? null;
            CorrelationId::set(is_string($correlationId) ? $correlationId : null);
        });

        Event::listen(CommandStarting::class, function (): void {
            CorrelationId::set(CorrelationId::current());
            Log::withContext(['entrypoint' => 'command']);
        });

        View::composer(['layouts.admin', 'layouts.super-admin', 'partials.notification-bell'], function ($view) {
            $authUser = SupportUser::currentUser();
            if (!$authUser) {
                $view->with('notificationUnreadCount', 0);
                $view->with('notificationHubRoute', null);
                return;
            }
            $isSuperAdminArea = request()->routeIs('super-admin.*');
            $audienceRole = $isSuperAdminArea ? 'super_admin' : (request()->attributes->get('sub_user') ? 'staff' : 'admin');
            $tenantId = $authUser->id;

            $unreadCount = Notification::query()
                ->forTenant($tenantId)
                ->where('channel', 'in_app')
                ->whereNull('read_at')
                ->where(function ($query) use ($authUser, $audienceRole) {
                    $query->where('user_id', $authUser->id)
                        ->orWhere(function ($q) use ($audienceRole) {
                            $q->whereNull('user_id')
                                ->where(function ($roleQ) use ($audienceRole) {
                                    $roleQ->whereNull('audience_role')
                                        ->orWhere('audience_role', $audienceRole);
                                });
                        });
                })
                ->count();

            $routeName = $isSuperAdminArea
                ? 'super-admin.notification-hub.notifications.index'
                : 'portal.notification-hub.notifications.index';

            $view->with('notificationUnreadCount', $unreadCount);
            $view->with('notificationHubRoute', $routeName);
        });

        if (Schema::hasTable('system_settings')) {
            $settings = app(SettingsRepository::class);
            $overrideEnabled = filter_var(
                $settings->get('mail', 'override_enabled', false),
                FILTER_VALIDATE_BOOLEAN
            );

            if ($overrideEnabled) {
                $encryption = $settings->get('mail', 'smtp.encryption', config('mail.mailers.smtp.encryption'));
                $encryption = $encryption === 'none' ? null : $encryption;

                config([
                    'mail.mailers.smtp.host' => $settings->get('mail', 'smtp.host', config('mail.mailers.smtp.host')),
                    'mail.mailers.smtp.port' => (int) $settings->get('mail', 'smtp.port', config('mail.mailers.smtp.port')),
                    'mail.mailers.smtp.username' => $settings->get('mail', 'smtp.username', config('mail.mailers.smtp.username')),
                    'mail.mailers.smtp.password' => $settings->get('mail', 'smtp.password', config('mail.mailers.smtp.password')),
                    'mail.mailers.smtp.encryption' => $encryption,
                    'mail.from.address' => $settings->get('mail', 'from.address', config('mail.from.address')),
                    'mail.from.name' => $settings->get('mail', 'from.name', config('mail.from.name')),
                ]);
            }
$ackSlaMinutes = $settings->get('incident_sla', 'ack_sla_minutes', null);
            if ($ackSlaMinutes !== null && $ackSlaMinutes !== '') {
                config(['incident_sla.ack_sla_minutes' => (int) $ackSlaMinutes]);
            }
$resolveSlaMinutes = $settings->get('incident_sla', 'resolve_sla_minutes', null);
            if ($resolveSlaMinutes !== null && $resolveSlaMinutes !== '') {
                config(['incident_sla.resolve_sla_minutes' => (int) $resolveSlaMinutes]);
            }
$staleMinutes = $settings->get('integration_health', 'stale_minutes', null);
            if ($staleMinutes !== null && $staleMinutes !== '') {
                config(['integration_health.stale_minutes' => (int) $staleMinutes]);
            }
$windowHours = $settings->get('integration_health', 'window_hours', null);
            if ($windowHours !== null && $windowHours !== '') {
                config(['integration_health.window_hours' => (int) $windowHours]);
            }
$degradedThreshold = $settings->get('integration_health', 'degraded_error_threshold', null);
            if ($degradedThreshold !== null && $degradedThreshold !== '') {
                config(['integration_health.degraded_error_threshold' => (int) $degradedThreshold]);
            }
$downRequiresCritical = $settings->get('integration_health', 'down_requires_critical', null);
            if ($downRequiresCritical !== null && $downRequiresCritical !== '') {
                config(['integration_health.down_requires_critical' => filter_var($downRequiresCritical, FILTER_VALIDATE_BOOLEAN)]);
            }
        }
    }
}
