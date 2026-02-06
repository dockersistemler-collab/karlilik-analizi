<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingEvent;
use App\Services\Observability\BillingEventReprocessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class BillingEventActionController extends Controller
{
    public function reprocessWebhook(
        Request $request,
        BillingEvent $billingEvent,
        BillingEventReprocessor $reprocessor
    ): JsonResponse|RedirectResponse {
        try {
            $action = $reprocessor->reprocessWebhook($billingEvent, $request->user());
        } catch (ConflictHttpException $e) {
            return $this->respondError($request, $e->getMessage(), 409);
        } catch (UnprocessableEntityHttpException $e) {
            return $this->respondError($request, $e->getMessage(), 422);
        } catch (\Throwable $e) {
            Log::error('observability.reprocess_webhook_failed', [
                'billing_event_id' => $billingEvent->id,
                'error' => $e->getMessage(),
            ]);
            return $this->respondError($request, 'Reprocess basarisiz.', 500);
        }

        if ($request->expectsJson()) {
            return response()->json(['status' => $action->status, 'action_id' => $action->id]);
        }

        return back()->with('success', 'Webhook yeniden isleme alindi.');
    }

    public function retryJob(
        Request $request,
        BillingEvent $billingEvent,
        BillingEventReprocessor $reprocessor
    ): JsonResponse|RedirectResponse {
        try {
            $action = $reprocessor->retryJob($billingEvent, $request->user());
        } catch (ConflictHttpException $e) {
            return $this->respondError($request, $e->getMessage(), 409);
        } catch (UnprocessableEntityHttpException $e) {
            return $this->respondError($request, $e->getMessage(), 422);
        } catch (\Throwable $e) {
            Log::error('observability.retry_job_failed', [
                'billing_event_id' => $billingEvent->id,
                'error' => $e->getMessage(),
            ]);
            return $this->respondError($request, 'Retry basarisiz.', 500);
        }

        if ($request->expectsJson()) {
            return response()->json(['status' => $action->status, 'action_id' => $action->id]);
        }

        return back()->with('success', 'Job yeniden kuyruga alindi.');
    }

    private function respondError(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return back()->with('error', $message);
    }
}
