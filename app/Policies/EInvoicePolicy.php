<?php

namespace App\Policies;

use App\Models\EInvoice;
use App\Models\User;

class EInvoicePolicy
{
    public function view(User $user, EInvoice $einvoice): bool
    {
        return $einvoice->user_id === $user->id;
    }

    public function update(User $user, EInvoice $einvoice): bool
    {
        return $einvoice->user_id === $user->id;
    }
}

