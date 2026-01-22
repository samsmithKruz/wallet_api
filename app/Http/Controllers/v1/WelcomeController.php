<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        version: '1.0.0',
        title: 'Wallet Management APIs',
        description: 'API for managing wallets and transfers with double-entry bookkeeping',
        contact: new OA\Contact(email: 'samspike46@gmail.com'),
        license: new OA\License(name: 'MIT', url: 'https://opensource.org/licenses/MIT')
    ),
    servers: [
        new OA\Server(url: 'http://localhost:8000', description: 'Local Development Server'),
    ],
    security: [
        ['tokenAuth' => []]
    ]
)]
#[OA\SecurityScheme(
    securityScheme: 'tokenAuth',
    type: 'apiKey',
    in: 'header',
    name: 'token',
    description: 'Use token: VG@123 in header'
)]

#[OA\Schema(
    schema: 'ApiResponse',
    description: 'Standard API response format',
    required: ['status_code', 'message'],
    properties: [
        new OA\Property(
            property: 'status_code',
            description: 'HTTP status code',
            type: 'integer',
            example: 200
        ),
        new OA\Property(
            property: 'message',
            description: 'Response message',
            type: 'string',
            example: 'Success'
        ),
        new OA\Property(
            property: 'data',
            description: 'Response data',
            type: 'object',
            nullable: true,
            additionalProperties: true
        ),
        new OA\Property(
            property: 'errors',
            description: 'Array of error messages',
            type: 'array',
            items: new OA\Items(type: 'string'),
            nullable: true,
            example: []
        ),
    ]
)]

#[OA\Schema(
    schema: 'ErrorResponse',
    description: 'Error response format',
    required: ['status_code', 'message'],
    properties: [
        new OA\Property(
            property: 'status_code',
            description: 'HTTP status code',
            type: 'integer',
            example: 400
        ),
        new OA\Property(
            property: 'message',
            description: 'Error message',
            type: 'string',
            example: 'Validation failed'
        ),
        new OA\Property(
            property: 'errors',
            description: 'Array of validation errors',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['The amount field is required.']
        ),
    ]
)]

#[OA\Schema(
    schema: 'Transaction',
    description: 'Transaction information',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'wallet_id', type: 'integer', example: 1),
        new OA\Property(property: 'type', type: 'string', example: 'credit'),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 500.00),
        new OA\Property(property: 'reference', type: 'string', format: 'uuid', example: '123e4567-e89b-12d3-a456-426614174000'),
        new OA\Property(property: 'description', type: 'string', example: 'Bank transfer', nullable: true),
        new OA\Property(property: 'status', type: 'string', example: 'completed'),
        new OA\Property(property: 'transfer_id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(
            property: 'wallet',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                new OA\Property(property: 'balance', type: 'number', format: 'float', example: 1500.00),
            ],
            type: 'object'
        ),
    ]
)]

#[OA\Schema(
    schema: 'Transfer',
    description: 'Transfer information with sender/receiver details',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'sender_wallet_id', type: 'integer', example: 1),
        new OA\Property(property: 'receiver_wallet_id', type: 'integer', example: 2),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 100.00),
        new OA\Property(property: 'reference', type: 'string', format: 'uuid', example: '123e4567-e89b-12d3-a456-426614174000'),
        new OA\Property(property: 'status', type: 'string', example: 'completed'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(
            property: 'sender',
            properties: [
                new OA\Property(property: 'wallet_id', type: 'integer', example: 1),
                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                new OA\Property(property: 'user_name', type: 'string', example: 'John Doe'),
                new OA\Property(property: 'user_email', type: 'string', example: 'john@example.com'),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'receiver',
            properties: [
                new OA\Property(property: 'wallet_id', type: 'integer', example: 2),
                new OA\Property(property: 'user_id', type: 'integer', example: 2),
                new OA\Property(property: 'user_name', type: 'string', example: 'Jane Smith'),
                new OA\Property(property: 'user_email', type: 'string', example: 'jane@example.com'),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'transactions',
            properties: [
                new OA\Property(
                    property: 'sender_transaction',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 10),
                        new OA\Property(property: 'type', type: 'string', example: 'transfer_out'),
                        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 100.00),
                        new OA\Property(property: 'reference', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'description', type: 'string', example: 'Payment for services'),
                        new OA\Property(property: 'status', type: 'string', example: 'completed'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                    ],
                    type: 'object',
                    nullable: true
                ),
                new OA\Property(
                    property: 'receiver_transaction',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 11),
                        new OA\Property(property: 'type', type: 'string', example: 'transfer_in'),
                        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 100.00),
                        new OA\Property(property: 'reference', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'description', type: 'string', example: 'Payment for services'),
                        new OA\Property(property: 'status', type: 'string', example: 'completed'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                    ],
                    type: 'object',
                    nullable: true
                ),
            ],
            type: 'object'
        ),
    ]
)]


#[OA\Schema(
    schema: 'User',
    description: 'User information',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
    ]
)]

#[OA\Schema(
    schema: 'Wallet',
    description: 'Wallet information with transaction summary',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'balance', type: 'number', format: 'float', example: 1000.00),
        new OA\Property(
            property: 'user',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'transaction_summary',
            properties: [
                new OA\Property(property: 'total_credits', type: 'number', format: 'float', example: 1500.00),
                new OA\Property(property: 'total_debits', type: 'number', format: 'float', example: 500.00),
                new OA\Property(property: 'total_transfers_in', type: 'number', format: 'float', example: 200.00),
                new OA\Property(property: 'total_transfers_out', type: 'number', format: 'float', example: 100.00),
                new OA\Property(property: 'net_flow', type: 'number', format: 'float', example: 1100.00),
            ],
            type: 'object'
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]

class WelcomeController extends Controller {}
