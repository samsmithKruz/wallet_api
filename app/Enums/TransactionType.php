<?php

namespace App\Enums;

enum TransactionType: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';
    case TRANSFER_IN = 'transfer_in';
    case TRANSFER_OUT = 'transfer_out';

    public function isCredit(): bool
    {
        return $this === self::CREDIT;
    }

    public function isDebit(): bool
    {
        return $this === self::DEBIT;
    }

    public function isTransfer(): bool
    {
        return in_array($this, [self::TRANSFER_IN, self::TRANSFER_OUT]);
    }

    public function isIncoming(): bool
    {
        return in_array($this, [self::CREDIT, self::TRANSFER_IN]);
    }

    public function isOutgoing(): bool
    {
        return in_array($this, [self::DEBIT, self::TRANSFER_OUT]);
    }
}
