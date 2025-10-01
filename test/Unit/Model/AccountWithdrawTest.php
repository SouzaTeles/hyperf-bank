<?php

declare(strict_types=1);

namespace Test\Unit\Model;

use App\Model\AccountWithdraw;
use PHPUnit\Framework\TestCase;

class AccountWithdrawTest extends TestCase
{
    public function testAccountWithdrawHasCorrectTableName(): void
    {
        // Arrange
        $withdraw = new AccountWithdraw();

        // Act & Assert
        $this->assertEquals('account_withdraw', $withdraw->getTable());
    }

    public function testAccountWithdrawUsesUuidAsPrimaryKey(): void
    {
        // Arrange
        $withdraw = new AccountWithdraw();

        // Act & Assert
        $this->assertEquals('id', $withdraw->getKeyName());
        $this->assertEquals('string', $withdraw->getKeyType());
        $this->assertFalse($withdraw->getIncrementing());
    }

    public function testAccountWithdrawHasTimestamps(): void
    {
        // Arrange
        $withdraw = new AccountWithdraw();

        // Act & Assert
        $this->assertTrue($withdraw->timestamps);
    }

    public function testAccountWithdrawFillableAttributes(): void
    {
        // Arrange
        $withdraw = new AccountWithdraw();
        $expectedFillable = [
            'id',
            'account_id',
            'method',
            'amount',
            'scheduled',
            'scheduled_for',
            'done',
            'error',
            'error_reason',
        ];

        // Act
        $fillable = $withdraw->getFillable();

        // Assert
        $this->assertEquals($expectedFillable, $fillable);
    }

    public function testAccountWithdrawCasts(): void
    {
        // Arrange
        $withdraw = new AccountWithdraw();

        // Act
        $casts = $withdraw->getCasts();

        // Assert
        $this->assertArrayHasKey('amount', $casts);
        $this->assertArrayHasKey('scheduled', $casts);
        $this->assertArrayHasKey('done', $casts);
        $this->assertArrayHasKey('error', $casts);

        $this->assertEquals('float', $casts['amount']);
        $this->assertEquals('boolean', $casts['scheduled']);
        $this->assertEquals('boolean', $casts['done']);
        $this->assertEquals('boolean', $casts['error']);
    }

    public function testAccountWithdrawHasPixMethodConstant(): void
    {
        // Act & Assert
        $this->assertEquals('PIX', AccountWithdraw::METHOD_PIX);
    }

    public function testAccountWithdrawCanSetBooleanFields(): void
    {
        // Arrange
        $withdraw = new AccountWithdraw();

        // Act
        $withdraw->scheduled = true;
        $withdraw->done = false;
        $withdraw->error = false;

        // Assert
        $this->assertTrue($withdraw->scheduled);
        $this->assertFalse($withdraw->done);
        $this->assertFalse($withdraw->error);
        $this->assertIsBool($withdraw->scheduled);
        $this->assertIsBool($withdraw->done);
        $this->assertIsBool($withdraw->error);
    }

    public function testAccountWithdrawCanSetAmount(): void
    {
        // Arrange
        $withdraw = new AccountWithdraw();
        $amount = 250.75;

        // Act
        $withdraw->amount = $amount;

        // Assert
        $this->assertEquals($amount, $withdraw->amount);
        $this->assertIsFloat($withdraw->amount);
    }

    public function testAccountWithdrawCanSetMethod(): void
    {
        // Arrange
        $withdraw = new AccountWithdraw();

        // Act
        $withdraw->method = AccountWithdraw::METHOD_PIX;

        // Assert
        $this->assertEquals('PIX', $withdraw->method);
    }

    public function testAccountWithdrawCanSetScheduledFor(): void
    {
        // Arrange
        $withdraw = new AccountWithdraw();
        $scheduledFor = '2025-12-31 15:00:00';

        // Act
        $withdraw->scheduled_for = $scheduledFor;

        // Assert
        $this->assertEquals($scheduledFor, $withdraw->scheduled_for);
    }

    public function testAccountWithdrawCanSetErrorReason(): void
    {
        // Arrange
        $withdraw = new AccountWithdraw();
        $errorReason = 'Insufficient balance';

        // Act
        $withdraw->error_reason = $errorReason;

        // Assert
        $this->assertEquals($errorReason, $withdraw->error_reason);
    }
}
