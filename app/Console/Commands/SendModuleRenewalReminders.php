<?php



namespace App\Console\Commands;



use App\Models\ModulePurchase;

use App\Models\NotificationLog;

use App\Notifications\ExpiredModuleNotification;

use App\Notifications\RenewalReminderNotification;

use Illuminate\Console\Command;

use Illuminate\Database\QueryException;

use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Notification;

use Throwable;



class SendModuleRenewalReminders extends Command

{

    protected $signature = 'modules:send-renewal-reminders';

    protected $description = 'Send module renewal/expired reminders for paid module purchases.';



    public function handle(): int

    {

        $tz = 'Europe/Istanbul';

        $today = Carbon::now($tz)->startOfDay();



        $windowStartUtc = $today->copy()->subDays(8)->timezone('UTC')->startOfDay();

        $windowEndUtc = $today->copy()->addDays(8)->timezone('UTC')->endOfDay();



        $targetsBefore = [7, 3, 1];

        $targetsAfter = [1, 3, 7];



        ModulePurchase::query()

            ->where('status', 'paid')

            ->whereIn('period', ['monthly', 'yearly'])

            ->whereNotNull('ends_at')

            ->with(['user', 'module'])

            ->orderBy('ends_at')

            ->chunkById(200, function ($purchases) use ($today, $tz, $targetsBefore, $targetsAfter) {

                foreach ($purchases as $purchase) {

                    $user = $purchase->user;

                    if (!$user) {

                        continue;

                    }
$endsAt = $purchase->ends_at;

                    if (!$endsAt) {

                        continue;

                    }
$endsDate = $endsAt->copy()->timezone($tz)->startOfDay();

                    $diffDays = (int) $today->diffInDays($endsDate, false); // +: future, -: past



                    if (in_array($diffDays, $targetsBefore, true)) {

                        $type = "renewal.d{$diffDays}";

                        $this->sendOnce($purchase->id, $user->id, $type, function () use ($user, $purchase, $diffDays) {

                            Notification::send($user, new RenewalReminderNotification($purchase, $diffDays));

                        }, [

                            'days_left' => $diffDays,

                            'ends_at' => $endsAt->toISOString(),

                        ]);

                        continue;

                    }



                    if ($diffDays < 0 && in_array(abs($diffDays), $targetsAfter, true)) {

                        $daysAgo = abs($diffDays);

                        $type = "expired.p{$daysAgo}";

                        $this->sendOnce($purchase->id, $user->id, $type, function () use ($user, $purchase, $daysAgo) {

                            Notification::send($user, new ExpiredModuleNotification($purchase, $daysAgo));

                        }, [

                            'days_ago' => $daysAgo,

                            'ends_at' => $endsAt->toISOString(),

                        ]);

                    }

                }

            });



        return self::SUCCESS;

    }



    /**

     * @param callable():void $send

     * @param array<string,mixed> $meta

     */

    private function sendOnce(int $purchaseId, int $userId, string $type, callable $send, array $meta = []): void

    {

        $log = null;



        try {

            try {

                $log = DB::transaction(function () use ($purchaseId, $userId, $type, $meta) {

                    $existing = NotificationLog::query()

                        ->where('module_purchase_id', $purchaseId)

                        ->where('type', $type)

                        ->first();



                    if ($existing) {

                        return null;

                    }



                    return NotificationLog::create([

                        'user_id' => $userId,

                        'module_purchase_id' => $purchaseId,

                        'type' => $type,

                        'sent_at' => Carbon::now(),

                        'meta' => empty($meta) ? null : $meta,

                    ]);

                });

            } catch (QueryException $e) {

                $message = $e->getMessage();

                if (str_contains($message, 'UNIQUE') || str_contains($message, 'Duplicate')) {

                    return;

                }

                throw $e;

            }



            if (!$log) {

                return;

            }
$send();

        } catch (Throwable $e) {

            if ($log) {

                try {

                    $log->delete();

                } catch (Throwable) {

                    // ignore

                }

            }



            Log::error('modules.reminders.send_failed', [

                'purchase_id' => $purchaseId,

                'user_id' => $userId,

                'type' => $type,

                'message' => $e->getMessage(),

            ]);

        }

    }

}

