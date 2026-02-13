<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Books API',
    version: '1.0.0',
    description: 'API'
)]

#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Local server'
)]

// #[OA\SecurityScheme(
//     securityScheme: "bearerAuth",
//     type: "http",
//     scheme: "bearer",
//     bearerFormat: "JWT"
// )]


class OpenApi
{
    //
}
