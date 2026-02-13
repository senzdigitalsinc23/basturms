<?php
namespace Repositories;

use App\Models\Transaction;

class TransactionRepository
{
    public function create(array $data): Transaction
    {
        $transaction = new Transaction();
        foreach ($data as $key => $value) {
            $transaction->$key = $value;
        }
        $transaction->save();
        return $transaction;
    }

    public function findByTransactionId(string $gateway, string $transactionId): ?Transaction
    {
        return Transaction::where('gateway', $gateway)
            ->where('transaction_id', $transactionId)
            ->first();
    }

    public function updateStatus(string $gateway, string $transactionId, string $status): void
    {
        $transaction = $this->findByTransactionId($gateway, $transactionId);
        if ($transaction) {
            $transaction->status = $status;
            $transaction->save();
        }
    }
}
