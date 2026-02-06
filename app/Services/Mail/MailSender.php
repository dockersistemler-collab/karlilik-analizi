<?php

namespace App\Services\Mail;

use App\Mail\TemplateMailable;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailSender
{
    public function __construct(private readonly MailPolicyService $policy)
    {
    }

    public function send(string $key, User $user, array $data = [], array $meta = []): void
    {
        $decision = $this->policy->canSend($key, $user, $meta);

        if ($decision['decision'] !== MailPolicyService::DECISION_SEND) {
            $this->log($key, $user, $decision['decision'], null, null, array_merge($meta, [
                'reason' => $decision['reason'],
            ]));
            return;
        }
$template = MailTemplate::query()->where('key', $key)->first();
        if (!$template) {
            $this->log($key, $user, MailPolicyService::DECISION_SKIPPED, null, 'template_missing', $meta);
            return;
        }
$subject = $this->renderString($template->subject, $data);
        $bodyHtml = $this->renderString($template->body_html, $data);

        try {
            Mail::to($user->email)->queue(new TemplateMailable($subject, $bodyHtml));
            $this->log($key, $user, 'success', null, null, $meta, now());
        } catch (Throwable $e) {
            $this->log($key, $user, 'failed', null, $e->getMessage(), $meta);
            report($e);
        }
    }

    public function renderPreview(string $value, array $data): string
    {
        return $this->renderString($value, $data);
    }

    private function renderString(string $value, array $data): string
    {
        $rendered = $value;
        foreach ($data as $key => $val) {
            if (!is_scalar($val) && $val !== null) {
                continue;
            }
$rendered = str_replace('{{'.$key.'}}', (string) $val, $rendered);
        }

        return $rendered;
    }

    private function log(
        string $key,
        User $user,
        string $status,
        ?string $providerMessageId,
        ?string $error,
        array $meta,
        $sentAt = null
    ): void {
        MailLog::create([
            'key' => $key,
            'user_id' => $user->id,
            'status' => $status,
            'provider_message_id' => $providerMessageId,
            'error' => $error,
            'metadata_json' => $meta,
            'sent_at' => $sentAt,
        ]);
    }
}
