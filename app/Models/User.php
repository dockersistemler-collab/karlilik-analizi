<?php

namespace App\Models;

use App\Domain\Tickets\Models\Ticket;
use App\Models\Customer;
use App\Models\EInvoiceSetting;
use App\Models\EInvoiceProviderInstallation;
use App\Models\CargoProviderInstallation;
use App\Models\SupportAccessLog;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Models\Referral;
use App\Models\ReferralProgram;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'billing_name',
        'billing_email',
        'billing_address',
        'company_name',
        'company_slogan',
        'company_phone',
        'notification_email',
        'company_address',
        'company_website',
        'company_logo_path',
        'invoice_number_tracking',
        'password',
        'role',
        'plan_code',
        'plan_started_at',
        'plan_expires_at',
        'is_active',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'invoice_number_tracking' => 'boolean',
            'plan_started_at' => 'datetime',
            'plan_expires_at' => 'datetime',
        ];
    }

    // İlişkiler
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function userModules()
    {
        return $this->hasMany(UserModule::class);
    }

    public function einvoiceSetting()
    {
        return $this->hasOne(EInvoiceSetting::class);
    }

    public function einvoiceProviderInstallations()
    {
        return $this->hasMany(EInvoiceProviderInstallation::class);
    }

    public function einvoiceProviderInstallation(string $key)
    {
        return $this->einvoiceProviderInstallations()->where('provider_key', $key)->first();
    }

    public function cargoProviderInstallations()
    {
        return $this->hasMany(CargoProviderInstallation::class);
    }

    public function cargoProviderInstallation(string $key)
    {
        return $this->cargoProviderInstallations()->where('provider_key', $key)->first();
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'user_modules')
            ->withPivot(['status', 'starts_at', 'ends_at', 'meta'])
            ->withTimestamps();
    }

    public function subscription()
    {
        // En güncel abonelik
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function marketplaceCredentials()
    {
        return $this->hasMany(MarketplaceCredential::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'customer_id');
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_to_id');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function subUsers()
    {
        return $this->hasMany(SubUser::class, 'owner_user_id');
    }

    public function webhookEndpoints()
    {
        return $this->hasMany(WebhookEndpoint::class);
    }

    public function webhookDeliveries()
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    public function supportAccessLogsAsSuperAdmin()
    {
        return $this->hasMany(SupportAccessLog::class, 'super_admin_id');
    }

    public function supportAccessLogsAsTarget()
    {
        return $this->hasMany(SupportAccessLog::class, 'target_user_id');
    }

    // Helper metodlar
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription && $this->subscription->isActive();
    }

    public function getActivePlan()
    {
        return $this->subscription?->plan;
    }
}

