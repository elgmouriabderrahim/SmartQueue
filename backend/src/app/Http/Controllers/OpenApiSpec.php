<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'SmartQueue API',
    description: 'Smart Appointment & Queue Management System API'
)]
#[OA\Server(url: '/api', description: 'API Base URL')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'token'
)]
#[OA\Tag(name: 'Auth')]
#[OA\Tag(name: 'Appointments')]
#[OA\Tag(name: 'Queue')]
#[OA\Tag(name: 'Messaging')]
#[OA\Tag(name: 'Ratings')]
class OpenApiSpec
{
    // Container class for OpenAPI global metadata.
}
