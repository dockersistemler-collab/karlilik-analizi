<?php

namespace App\Domain\Tickets\Queries;

use App\Domain\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

class TicketListQuery
{
    public function forCustomer(int $customerId, array $filters = []): Builder
    {
        $query = Ticket::query()->forCustomer($customerId);

        return $this->applyFilters($query, $filters);
    }

    public function forAdmin(array $filters = []): Builder
    {
        $query = Ticket::query();

        return $this->applyFilters($query, $filters);
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, function (Builder $builder, $status) {
                $builder->where('status', $status);
            })
            ->when($filters['customer_id'] ?? null, function (Builder $builder, $customerId) {
                $builder->where('customer_id', $customerId);
            })
            ->when($filters['assigned_to_id'] ?? null, function (Builder $builder, $assigneeId) {
                $builder->where('assigned_to_id', $assigneeId);
            })
            ->when($filters['search'] ?? null, function (Builder $builder, $search) {
                $builder->where('subject', 'like', '%'.$search.'%');
            })
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id');
    }
}
