<?php

namespace App\Models;

use App\Domain\Tickets\Models\Ticket;
use App\Models\Customer;
use App\Models\Referral;
use App\Models\ReferralProgram;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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
        ];
    }

    // İlişkiler
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
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

    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
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
