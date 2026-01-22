<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitiateTransferRequest;
use App\Http\Resources\TransferResource;
use App\Models\Transfer;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\TransferStatus;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;
use App\Services\PaymentService;


class TransferController extends Controller
{
    //
    use ApiResponses;

    #[OA\Post(
        path: '/api/v1/transfers',
        operationId: 'initiateTransfer',
        summary: 'Initiate transfer between wallets',
        description: 'Send money from one user\'s wallet to another. Must validate: Sender != receiver, Sufficient balance, Atomicity (both debit & credit must succeed).',
        tags: ['Transfers'],
        security: [['tokenAuth' => []]]
    )]
    #[OA\RequestBody(
        description: 'Transfer initiation data',
        required: true,
        content: new OA\JsonContent(
            required: ['sender_wallet_id', 'receiver_wallet_id', 'amount'],
            properties: [
                new OA\Property(
                    property: 'sender_wallet_id',
                    type: 'integer',
                    example: 1
                ),
                new OA\Property(
                    property: 'receiver_wallet_id',
                    type: 'integer',
                    example: 2
                ),
                new OA\Property(
                    property: 'amount',
                    type: 'number',
                    format: 'float',
                    example: 100.00,
                    minimum: 1
                ),
                new OA\Property(
                    property: 'description',
                    type: 'string',
                    example: 'Payment for services',
                    nullable: true
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Transfer initiated successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')
    )]
    #[OA\Response(
        response: 400,
        description: 'Insufficient balance or validation error',
        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorized',
        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
    )]
    public function store(InitiateTransferRequest $request): JsonResponse
    {
        try {
            $paymentService = new PaymentService();
            $result = $paymentService->initiateTransfer(
                senderWalletId: (int) $request->sender_wallet_id,
                receiverWalletId: (int) $request->receiver_wallet_id,
                amount: $request->amount,
                description: $request->description
            );

            // Add transactions to transfer for the resource
            $result['transfer']->sender_transaction = $result['sender_transaction'];
            $result['transfer']->receiver_transaction = $result['receiver_transaction'];

            return $this->successResponse(
                data: new TransferResource($result['transfer']),
                message: 'Transfer initiated successfully',
                statusCode: 201
            );
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: $statusCode,
                errors: [$e->getMessage()]
            );
        }
    }

    #[OA\Get(
        path: '/api/v1/transfers/{id}',
        operationId: 'getTransfer',
        summary: 'View transfer details',
        description: 'Fetch details of a specific transfer including sender, receiver, timestamps, and status.',
        tags: ['Transfers'],
        security: [['tokenAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Transfer ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Transfer details retrieved successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorized',
        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
    )]
    #[OA\Response(
        response: 404,
        description: 'Transfer not found',
        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
    )]
    public function show(string $id): JsonResponse
    {
        try {
            $paymentService = new PaymentService();
            $transfer = $paymentService->getTransferWithDetails((int) $id);

            return $this->successResponse(
                data: new TransferResource($transfer),
                message: 'Transfer details retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse(
                message: 'Transfer not found',
                statusCode: 404
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: 'Failed to retrieve transfer details',
                statusCode: 500,
                errors: [$e->getMessage()]
            );
        }
    }

    #[OA\Get(
        path: '/api/v1/wallets/{walletId}/transfers',
        operationId: 'getWalletTransfers',
        summary: 'View all transfers for a wallet',
        description: 'List all incoming and outgoing transfers for a specific wallet.',
        tags: ['Transfers'],
        security: [['tokenAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'walletId',
        description: 'Wallet ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'type',
        description: 'Filter by transfer type',
        in: 'query',
        required: false,
        schema: new OA\Schema(
            type: 'string',
            enum: ['incoming', 'outgoing', 'all'],
            default: 'all'
        )
    )]
    #[OA\Parameter(
        name: 'page',
        description: 'Page number',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 1)
    )]
    #[OA\Parameter(
        name: 'per_page',
        description: 'Items per page',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 15)
    )]
    #[OA\Response(
        response: 200,
        description: 'Wallet transfers retrieved successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorized',
        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
    )]
    #[OA\Response(
        response: 404,
        description: 'Wallet not found',
        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
    )]
    public function walletTransfers(string $walletId): JsonResponse
    {
        try {
            $paymentService = new PaymentService();

            $type = request()->query('type', 'all');
            $perPage = request()->query('per_page', 15);

            // Get wallet first to check existence
            $wallet = Wallet::findOrFail($walletId);

            // Get transfers using service
            $transfers = $paymentService->getWalletTransfers(
                walletId: (int) $walletId,
                type: $type,
                perPage: $perPage
            );

            // Get summary using service
            $summary = $paymentService->getTransferSummary((int) $walletId);

            return $this->successResponse(
                data: [
                    'transfers' => TransferResource::collection($transfers),
                    'wallet' => [
                        'id' => $wallet->id,
                        'user_id' => $wallet->user_id,
                        'user_name' => $wallet->user->name,
                        'balance' => (float) $wallet->balance,
                    ],
                    'pagination' => [
                        'total' => $transfers->total(),
                        'per_page' => $transfers->perPage(),
                        'current_page' => $transfers->currentPage(),
                        'last_page' => $transfers->lastPage(),
                        'from' => $transfers->firstItem(),
                        'to' => $transfers->lastItem(),
                    ],
                    'summary' => [
                        'total_transfers' => $summary['total_transfers'],
                        'incoming_count' => $summary['incoming_count'],
                        'outgoing_count' => $summary['outgoing_count'],
                    ]
                ],
                message: 'Wallet transfers retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse(
                message: 'Wallet not found',
                statusCode: 404
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: 'Failed to retrieve wallet transfers',
                statusCode: 500,
                errors: [$e->getMessage()]
            );
        }
    }
}
