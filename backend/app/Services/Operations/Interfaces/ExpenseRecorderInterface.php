<?php

namespace App\Services\Operations\Interfaces;

interface ExpenseRecorderInterface
{
    /**
     * Record an expense in the finance module.
     * 
     * @param int $orgId The organization ID
     * @param float $amount The total amount
     * @param string $description The expense description
     * @param array $options Additional options (vendor_id, event_id, etc)
     * @return int The created expense ID
     */
    public function recordExpense(int $orgId, float $amount, string $description, array $options = []): int;
}
