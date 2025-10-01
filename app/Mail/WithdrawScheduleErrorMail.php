<?php

declare(strict_types=1);

namespace App\Mail;

use App\Model\AccountWithdraw;
use Symfony\Component\Mime\Email;

class WithdrawScheduleErrorMail extends AbstractMail
{
    public function __construct(
        private AccountWithdraw $withdraw,
        private string $accountName,
        private string $pixKey,
        private string $errorMessage
    ) {
    }

    public function build(string $toEmail): Email
    {
        return (new Email())
            ->from($this->getFromEmail())
            ->subject($this->getSubject())
            ->to($toEmail)
            ->html($this->renderTemplate());
    }

    protected function getSubject(): string
    {
        return 'Erro no Processamento de Saque Agendado - Hyperf Bank';
    }

    protected function getTemplateVariables(): array
    {
        $amount = number_format($this->withdraw->amount, 2, ',', '.');
        $scheduledTime = date('d/m/Y \à\s H:i', strtotime($this->withdraw->scheduled_for));

        return [
            'accountName' => $this->accountName,
            'amount' => $amount,
            'pixKey' => $this->pixKey,
            'scheduledTime' => $scheduledTime,
            'errorMessage' => $this->errorMessage,
            'withdrawId' => $this->withdraw->id,
        ];
    }
}
