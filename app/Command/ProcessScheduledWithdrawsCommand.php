<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\WithdrawService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;

#[Command]
class ProcessScheduledWithdrawsCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('withdraw:process-scheduled');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Processa saques agendados pendentes');
    }

    public function handle()
    {
        $this->line('Processando saques agendados...', 'info');

        $withdrawService = $this->container->get(WithdrawService::class);
        $results = $withdrawService->processScheduledWithdraws();

        if ($results['processed'] === 0 && $results['failed'] === 0) {
            $this->line('Sem saques agendados para processar.', 'comment');
            return 0;
        }

        if ($results['processed'] > 0) {
            $this->line("Processados: {$results['processed']}", 'info');
        }

        if ($results['failed'] > 0) {
            $this->error("Falha: {$results['failed']}");
            foreach ($results['errors'] as $error) {
                $this->error("  - {$error['withdraw_id']}: {$error['error']}");
            }
        }

        return $results['failed'] > 0 ? 1 : 0;
    }
    
}
