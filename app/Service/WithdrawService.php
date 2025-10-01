<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\InsufficientBalanceException;
use App\Mail\WithdrawConfirmationMail;
use App\Mail\WithdrawScheduleConfirmationMail;
use App\Mail\WithdrawScheduleErrorMail;
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
     * Create a withdraw for the given account.
     *
     * @throws InsufficientBalanceException
     */
    public function createWithdraw(Account $account, array $data): AccountWithdraw
    {
        $amount = $data['amount'];
        $method = $data['method'];
        $isScheduled = !empty($data['schedule']);

        // Only validate balance for immediate withdrawals
        if (!$isScheduled && $account->balance < $amount) {
            throw new InsufficientBalanceException(
                'Insufficient balance',
                $account->balance,
                $amount
            );
        }

        $withdrawId = Str::uuid()->toString();

        Db::beginTransaction();
        try {
            $withdraw = AccountWithdraw::create([
                'id' => $withdrawId,
                'account_id' => $account->id,
                'method' => $method,
                'amount' => $amount,
                'scheduled' => $isScheduled,
                'scheduled_for' => $data['schedule'] ?? null,
                'done' => false,
                'error' => false,
            ]);

            if ($method === AccountWithdraw::METHOD_PIX && isset($data['pix'])) {
                AccountWithdrawPix::create([
                    'account_withdraw_id' => $withdrawId,
                    'type' => $data['pix']['type'],
                    'key' => $data['pix']['key'],
                ]);
            }

            if (!$isScheduled) {
                $this->executeWithdraw($withdraw, $account);
            }

            Db::commit();

            $this->sendWithdrawEmail($withdraw, $account, $isScheduled);

            return $withdraw;
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function executeWithdraw(AccountWithdraw $withdraw, Account $account): void
    {
        if ($account->balance < $withdraw->amount) {
            throw new InsufficientBalanceException(
                'Insufficient balance',
                $account->balance,
                $withdraw->amount
            );
        }

        $account->balance -= $withdraw->amount;
        $account->save();

        $withdraw->done = true;
        $withdraw->save();
    }

    public function processScheduledWithdraws(): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $withdraws = AccountWithdraw::where('scheduled', true)
            ->where('done', false)
            ->where('error', false)
            ->where('scheduled_for', '<=', date('Y-m-d H:i:s'))
            ->get();

        foreach ($withdraws as $withdraw) {
            $this->processScheduledWithdraw($withdraw, $results);
        }

        return $results;
    }

    private function processScheduledWithdraw(AccountWithdraw $withdraw, array &$results): void
    {
        $account = null;

        Db::beginTransaction();
        try {
            $account = Account::findOrFail($withdraw->account_id);

            $this->executeWithdraw($withdraw, $account);

            Db::commit();

            ++$results['processed'];
        } catch (Throwable $e) {
            Db::rollBack();

            $withdraw->error = true;
            $withdraw->save();

            ++$results['failed'];
            $results['errors'][] = [
                'withdraw_id' => $withdraw->id,
                'error' => $e->getMessage(),
            ];

            $this->logger->error('Failed to process scheduled withdraw', [
                'withdraw_id' => $withdraw->id,
                'error' => $e->getMessage(),
            ]);

            // Send error notification email with user-friendly message
            $pixData = $withdraw->pix;
            if ($pixData && $account) {
                $userMessage = $this->getUserFriendlyErrorMessage($e);
                $this->sendErrorEmail($withdraw, $account, $pixData->key, $userMessage);
            }
            return;
        }

        $this->sendConfirmationEmail($withdraw, $account);
    }

    private function sendWithdrawEmail(
        AccountWithdraw $withdraw,
        Account $account,
        bool $isScheduled
    ): void {
        if ($isScheduled) {
            $this->sendScheduledEmail($withdraw, $account);
            return;
        }

        $this->sendConfirmationEmail($withdraw, $account);
    }

    private function sendScheduledEmail(
        AccountWithdraw $withdraw,
        Account $account
    ): void {
        $pixKey = null;
        try {
            $pixKey = $withdraw->pix->key;

            $mailBuilder = new WithdrawScheduleConfirmationMail(
                $withdraw,
                $account->name,
                $pixKey
            );
            $email = $mailBuilder->build($pixKey);
            $this->mailer->send($email);

            $this->logger->info('Withdraw scheduled email sent', [
                'withdraw_id' => $withdraw->id,
                'account_id' => $account->id,
                'pix_key' => $pixKey,
                'scheduled_for' => $withdraw->scheduled_for,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to send withdraw scheduled email', [
                'withdraw_id' => $withdraw->id,
                'account_id' => $account->id,
                'pix_key' => $pixKey ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function sendConfirmationEmail(
        AccountWithdraw $withdraw,
        Account $account
    ): void {
        $pixKey = null;
        try {
            $pixKey = $withdraw->pix->key;

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
                'pix_key' => $pixKey ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't fail the withdraw
        }
    }

    private function getUserFriendlyErrorMessage(Throwable $e): string
    {
        return match (true) {
            $e instanceof InsufficientBalanceException => 'Saldo insuficiente. Certifique-se de que sua conta possui saldo disponível para realizar o saque.',

            default => 'Não foi possível processar seu saque. Por favor, tente novamente mais tarde ou entre em contato com o suporte.'
        };
    }

    private function sendErrorEmail(
        AccountWithdraw $withdraw,
        Account $account,
        string $pixKey,
        string $errorMessage
    ): void {
        try {
            $mailBuilder = new WithdrawScheduleErrorMail(
                $withdraw,
                $account->name,
                $pixKey,
                $errorMessage
            );
            $email = $mailBuilder->build($pixKey);
            $this->mailer->send($email);

            $this->logger->info('Withdraw error email sent', [
                'withdraw_id' => $withdraw->id,
                'account_id' => $account->id,
                'pix_key' => $pixKey,
                'error' => $errorMessage,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to send withdraw error email', [
                'withdraw_id' => $withdraw->id,
                'account_id' => $account->id,
                'pix_key' => $pixKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't fail the withdraw processing
        }
    }
}
