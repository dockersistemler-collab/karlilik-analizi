<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        return (int) $invoice->user_id === (int) $user->id;
    }

    public function download(User $user, Invoice $invoice): bool
    {
        return (int) $invoice->user_id === (int) $user->id;
    }
}
