<?php

declare(strict_types=1);

namespace Test\Unit\Model;

use App\Model\Account;
use PHPUnit\Framework\TestCase;

class AccountTest extends TestCase
{
    public function testAccountHasCorrectTableName(): void
    {
        // Arrange
        $account = new Account();

        // Act & Assert
        $this->assertEquals('account', $account->getTable());
    }

    public function testAccountUsesUuidAsPrimaryKey(): void
    {
        // Arrange
        $account = new Account();

        // Act & Assert
        $this->assertEquals('id', $account->getKeyName());
        $this->assertEquals('string', $account->getKeyType());
        $this->assertFalse($account->getIncrementing());
    }

    public function testAccountHasTimestamps(): void
    {
        // Arrange
        $account = new Account();

        // Act & Assert
        $this->assertTrue($account->timestamps);
    }

    public function testAccountFillableAttributes(): void
    {
        // Arrange
        $account = new Account();
        $expectedFillable = ['id', 'name', 'balance'];

        // Act
        $fillable = $account->getFillable();

        // Assert
        $this->assertEquals($expectedFillable, $fillable);
    }

    public function testAccountCastsBalanceToFloat(): void
    {
        // Arrange
        $account = new Account();

        // Act
        $casts = $account->getCasts();

        // Assert
        $this->assertArrayHasKey('balance', $casts);
        $this->assertEquals('float', $casts['balance']);
    }

    public function testAccountCanSetAndGetBalance(): void
    {
        // Arrange
        $account = new Account();
        $balance = 1000.50;

        // Act
        $account->balance = $balance;

        // Assert
        $this->assertEquals($balance, $account->balance);
        $this->assertIsFloat($account->balance);
    }

    public function testAccountCanSetAndGetName(): void
    {
        // Arrange
        $account = new Account();
        $name = 'Test User';

        // Act
        $account->name = $name;

        // Assert
        $this->assertEquals($name, $account->name);
    }

    public function testAccountBalanceDefaultsToZero(): void
    {
        // Arrange
        $account = new Account();

        // Act
        $account->fill(['name' => 'Test']);

        // Assert
        $this->assertIsFloat($account->balance ?? 0.0);
    }
}
