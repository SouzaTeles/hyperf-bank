<?php

declare(strict_types=1);

namespace App\Mail;

use App\Model\AccountWithdraw;
use Symfony\Component\Mime\Email;

class WithdrawConfirmationMail extends AbstractMail
{
    public function __construct(
        private AccountWithdraw $withdraw,
        private string $accountName,
        private string $pixKey
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
        return 'Confirmação de Saque - Hyperf Bank';
    }

    protected function getTemplateVariables(): array
    {
        $amount = number_format($this->withdraw->amount, 2, ',', '.');
        $scheduled = $this->withdraw->scheduled ? 'Sim' : 'Não';
        $scheduledFor = $this->withdraw->scheduled_for 
            ? date('d/m/Y H:i', strtotime($this->withdraw->scheduled_for))
            : 'N/A';

        return [
            'amount' => $amount,
            'method' => $this->withdraw->method,
            'pixKey' => $this->pixKey,
            'scheduled' => $scheduled,
            'withdrawId' => $this->withdraw->id,
            'accountName' => $this->accountName,
            'scheduledFor' => $scheduledFor,
        ];
    }
}
