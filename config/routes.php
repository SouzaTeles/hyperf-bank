<?php

declare(strict_types=1);

use App\Controller\AccountController;
use Hyperf\HttpServer\Router\Router;

Router::post('/account/{accountId}/balance/withdraw', AccountController::class);
