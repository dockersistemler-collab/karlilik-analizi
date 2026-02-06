<?php

namespace App\Console\Commands;

use App\Http\Middleware\EnsureModuleEnabled;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class EntitlementsSmoke extends Command
{
    protected $signature = 'entitlements:smoke';

    protected $description = 'Runs a basic entitlements smoke check and writes a log.';

    public function handle(): int
    {
        $lines = [];
        $lines[] = 'Entitlements smoke: '.now()->toDateTimeString();

        $plan = Plan::query()->where('is_active', true)->first();
        if (!$plan) {
            $lines[] = 'No active plan found.';
        } else {
            $checks = [
                'feature.reports',
                'feature.exports',
                'feature.integrations',
                'feature.cargo_tracking',
                'feature.einvoice_api',
                'feature.einvoice_webhooks',
                'integration.marketplace.trendyol',
            ];

            foreach ($checks as $code) {
                $lines[] = sprintf('plan[%s] hasModule(%s) => %s', $plan->slug, $code, $plan->hasModule($code) ? 'true' : 'false');
            }
        }
$lines[] = $this->testPlaceholderValidation();

        $logPath = storage_path('logs/entitlements-smoke.log');
        file_put_contents($logPath, implode(PHP_EOL, $lines).PHP_EOL, FILE_APPEND);

        foreach ($lines as $line) {
            $this->line($line);
        }
$this->info('Log written: '.$logPath);

        return self::SUCCESS;
    }

    private function testPlaceholderValidation(): string
    {
        $user = User::query()->first();
        if (!$user) {
            return 'placeholder_validation: skipped (no user)';
        }
$middleware = new EnsureModuleEnabled();
        $request = Request::create('/entitlements-smoke', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setRouteResolver(function () use ($request) {
            $route = new Route(['GET'], '/entitlements-smoke', []);
            $route->bind($request);
            $route->setParameter('marketplace', 'bad-value!');
            return $route;
        });

        try {
            $middleware->handle($request, fn () => null, 'integration.marketplace.{marketplace}');
        } catch (HttpException $e) {
            return $e->getStatusCode() === 400
                ? 'placeholder_validation: ok (400)'
                : 'placeholder_validation: unexpected status '.$e->getStatusCode();
        } catch (Throwable $e) {
            return 'placeholder_validation: failed '.$e->getMessage();
        }

        return 'placeholder_validation: failed (no exception)';
    }
}
