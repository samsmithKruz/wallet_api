# Wallet Management API

A robust Laravel-based Wallet Management System with double-entry bookkeeping, secure transfers, and comprehensive transaction tracking.

## 📋 Features

- **Wallet Management**: Create, view, fund, withdraw, and delete wallets
- **Payment Transfers**: Secure money transfers between users with double-entry consistency
- **Transaction Ledger**: Complete audit trail for all financial operations
- **Security**: Token-based authentication and race condition prevention
- **Data Integrity**: Atomic operations with database transactions and row locking

## 🏗️ Architecture

- **MVC Pattern** with clean service-layer abstraction
- **Repository/Service Pattern** for business logic separation
- **RESTful API** with JSON responses
- **OpenAPI/Swagger** documentation
- **Database Transactions** for data consistency

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- MySQL 8.0+
- Laravel 12+

### Installation

1. **Clone the repository**
```bash
git clone <repository-url>
cd wallet-api
```

2. **Install dependencies**
```bash
composer install
```

3. **Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Update database configuration in `.env`**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wallet_api
DB_USERNAME=root
DB_PASSWORD=
```

5. **Run migrations and seeders**
```bash
php artisan migrate --seed
```

6. **Start the development server**
```bash
php artisan serve
```

7. **Access the API documentation**
```
http://localhost:8000/api/v1/documentation
```

## 🔐 Authentication

All API endpoints require token authentication:

```bash
# Include this header in all requests:
token: VG@123
```

## 📊 Database Schema

### Tables
- **users**: User accounts (extends Laravel's default)
- **wallets**: One-to-one with users, stores balance
- **transactions**: All money movements (credits, debits, transfers)
- **transfers**: Links two transactions for transfers

### Key Relationships
- User → hasOne → Wallet
- Wallet → hasMany → Transaction
- Transfer → hasMany → Transaction (sender_tx + receiver_tx)

## 🛠️ API Endpoints

### Wallet Management

#### 1. Create Wallet
```http
POST /api/v1/wallets
Content-Type: application/json
token: VG@123

{
    "user_id": 1,
    "initial_balance": 1000.00
}
```

#### 2. View Wallet Balance
```http
GET /api/v1/wallets/{id}
token: VG@123
```

#### 3. Fund Wallet (Credit)
```http
POST /api/v1/wallets/{id}/fund
Content-Type: application/json
token: VG@123

{
    "amount": 500.00,
    "description": "Bank transfer"
}
```

#### 4. Withdraw from Wallet (Debit)
```http
POST /api/v1/wallets/{id}/withdraw
Content-Type: application/json
token: VG@123

{
    "amount": 200.00,
    "description": "Cash withdrawal"
}
```

#### 5. Delete Wallet
```http
DELETE /api/v1/wallets/{id}
token: VG@123
```

### Payment Transfers

#### 1. Initiate Transfer
```http
POST /api/v1/transfers
Content-Type: application/json
token: VG@123

{
    "sender_wallet_id": 1,
    "receiver_wallet_id": 2,
    "amount": 100.00,
    "description": "Payment for services"
}
```

#### 2. View Transfer Details
```http
GET /api/v1/transfers/{id}
token: VG@123
```

#### 3. View Wallet Transfers
```http
GET /api/v1/wallets/{walletId}/transfers
GET /api/v1/wallets/{walletId}/transfers?type=incoming
GET /api/v1/wallets/{walletId}/transfers?type=outgoing
GET /api/v1/wallets/{walletId}/transfers?page=2&per_page=10
token: VG@123
```

## 📈 Response Format

All responses follow this consistent JSON structure:

### Success Response
```json
{
    "status_code": 200,
    "message": "Success message",
    "data": { ... },
    "errors": null
}
```

### Error Response
```json
{
    "status_code": 400,
    "message": "Error message",
    "data": null,
    "errors": ["Error details"]
}
```

## 🔧 Services & Architecture

### Service Layer
- **WalletService**: Handles wallet operations (fund, withdraw, balance updates)
- **PaymentService**: Manages transfers with double-entry consistency

### Key Design Patterns
1. **Form Request Validation**: Input validation using Laravel Form Requests
2. **Service Abstraction**: Business logic separated from controllers
3. **Resource Transformation**: Consistent API response formatting
4. **Database Transactions**: Atomic operations for data integrity
5. **Row Locking**: Prevents race conditions during balance updates

## 🛡️ Security Features

### Data Integrity
- **Decimal Precision**: All monetary values stored as `decimal(15,2)`
- **Negative Balance Prevention**: Validation before debit operations
- **Race Condition Prevention**: `lockForUpdate()` on wallet rows
- **Atomic Transactions**: Database transactions for multi-step operations

### Validation Rules
- Amounts must be ≥ 1.00
- Sender ≠ receiver for transfers
- Sufficient balance checks
- User can only have one wallet
- Wallet deletion only with zero balance

## 📊 Database Seeder

Pre-populates the database with:
- 3 test users with random names/emails
- Each user has a wallet with random balance (100-5000)
- Credit/debit transactions for each wallet
- Sample transfers between wallets

Run seeder:
```bash
php artisan migrate:fresh --seed
```

## 🧪 Testing

### Run Tests
```bash
# Coming soon - test suite
```

### Manual Testing with cURL
```bash
# Test wallet creation
curl -X POST http://localhost:8000/api/v1/wallets \
  -H "Content-Type: application/json" \
  -H "token: VG@123" \
  -d '{"user_id": 1, "initial_balance": 1000}'

# Test transfer
curl -X POST http://localhost:8000/api/v1/transfers \
  -H "Content-Type: application/json" \
  -H "token: VG@123" \
  -d '{"sender_wallet_id": 1, "receiver_wallet_id": 2, "amount": 100}'
```

## 📚 API Documentation

Interactive API documentation available at:
```
http://localhost:8000/api/v1/documentation
```

Features:
- Try endpoints directly from browser
- View request/response schemas
- Authentication setup
- Example requests

## 🗄️ Database Migrations

Key migrations included:
```bash
2026_01_22_000001_create_wallets_table
2026_01_22_000003_create_transactions_table
2026_01_22_141940_create_transfers_table
```

## 📁 Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/V1/
│   │       ├── WalletController.php
│   │       └── TransferController.php
│   ├── Middleware/
│   │   └── TokenAuthMiddleware.php
│   └── Requests/
│       ├── CreateWalletRequest.php
│       ├── FundWalletRequest.php
│       ├── WithdrawWalletRequest.php
│       └── InitiateTransferRequest.php
├── Models/
│   ├── Wallet.php
│   ├── Transaction.php
│   └── Transfer.php
├── Services/
│   ├── WalletService.php
│   └── PaymentService.php
├── Traits/
│   └── ApiResponses.php
└── Exceptions/
    └── ApiException.php
```

## 🔄 Double-Entry Consistency

Transfers create two linked transactions:

1. **Sender**: `transfer_out` (debit)
2. **Receiver**: `transfer_in` (credit)

Both transactions:
- Share the same `transfer_id`
- Have unique UUID references
- Are created within a single database transaction
- Update respective wallet balances atomically

## ⚡ Performance Considerations

- **Indexed columns**: Frequently queried fields are indexed
- **Pagination**: Large result sets are paginated
- **Eager loading**: Relationships loaded efficiently
- **Row locking**: Prevents concurrent write issues

## 🚨 Error Handling

### HTTP Status Codes
- `200` OK - Successful request
- `201` Created - Resource created
- `400` Bad Request - Validation/ business rule failure
- `401` Unauthorized - Invalid/missing token
- `404` Not Found - Resource not found
- `422` Unprocessable Entity - Validation errors
- `500` Internal Server Error - Server error

### Common Error Scenarios
- Insufficient balance for withdrawal/transfer
- Attempt to create duplicate wallet for user
- Deleting wallet with non-zero balance
- Self-transfer attempts
- Invalid token authentication

## 📦 Dependencies

- **Laravel 12**: PHP framework
- **L5-Swagger**: OpenAPI documentation
- **Ramsey UUID**: UUID generation
- **Brick Math**: Precise decimal arithmetic

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📄 License

This project is licensed under the MIT License.

## 🙏 Acknowledgments

- Laravel framework
- OpenAPI/Swagger for documentation
- All contributors and testers

---

**Note**: This is a demonstration project for wallet management and payment transfer systems with double-entry bookkeeping.