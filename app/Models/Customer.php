<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'city',
        'district',
        'neighborhood',
        'street',
        'billing_address',
        'customer_type',
        'company_title',
        'tax_id',
        'tax_office',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
