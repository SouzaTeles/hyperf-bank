<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\InsufficientBalanceException;
use App\Mail\WithdrawConfirmationMail;
use App\Model\Account;
use App\Model\AccountWithdraw;
use App\Model\AccountWithdrawPix;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Stringable\Str;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Throwable;

class WithdrawService
{
    private LoggerInterface $logger;

    public function __construct(
        private MailerInterface $mailer,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get('withdraw');
    }
    /**
     * Create a withdraw for the given account
     * 
     * @param Account $account
     * @param array $data
     * @return AccountWithdraw
     * @throws InsufficientBalanceException
     */
    public function createWithdraw(Account $account, array $data): AccountWithdraw
    {
        $amount = $data['amount'];
        $method = $data['method'];

        // Validate balance
        if ($account->balance < $amount) {
            throw new InsufficientBalanceException(
                'Insufficient balance',
                $account->balance,
                $amount
            );
        }

        $withdrawId = Str::uuid()->toString();

        Db::beginTransaction();
        try {
            // Create withdraw record
            $withdraw = AccountWithdraw::create([
                'id' => $withdrawId,
                'account_id' => $account->id,
                'method' => $method,
                'amount' => $amount,
                'scheduled' => !empty($data['schedule']),
                'scheduled_for' => $data['schedule'] ?? null,
                'done' => false,
                'error' => false,
            ]);

            // Create PIX data if method is PIX
            if ($method === AccountWithdraw::METHOD_PIX && isset($data['pix'])) {
                AccountWithdrawPix::create([
                    'account_withdraw_id' => $withdrawId,
                    'type' => $data['pix']['type'],
                    'key' => $data['pix']['key'],
                ]);
            }

            // Deduct balance from account
            $account->balance -= $amount;
            $account->save();

            Db::commit();

            // Send confirmation email if PIX
            if ($method === AccountWithdraw::METHOD_PIX && isset($data['pix'])) {
                $this->sendConfirmationEmail($withdraw, $account, $data['pix']['key']);
            }

            return $withdraw;

        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * Send confirmation email to PIX key
     */
    private function sendConfirmationEmail(
        AccountWithdraw $withdraw,
        Account $account,
        string $pixKey
    ): void {
        try {
            $mailBuilder = new WithdrawConfirmationMail(
                $withdraw,
                $account->name,
                $pixKey
            );
            $email = $mailBuilder->build($pixKey);
            $this->mailer->send($email);
            
            $this->logger->info('Withdraw confirmation email sent', [
                'withdraw_id' => $withdraw->id,
                'account_id' => $account->id,
                'pix_key' => $pixKey,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to send withdraw confirmation email', [
                'withdraw_id' => $withdraw->id,
                'account_id' => $account->id,
                'pix_key' => $pixKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't fail the withdraw
        }
    }
}
