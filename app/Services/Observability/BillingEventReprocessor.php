<?php

namespace App\Services\Observability;

use App\Models\BillingEvent;
use App\Models\BillingEventAction;
use App\Services\BillingEventLogger;
use App\Support\CorrelationId;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class BillingEventReprocessor
{
    public function __construct(private readonly BillingEventLogger $events)
    {
    }

    public function reprocessWebhook(BillingEvent $event, $admin): BillingEventAction
    {
        $this->ensureAllowed($event, 'webhook_allowlist');
        $this->enforceIdempotency($event, 'reprocess_webhook');

        $correlationId = CorrelationId::set($event->correlation_id);

        $action = BillingEventAction::create([
            'billing_event_id' => $event->id,
            'action_type' => 'reprocess_webhook',
            'requested_by_admin_id' => $admin->id,
            'status' => 'queued',
            'correlation_id' => $correlationId,
        ]);

        try {
            $this->dispatchWebhook($event);
            $action->status = 'succeeded';
            $action->save();

            $this->events->record(['tenant_id' => $event->tenant_id,
                'user_id' => $event->user_id,
                'subscription_id' => $event->subscription_id,
                'invoice_id' => $event->invoice_id,
                'type' => 'observability.reprocess_webhook.succeeded',
                'status' => 'succeeded',
                'provider' => $event->provider,
                'payload' => [
                    'original_event_id' => $event->id,
                    'action_id' => $action->id,
                ],
            ]);
        } catch (\Throwable $e) {
            $action->status = 'failed';
            $action->error_message = Str::limit($e->getMessage(), 2000, '...');
            $action->save();

            $this->events->record(['tenant_id' => $event->tenant_id,
                'user_id' => $event->user_id,
                'subscription_id' => $event->subscription_id,
                'invoice_id' => $event->invoice_id,
                'type' => 'observability.reprocess_webhook.failed',
                'status' => 'failed',
                'provider' => $event->provider,
                'payload' => [
                    'original_event_id' => $event->id,
                    'action_id' => $action->id,
                    'error' => $e->getMessage(),
                ],
            ]);

            throw $e;
        }

        return $action;
    }

    public function retryJob(BillingEvent $event, $admin): BillingEventAction
    {
        $this->ensureAllowed($event, 'job_allowlist');
        $this->enforceIdempotency($event, 'retry_job');

        $correlationId = CorrelationId::set($event->correlation_id);

        $action = BillingEventAction::create([
            'billing_event_id' => $event->id,
            'action_type' => 'retry_job',
            'requested_by_admin_id' => $admin->id,
            'status' => 'queued',
            'correlation_id' => $correlationId,
        ]);

        try {
            $this->dispatchJob($event);
            $action->status = 'succeeded';
            $action->save();

            $this->events->record(['tenant_id' => $event->tenant_id,
                'user_id' => $event->user_id,
                'subscription_id' => $event->subscription_id,
                'invoice_id' => $event->invoice_id,
                'type' => 'observability.retry_job.succeeded',
                'status' => 'succeeded',
                'provider' => $event->provider,
                'payload' => [
                    'original_event_id' => $event->id,
                    'action_id' => $action->id,
                ],
            ]);
        } catch (\Throwable $e) {
            $action->status = 'failed';
            $action->error_message = Str::limit($e->getMessage(), 2000, '...');
            $action->save();

            $this->events->record(['tenant_id' => $event->tenant_id,
                'user_id' => $event->user_id,
                'subscription_id' => $event->subscription_id,
                'invoice_id' => $event->invoice_id,
                'type' => 'observability.retry_job.failed',
                'status' => 'failed',
                'provider' => $event->provider,
                'payload' => [
                    'original_event_id' => $event->id,
                    'action_id' => $action->id,
                    'error' => $e->getMessage(),
                ],
            ]);

            throw $e;
        }

        return $action;
    }

    private function dispatchWebhook(BillingEvent $event): void
    {
        if (Str::is('iyzico.webhook.*', $event->type)) {
            $payload = is_array($event->payload) ? $event->payload : [];
            $request = Request::create('/webhooks/iyzico/payment', 'POST', $payload);

            $secret = config('services.iyzico.webhook_secret');
            if (is_string($secret) && $secret !== '') {
                $request->headers->set('x-webhook-secret', $secret);
            }
$controller = App::make(\App\Http\Controllers\Webhooks\IyzicoPaymentWebhookController::class);
            $controller($request);
            return;
        }

        throw new UnprocessableEntityHttpException('Unsupported webhook type for reprocess.');
    }

    private function dispatchJob(BillingEvent $event): void
    {
        $payload = is_array($event->payload) ? $event->payload : [];
        $jobClass = $payload['job_class'] ?? null;
        $jobPayload = $payload['job_payload'] ?? [];

        if (is_string($jobClass) && class_exists($jobClass)) {
            $jobPayload = is_array($jobPayload) ? $jobPayload : [];
            $job = App::makeWith($jobClass, $jobPayload);
            dispatch($job);
            return;
        }

        if (Str::is('dunning.*', $event->type)) {
            \Illuminate\Support\Facades\Artisan::queue('billing:dunning-run', [
                '--tenant' => $event->tenant_id,
            ]);
            return;
        }

        throw new UnprocessableEntityHttpException('Job payload missing for retry.');
    }

    private function ensureAllowed(BillingEvent $event, string $configKey): void
    {
        $allowList = (array) config("observability.reprocess.{$configKey}", []);
        foreach ($allowList as $pattern) {
            if (Str::is($pattern, $event->type)) {
                return;
            }
        }

        throw new UnprocessableEntityHttpException('Event type not allowed for reprocess.');
    }

    private function enforceIdempotency(BillingEvent $event, string $actionType): void
    {
        $window = (int) config('observability.reprocess.idempotency_window_seconds', 120);
        $cutoff = Carbon::now()->subSeconds(max(1, $window));

        $existing = BillingEventAction::query()
            ->where('billing_event_id', $event->id)
            ->where('action_type', $actionType)
            ->where('created_at', '>=', $cutoff)
            ->latest('id')
            ->first();

        if ($existing) {
            throw new ConflictHttpException('Duplicate reprocess request.');
        }
    }
}
