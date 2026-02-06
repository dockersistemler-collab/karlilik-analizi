<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'source_type',
        'source_id',
        'parent_invoice_id',
        'marketplace',
        'marketplace_order_no',
        'status',
        'type',
        'invoice_no',
        'issued_at',
        'currency',
        'subtotal',
        'tax_total',
        'discount_total',
        'grand_total',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'billing_address_json',
        'shipping_address_json',
        'provider',
        'provider_invoice_id',
        'provider_status',
        'provider_payload_json',
        'pdf_path',
        'xml_path',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'billing_address_json' => 'array',
        'shipping_address_json' => 'array',
        'provider_payload_json' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parentInvoice()
    {
        return $this->belongsTo(EInvoice::class, 'parent_invoice_id');
    }

    public function childInvoices()
    {
        return $this->hasMany(EInvoice::class, 'parent_invoice_id');
    }

    public function items()
    {
        return $this->hasMany(EInvoiceItem::class, 'einvoice_id');
    }

    public function events()
    {
        return $this->hasMany(EInvoiceEvent::class, 'einvoice_id');
    }
}
