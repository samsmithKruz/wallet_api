<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateWalletRequest;
use App\Http\Requests\FundWalletRequest;
use App\Http\Requests\WithdrawWalletRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\WalletResource;
use App\Models\Wallet;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as FacadesLog;
use OpenApi\Attributes as OA;
use App\Services\WalletService;

#[OA\Tag(name: 'Wallets', description: 'Wallet management endpoints')]
class WalletController extends Controller
{
    //
    use ApiResponses;

    #[OA\Post(
        path: '/api/v1/wallets',
        operationId: 'createWallet',
        summary: 'Create a new wallet for a user',
        description: 'Initialize a wallet for a user. A user may only have one wallet.',
        tags: ['Wallets'],
        security: [['tokenAuth' => []]]
    )]
    #[OA\RequestBody(
        description: 'Wallet creation data',
        required: true,
        content: new OA\JsonContent(
            required: ['user_id'],
            properties: [
                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                new OA\Property(
                    property: 'initial_balance',
                    type: 'number',
                    format: 'float',
                    example: 1000.00,
                    nullable: true
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Wallet created successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')
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
    #[OA\Response(
        response: 409,
        description: 'Conflict - User already has a wallet',
        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
    )]
    public function store(CreateWalletRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $wallet = Wallet::create([
                'user_id' => $request->user_id,
                'balance' => $request->initial_balance ?? 0.00,
            ]);

            DB::commit();

            return $this->successResponse(
                data: $wallet->load('user'),
                message: 'Wallet created successfully',
                statusCode: 201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            FacadesLog::error('Error creating wallet: ' . $e->getMessage());

            return $this->successResponse(
                data: null,
                message: 'Failed to create wallet',
                statusCode: 500
            );
        }
    }

    #[OA\Get(
        path: '/api/v1/wallets/{id}',
        operationId: 'getWallet',
        summary: 'Get wallet details and balance',
        description: 'Fetch wallet details including balance and transaction summary (credit, debit totals).',
        tags: ['Wallets'],
        security: [['tokenAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Wallet ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Wallet details retrieved successfully',
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
    public function show(string $id): JsonResponse
    {
        try {
            $wallet = Wallet::with(['user', 'transactions' => function ($query) {
                $query->latest()->limit(10); // Get latest 10 transactions
            }])->findOrFail($id);

            return $this->successResponse(
                data: new WalletResource($wallet),
                message: 'Wallet details retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse(
                message: 'Wallet not found',
                statusCode: 404
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: 'Failed to retrieve wallet details',
                statusCode: 500,
                errors: [$e->getMessage()]
            );
        }
    }

    #[OA\Post(
        path: '/api/v1/wallets/{id}/fund',
        operationId: 'fundWallet',
        summary: 'Add funds to wallet',
        description: 'Fund wallet by adding credit transaction. Creates a wallet transaction of type credit.',
        tags: ['Wallets'],
        security: [['tokenAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Wallet ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        description: 'Fund wallet data',
        required: true,
        content: new OA\JsonContent(
            required: ['amount'],
            properties: [
                new OA\Property(
                    property: 'amount',
                    type: 'number',
                    format: 'float',
                    example: 500.00,
                    minimum: 1
                ),
                new OA\Property(
                    property: 'description',
                    type: 'string',
                    example: 'Bank transfer',
                    nullable: true
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Wallet funded successfully',
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
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
    )]
    public function fund(FundWalletRequest $request, string $id): JsonResponse
    {
        try {
            $walletService = new WalletService();
            $result = $walletService->fund(
                walletId: (int) $id,
                amount: $request->amount,
                description: $request->description
            );

            return $this->successResponse(
                data: [
                    'transaction' => new TransactionResource($result['transaction']),
                    'new_balance' => $result['new_balance'],
                ],
                message: 'Wallet funded successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse(
                message: 'Wallet not found',
                statusCode: 404
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

    #[OA\Post(
        path: '/api/v1/wallets/{id}/withdraw',
        operationId: 'withdrawFromWallet',
        summary: 'Withdraw funds from wallet',
        description: 'Withdraw funds from wallet. Creates a wallet transaction of type debit. Must enforce balance > withdrawal amount.',
        tags: ['Wallets'],
        security: [['tokenAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Wallet ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        description: 'Withdraw wallet data',
        required: true,
        content: new OA\JsonContent(
            required: ['amount'],
            properties: [
                new OA\Property(
                    property: 'amount',
                    type: 'number',
                    format: 'float',
                    example: 200.00,
                    minimum: 1
                ),
                new OA\Property(
                    property: 'description',
                    type: 'string',
                    example: 'Cash withdrawal',
                    nullable: true
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Withdrawal successful',
        content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')
    )]
    #[OA\Response(
        response: 400,
        description: 'Insufficient balance',
        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
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
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
    )]
    public function withdraw(WithdrawWalletRequest $request, string $id): JsonResponse
    {
        try {
            $walletService = new WalletService();
            $result = $walletService->withdraw(
                walletId: (int) $id,
                amount: $request->amount,
                description: $request->description
            );

            return $this->successResponse(
                data: [
                    'transaction' => new TransactionResource($result['transaction']),
                    'new_balance' => $result['new_balance'],
                ],
                message: 'Withdrawal successful'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse(
                message: 'Wallet not found',
                statusCode: 404
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

    #[OA\Delete(
        path: '/api/v1/wallets/{id}',
        operationId: 'deleteWallet',
        summary: 'Delete a wallet',
        description: 'Delete a wallet. Only allowed if wallet balance is zero.',
        tags: ['Wallets'],
        security: [['tokenAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Wallet ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Wallet deleted successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')
    )]
    #[OA\Response(
        response: 400,
        description: 'Wallet balance is not zero',
        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
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
    public function destroy(string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Find the wallet
            $wallet = Wallet::findOrFail($id);

            // Check if wallet balance is zero
            if (!$wallet->canBeDeleted()) {
                throw new \Exception('Cannot delete wallet with non-zero balance');
            }

            // Delete the wallet (transactions will be cascade deleted via foreign key)
            $wallet->delete();

            DB::commit();

            return $this->successResponse(
                data: null,
                message: 'Wallet deleted successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return $this->errorResponse(
                message: 'Wallet not found',
                statusCode: 404
            );
        } catch (\Exception $e) {
            DB::rollBack();

            $statusCode = $e->getMessage() === 'Cannot delete wallet with non-zero balance' ? 400 : 500;

            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: $statusCode,
                errors: [$e->getMessage()]
            );
        }
    }
}
