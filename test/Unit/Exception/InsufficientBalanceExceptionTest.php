<?php

declare(strict_types=1);

namespace Test\Unit\Exception;

use App\Exception\InsufficientBalanceException;
use PHPUnit\Framework\TestCase;

class InsufficientBalanceExceptionTest extends TestCase
{
    public function testExceptionStoresBalanceAndRequested(): void
    {
        // Arrange
        $currentBalance = 100.50;
        $requestedAmount = 200.75;
        $message = 'Insufficient balance';

        // Act
        $exception = new InsufficientBalanceException($message, $currentBalance, $requestedAmount);

        // Assert
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($currentBalance, $exception->getBalance());
        $this->assertEquals($requestedAmount, $exception->getRequested());
    }

    public function testExceptionWithZeroBalance(): void
    {
        // Arrange
        $currentBalance = 0.00;
        $requestedAmount = 50.00;

        // Act
        $exception = new InsufficientBalanceException('No balance', $currentBalance, $requestedAmount);

        // Assert
        $this->assertEquals(0.00, $exception->getBalance());
        $this->assertEquals(50.00, $exception->getRequested());
    }

    public function testExceptionWithLargeAmounts(): void
    {
        // Arrange
        $currentBalance = 999999.99;
        $requestedAmount = 1000000.00;

        // Act
        $exception = new InsufficientBalanceException(
            'Insufficient balance',
            $currentBalance,
            $requestedAmount
        );

        // Assert
        $this->assertEquals($currentBalance, $exception->getBalance());
        $this->assertEquals($requestedAmount, $exception->getRequested());
        $this->assertGreaterThan($currentBalance, $requestedAmount);
    }

    public function testExceptionMessageCanBeCustomized(): void
    {
        // Arrange
        $customMessage = 'Custom error message for insufficient balance';

        // Act
        $exception = new InsufficientBalanceException($customMessage, 100.00, 200.00);

        // Assert
        $this->assertEquals($customMessage, $exception->getMessage());
    }

    public function testExceptionIsThrowable(): void
    {
        // Arrange & Assert
        $this->expectException(InsufficientBalanceException::class);
        $this->expectExceptionMessage('Test exception');

        // Act
        throw new InsufficientBalanceException('Test exception', 50.00, 100.00);
    }
}
