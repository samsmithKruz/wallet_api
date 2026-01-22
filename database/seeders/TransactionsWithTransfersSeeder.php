<?php

namespace Database\Seeders;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\TransferStatus;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TransactionsWithTransfersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wallets = Wallet::all();

        if ($wallets->count() < 2) {
            $this->command->error('Need at least 2 wallets to create transfers. Run UsersWithWalletsSeeder first.');
            return;
        }

        $this->command->info("Creating transactions for {$wallets->count()} wallets...");

        foreach ($wallets as $wallet) {
            // Create initial credit transactions (3-5 per wallet)
            $creditCount = rand(3, 5);
            $totalCredits = 0;

            for ($i = 0; $i < $creditCount; $i++) {
                $amount = fake()->randomFloat(2, 50, 500);
                $totalCredits += $amount;

                Transaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => TransactionType::CREDIT,
                    'amount' => $amount,
                    'reference' => Str::uuid(),
                    'description' => fake()->randomElement([
                        'Initial deposit',
                        'Wallet top-up',
                        'Bank transfer',
                        'Cash deposit',
                        'Funds added',
                    ]),
                    'status' => TransactionStatus::COMPLETED,
                    'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
                ]);
            }

            // Create debit transactions (1-3 per wallet)
            $debitCount = rand(1, 3);
            $totalDebits = 0;

            for ($i = 0; $i < $debitCount; $i++) {
                $amount = fake()->randomFloat(2, 10, min(200, $totalCredits * 0.5));
                $totalDebits += $amount;

                Transaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => TransactionType::DEBIT,
                    'amount' => $amount,
                    'reference' => Str::uuid(),
                    'description' => fake()->randomElement([
                        'Withdrawal',
                        'Purchase',
                        'Bill payment',
                        'Service fee',
                        'Cash withdrawal',
                    ]),
                    'status' => TransactionStatus::COMPLETED,
                    'created_at' => fake()->dateTimeBetween('-20 days', 'now'),
                ]);
            }

            $this->command->info("Wallet {$wallet->id}: Created {$creditCount} credits, {$debitCount} debits");
        }

        // Create transfers between wallets
        $this->command->info('Creating transfers between wallets...');

        // Create 2-4 transfers
        $transferCount = rand(2, 4);

        for ($i = 0; $i < $transferCount; $i++) {
            // Get two different random wallets
            $sender = $wallets->random();
            $receiver = $wallets->where('id', '!=', $sender->id)->random();

            $amount = fake()->randomFloat(2, 10, min(100, $sender->balance * 0.3));

            // Create transfer record
            $transfer = Transfer::create([
                'sender_wallet_id' => $sender->id,
                'receiver_wallet_id' => $receiver->id,
                'amount' => $amount,
                'reference' => Str::uuid(),
                'status' => TransferStatus::COMPLETED,
                'created_at' => fake()->dateTimeBetween('-10 days', 'now'),
            ]);

            // Create sender transaction (transfer_out)
            $senderTransaction = Transaction::create([
                'wallet_id' => $sender->id,
                'type' => TransactionType::TRANSFER_OUT,
                'amount' => $amount,
                'reference' => Str::uuid(),
                'description' => 'Transfer to ' . $receiver->user->name,
                'status' => TransactionStatus::COMPLETED,
                'transfer_id' => $transfer->id,
                'created_at' => $transfer->created_at,
            ]);

            // Create receiver transaction (transfer_in)
            $receiverTransaction = Transaction::create([
                'wallet_id' => $receiver->id,
                'type' => TransactionType::TRANSFER_IN,
                'amount' => $amount,
                'reference' => Str::uuid(),
                'description' => 'Transfer from ' . $sender->user->name,
                'status' => TransactionStatus::COMPLETED,
                'transfer_id' => $transfer->id,
                'created_at' => $transfer->created_at,
            ]);

            // Update wallet balances
            $sender->decrement('balance', $amount, []);
            $receiver->increment('balance', $amount, []);

            $this->command->info("Transfer " . ($i + 1) . ":" . $sender->user->name . " " . $receiver->user->name . " (\$" . $amount . ")");
        }

        $this->command->info('Transactions and transfers created successfully!');
    }
}
