<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    protected $fillable = [
        'webhook_endpoint_id',
        'user_id',
        'delivery_uuid',
        'event',
        'payload_json',
        'payload_log_json',
        'request_body',
        'dedupe_key',
        'attempt',
        'status',
        'http_status',
        'response_body',
        'response_headers_json',
        'duration_ms',
        'next_retry_at',
        'last_error',
        'request_id',
        'request_headers_json',
        'signature_timestamp',
        'signature_v1',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'payload_log_json' => 'array',
        'request_headers_json' => 'array',
        'response_headers_json' => 'array',
        'next_retry_at' => 'datetime',
        'duration_ms' => 'integer',
        'attempt' => 'integer',
        'signature_timestamp' => 'integer',
    ];

    public function endpoint()
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
