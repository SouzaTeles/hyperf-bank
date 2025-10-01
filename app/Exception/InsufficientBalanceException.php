<?php

declare(strict_types=1);

namespace App\Exception;

use Hyperf\Server\Exception\ServerException;

class InsufficientBalanceException extends ServerException
{
    public function __construct(
        string $message = 'Insufficient balance',
        private float $balance = 0.0,
        private float $requested = 0.0
    ) {
        parent::__construct($message, 400);
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function getRequested(): float
    {
        return $this->requested;
    }
}
