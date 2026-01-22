<?php

namespace App\Http\Controllers\V1;

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
        new OA\Server(url: 'http://localhost:8000/api/v1', description: 'Local Development Server'),
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


class WelcomeController extends Controller
{
    //
    #[OA\Get(
        path: '/api/v1/',
        operationId: 'getApiWelcome',
        tags: ['Welcome'],
        summary: 'API Welcome',
        description: 'Welcome to Wallet API v1',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function index()
    {
        return response()->json([
            'status_code' => 200,
            'message' => 'Wallet API v1',
            'data' => [
                'version' => '1.0.0',
                'documentation' => url('api/v1/documentation')
            ],
            'errors' => null
        ]);
    }
}
