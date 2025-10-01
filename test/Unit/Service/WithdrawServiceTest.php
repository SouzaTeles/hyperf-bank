<?php

declare(strict_types=1);

namespace Test\Unit\Service;

use App\Exception\InsufficientBalanceException;
use App\Model\Account;
use App\Model\AccountWithdraw;
use App\Service\WithdrawService;
use Hyperf\Logger\LoggerFactory;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;

class WithdrawServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testExecuteWithdrawWithSufficientBalance(): void
    {
        // Arrange
        $mailer = Mockery::mock(MailerInterface::class);
        $loggerFactory = Mockery::mock(LoggerFactory::class);
        $logger = Mockery::mock(LoggerInterface::class);
        
        $loggerFactory->shouldReceive('get')
            ->with('withdraw')
            ->andReturn($logger);

        $service = new WithdrawService($mailer, $loggerFactory);

        /** @var Account|MockInterface $account */
        $account = Mockery::mock(Account::class)->makePartial();
        $account->balance = 1000.00;
        $account->shouldReceive('save')->once()->andReturn(true);
        $account->shouldReceive('setAttribute')->andReturnUsing(function($key, $value) use ($account) {
            $account->$key = $value;
            return $account;
        });

        /** @var AccountWithdraw|MockInterface $withdraw */
        $withdraw = Mockery::mock(AccountWithdraw::class)->makePartial();
        $withdraw->amount = 100.00;
        $withdraw->shouldReceive('save')->once()->andReturn(true);
        $withdraw->shouldReceive('setAttribute')->andReturnUsing(function($key, $value) use ($withdraw) {
            $withdraw->$key = $value;
            return $withdraw;
        });

        // Act
        $service->executeWithdraw($withdraw, $account);

        // Assert
        $this->assertEquals(900.00, $account->balance);
        $this->assertTrue($withdraw->done);
    }

    public function testExecuteWithdrawThrowsExceptionWithInsufficientBalance(): void
    {
        // Arrange
        $mailer = Mockery::mock(MailerInterface::class);
        $loggerFactory = Mockery::mock(LoggerFactory::class);
        $logger = Mockery::mock(LoggerInterface::class);
        
        $loggerFactory->shouldReceive('get')
            ->with('withdraw')
            ->andReturn($logger);

        $service = new WithdrawService($mailer, $loggerFactory);

        /** @var Account|MockInterface $account */
        $account = Mockery::mock(Account::class)->makePartial();
        $account->balance = 50.00;

        /** @var AccountWithdraw|MockInterface $withdraw */
        $withdraw = Mockery::mock(AccountWithdraw::class)->makePartial();
        $withdraw->amount = 100.00;

        // Expect exception
        $this->expectException(InsufficientBalanceException::class);
        $this->expectExceptionMessage('Insufficient balance');

        // Act
        $service->executeWithdraw($withdraw, $account);
    }

    public function testExecuteWithdrawDoesNotAllowNegativeBalance(): void
    {
        // Arrange
        $mailer = Mockery::mock(MailerInterface::class);
        $loggerFactory = Mockery::mock(LoggerFactory::class);
        $logger = Mockery::mock(LoggerInterface::class);
        
        $loggerFactory->shouldReceive('get')
            ->with('withdraw')
            ->andReturn($logger);

        $service = new WithdrawService($mailer, $loggerFactory);

        /** @var Account|MockInterface $account */
        $account = Mockery::mock(Account::class)->makePartial();
        $account->balance = 100.00;

        /** @var AccountWithdraw|MockInterface $withdraw */
        $withdraw = Mockery::mock(AccountWithdraw::class)->makePartial();
        $withdraw->amount = 100.01;

        // Expect exception
        $this->expectException(InsufficientBalanceException::class);

        // Act
        $service->executeWithdraw($withdraw, $account);
    }

    public function testExecuteWithdrawUpdatesWithdrawStatus(): void
    {
        // Arrange
        $mailer = Mockery::mock(MailerInterface::class);
        $loggerFactory = Mockery::mock(LoggerFactory::class);
        $logger = Mockery::mock(LoggerInterface::class);
        
        $loggerFactory->shouldReceive('get')
            ->with('withdraw')
            ->andReturn($logger);

        $service = new WithdrawService($mailer, $loggerFactory);

        /** @var Account|MockInterface $account */
        $account = Mockery::mock(Account::class)->makePartial();
        $account->balance = 500.00;
        $account->shouldReceive('save')->once()->andReturn(true);
        $account->shouldReceive('setAttribute')->andReturnUsing(function($key, $value) use ($account) {
            $account->$key = $value;
            return $account;
        });

        /** @var AccountWithdraw|MockInterface $withdraw */
        $withdraw = Mockery::mock(AccountWithdraw::class)->makePartial();
        $withdraw->amount = 250.00;
        $withdraw->done = false;
        $withdraw->shouldReceive('save')->once()->andReturn(true);
        $withdraw->shouldReceive('setAttribute')->andReturnUsing(function($key, $value) use ($withdraw) {
            $withdraw->$key = $value;
            return $withdraw;
        });

        // Act
        $service->executeWithdraw($withdraw, $account);

        // Assert
        $this->assertTrue($withdraw->done);
        $this->assertEquals(250.00, $account->balance);
    }

    public function testExecuteWithdrawWithExactBalance(): void
    {
        // Arrange
        $mailer = Mockery::mock(MailerInterface::class);
        $loggerFactory = Mockery::mock(LoggerFactory::class);
        $logger = Mockery::mock(LoggerInterface::class);
        
        $loggerFactory->shouldReceive('get')
            ->with('withdraw')
            ->andReturn($logger);

        $service = new WithdrawService($mailer, $loggerFactory);

        /** @var Account|MockInterface $account */
        $account = Mockery::mock(Account::class)->makePartial();
        $account->balance = 100.00;
        $account->shouldReceive('save')->once()->andReturn(true);
        $account->shouldReceive('setAttribute')->andReturnUsing(function($key, $value) use ($account) {
            $account->$key = $value;
            return $account;
        });

        /** @var AccountWithdraw|MockInterface $withdraw */
        $withdraw = Mockery::mock(AccountWithdraw::class)->makePartial();
        $withdraw->amount = 100.00;
        $withdraw->shouldReceive('save')->once()->andReturn(true);
        $withdraw->shouldReceive('setAttribute')->andReturnUsing(function($key, $value) use ($withdraw) {
            $withdraw->$key = $value;
            return $withdraw;
        });

        // Act
        $service->executeWithdraw($withdraw, $account);

        // Assert
        $this->assertEquals(0.00, $account->balance);
        $this->assertTrue($withdraw->done);
    }
}
