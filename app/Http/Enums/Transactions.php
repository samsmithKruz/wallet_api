<?php 

enum TransactionTypes: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';
    case TRANSFER_IN = 'transfer_in';
    case TRANSFER_OUT = 'transfer_out';
}

enum TransactionStatuses: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}