<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EInvoiceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'active_provider_key',
        'auto_draft_enabled',
        'auto_issue_enabled',
        'draft_on_status',
        'issue_on_status',
        'prefix',
        'default_vat_rate',
    ];

    protected $casts = [
        'auto_draft_enabled' => 'boolean',
        'auto_issue_enabled' => 'boolean',
        'default_vat_rate' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
