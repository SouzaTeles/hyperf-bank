<?php

declare(strict_types=1);

use App\Controller\WithdrawController;
use Hyperf\HttpServer\Router\Router;

Router::post('/account/{accountId}/withdraw', WithdrawController::class);
