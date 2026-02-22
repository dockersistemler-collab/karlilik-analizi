<?php

namespace App\Domains\Settlements\Models;

use App\Domains\Tenancy\Concerns\BelongsToTenant;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnRecord extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $table = 'returns';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'marketplace_return_id',
        'status',
        'amounts',
        'raw_payload',
    ];

    protected $casts = [
        'amounts' => 'array',
        'raw_payload' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

