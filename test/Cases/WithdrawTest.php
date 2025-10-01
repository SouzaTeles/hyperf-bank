<?php

declare(strict_types=1);

namespace Test\Cases;

use App\Service\WithdrawService;
use Hyperf\Testing\TestCase;
use Hyperf\DbConnection\Db;
use Hyperf\Stringable\Str;

class WithdrawTest extends TestCase
{
    protected string $accountId;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->accountId = Str::uuid()->toString();
        Db::table('account')->insert([
            'id' => $this->accountId,
            'name' => 'Test Account',
            'balance' => 1000.00,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    protected function tearDown(): void
    {
        Db::table('account')->where('id', $this->accountId)->delete();
        parent::tearDown();
    }
    
    public function testWithdrawRequestAndGetSuccess()
    {
        $payload = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'fulano@email.com',
            ],
            'amount' => 150.75,
            'accountId' => $this->accountId,
            'schedule' => null,
        ];

        $response = $this->post("/account/{$this->accountId}/balance/withdraw", $payload);
        
        $response->assertStatus(200)
            ->assertJsonStructure(['account_id', 'withdraw_id', 'amount', 'new_balance'])
            ->assertJson(['account_id' => $this->accountId]);
    }
    
    public function testWithdrawRequestAndGetError()
    {
        $payload = [
            'method' => 'TED',
            'pix' => [
                'type' => 'email',
                'key' => 'fulano@email.com',
            ],
            'amount' => 150.75,
            'accountId' => $this->accountId,
            'schedule' => null,
        ];

        $response = $this->post("/account/{$this->accountId}/balance/withdraw", $payload);
        
        $response->assertStatus(400)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJson(['message' => 'Validation failed']);
    }
    
    public function testWithdrawWithInsufficientBalanceAndGetError()
    {
        // Conta tem saldo de 1000,00, tentando sacar 1500,00
        $payload = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'fulano@email.com',
            ],
            'amount' => 1500.00,
            'accountId' => $this->accountId,
            'schedule' => null,
        ];

        $response = $this->post("/account/{$this->accountId}/balance/withdraw", $payload);
        
        $response->assertStatus(400)
            ->assertJsonStructure(['message', 'balance', 'requested'])
            ->assertJsonFragment(['message' => 'Insufficient balance']);
        
        // Verificar que o saldo não foi alterado
        $account = Db::table('account')->where('id', $this->accountId)->first();
        $this->assertEquals(1000.00, $account->balance);
    }
    
    public function testWithdrawScheduledInPastAndGetError()
    {
        // Tentar agendar para uma data no passado
        $payload = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'fulano@email.com',
            ],
            'amount' => 100.00,
            'accountId' => $this->accountId,
            'schedule' => '2024-01-01 10:00', // Data no passado
        ];

        $response = $this->post("/account/{$this->accountId}/balance/withdraw", $payload);
        
        $response->assertStatus(400)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJson(['message' => 'Validation failed'])
            ->assertJsonFragment(['A data de agendamento deve ser no futuro.']);
        
        // Verificar que o saldo não foi alterado
        $account = Db::table('account')->where('id', $this->accountId)->first();
        $this->assertEquals(1000.00, $account->balance);
    }
    
    public function testWithdrawScheduledMoreThan7DaysAndGetError()
    {
        // Tentar agendar para mais de 7 dias no futuro
        $futureDate = date('Y-m-d H:i', strtotime('+8 days'));
        
        $payload = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'fulano@email.com',
            ],
            'amount' => 100.00,
            'accountId' => $this->accountId,
            'schedule' => $futureDate,
        ];

        $response = $this->post("/account/{$this->accountId}/balance/withdraw", $payload);
        
        $response->assertStatus(400)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJson(['message' => 'Validation failed'])
            ->assertJsonFragment(['A data de agendamento não pode ser maior que 7 dias.']);
        
        // Verificar que o saldo não foi alterado
        $account = Db::table('account')->where('id', $this->accountId)->first();
        $this->assertEquals(1000.00, $account->balance);
    }
    
    public function testWithdrawScheduledIn2DaysAndGetSuccess()
    {
        // Agendar para 2 dias no futuro (dentro da janela válida)
        $futureDate = date('Y-m-d H:i', strtotime('+2 days'));
        
        $payload = [
            'method' => 'PIX',
            'pix' => [
                'type' => 'email',
                'key' => 'fulano@email.com',
            ],
            'amount' => 100.00,
            'accountId' => $this->accountId,
            'schedule' => $futureDate,
        ];

        $response = $this->post("/account/{$this->accountId}/balance/withdraw", $payload);
        
        $response->assertStatus(200)
            ->assertJsonStructure(['account_id', 'withdraw_id', 'amount', 'new_balance'])
            ->assertJson(['account_id' => $this->accountId]);
        
        // Verificar que o saldo NÃO foi deduzido (apenas quando processar)
        $account = Db::table('account')->where('id', $this->accountId)->first();
        $this->assertEquals(1000.00, $account->balance);
    }

    public function testProcessScheduledWithdrawWithSufficientBalance()
    {
        // 1. Criar saque agendado no passado
        $pastDate = date('Y-m-d H:i:s', strtotime('-1 hour'));
        
        $withdrawId = Str::uuid()->toString();
        Db::table('account_withdraw')->insert([
            'id' => $withdrawId,
            'account_id' => $this->accountId,
            'method' => 'pix',
            'amount' => 100.00,
            'scheduled' => true,
            'scheduled_for' => $pastDate,
            'done' => false,
            'error' => false,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Db::table('account_withdraw_pix')->insert([
            'account_withdraw_id' => $withdrawId,
            'type' => 'email',
            'key' => 'teste@exemplo.com',
        ]);

        // 2. Processar saques agendados
        $withdrawService = $this->container->get(WithdrawService::class);
        $results = $withdrawService->processScheduledWithdraws();

        // 3. Verificar resultados
        $this->assertEquals(1, $results['processed']);
        $this->assertEquals(0, $results['failed']);

        // 4. Verificar que o saque foi marcado como done
        $withdraw = Db::table('account_withdraw')->where('id', $withdrawId)->first();
        $this->assertTrue((bool) $withdraw->done);
        $this->assertFalse((bool) $withdraw->error);

        // 5. Verificar que o saldo foi debitado
        $account = Db::table('account')->where('id', $this->accountId)->first();
        $this->assertEquals(900.00, $account->balance);

        // Cleanup
        Db::table('account_withdraw_pix')->where('account_withdraw_id', $withdrawId)->delete();
        Db::table('account_withdraw')->where('id', $withdrawId)->delete();
    }

    public function testProcessScheduledWithdrawWithInsufficientBalance()
    {
        // 1. Criar saque agendado no passado com valor maior que o saldo
        $pastDate = date('Y-m-d H:i:s', strtotime('-1 hour'));
        
        $withdrawId = Str::uuid()->toString();
        Db::table('account_withdraw')->insert([
            'id' => $withdrawId,
            'account_id' => $this->accountId,
            'method' => 'pix',
            'amount' => 1500.00, // Mais que o saldo de 1000
            'scheduled' => true,
            'scheduled_for' => $pastDate,
            'done' => false,
            'error' => false,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Db::table('account_withdraw_pix')->insert([
            'account_withdraw_id' => $withdrawId,
            'type' => 'email',
            'key' => 'teste@exemplo.com',
        ]);

        // 2. Processar saques agendados
        $withdrawService = $this->container->get(WithdrawService::class);
        $results = $withdrawService->processScheduledWithdraws();

        // 3. Verificar resultados
        $this->assertEquals(0, $results['processed']);
        $this->assertEquals(1, $results['failed']);
        $this->assertCount(1, $results['errors']);

        // 4. Verificar que o saque foi marcado como error
        $withdraw = Db::table('account_withdraw')->where('id', $withdrawId)->first();
        $this->assertFalse((bool) $withdraw->done);
        $this->assertTrue((bool) $withdraw->error);

        // 5. Verificar que o saldo NÃO foi debitado
        $account = Db::table('account')->where('id', $this->accountId)->first();
        $this->assertEquals(1000.00, $account->balance);

        // Cleanup
        Db::table('account_withdraw_pix')->where('account_withdraw_id', $withdrawId)->delete();
        Db::table('account_withdraw')->where('id', $withdrawId)->delete();
    }

    public function testProcessScheduledWithdrawsDoesNotProcessFuture()
    {
        // 1. Criar saque agendado no FUTURO
        $futureDate = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $withdrawId = Str::uuid()->toString();
        Db::table('account_withdraw')->insert([
            'id' => $withdrawId,
            'account_id' => $this->accountId,
            'method' => 'pix',
            'amount' => 100.00,
            'scheduled' => true,
            'scheduled_for' => $futureDate,
            'done' => false,
            'error' => false,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Db::table('account_withdraw_pix')->insert([
            'account_withdraw_id' => $withdrawId,
            'type' => 'email',
            'key' => 'teste@exemplo.com',
        ]);

        // 2. Processar saques agendados
        $withdrawService = $this->container->get(WithdrawService::class);
        $results = $withdrawService->processScheduledWithdraws();

        // 3. Verificar que NÃO foi processado
        $this->assertEquals(0, $results['processed']);
        $this->assertEquals(0, $results['failed']);

        // 4. Verificar que o saque NÃO foi marcado como done
        $withdraw = Db::table('account_withdraw')->where('id', $withdrawId)->first();
        $this->assertFalse((bool) $withdraw->done);
        $this->assertFalse((bool) $withdraw->error);

        // 5. Verificar que o saldo NÃO foi debitado
        $account = Db::table('account')->where('id', $this->accountId)->first();
        $this->assertEquals(1000.00, $account->balance);

        // Cleanup
        Db::table('account_withdraw_pix')->where('account_withdraw_id', $withdrawId)->delete();
        Db::table('account_withdraw')->where('id', $withdrawId)->delete();
    }

    public function testProcessMultipleScheduledWithdraws()
    {
        // 1. Criar 3 saques agendados no passado
        $pastDate = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $withdrawIds = [];

        for ($i = 0; $i < 3; $i++) {
            $withdrawId = Str::uuid()->toString();
            $withdrawIds[] = $withdrawId;

            Db::table('account_withdraw')->insert([
                'id' => $withdrawId,
                'account_id' => $this->accountId,
                'method' => 'pix',
                'amount' => 100.00,
                'scheduled' => true,
                'scheduled_for' => $pastDate,
                'done' => false,
                'error' => false,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            Db::table('account_withdraw_pix')->insert([
                'account_withdraw_id' => $withdrawId,
                'type' => 'email',
                'key' => "teste{$i}@exemplo.com",
            ]);
        }

        // 2. Processar saques agendados
        $withdrawService = $this->container->get(WithdrawService::class);
        $results = $withdrawService->processScheduledWithdraws();

        // 3. Verificar que os 3 foram processados
        $this->assertEquals(3, $results['processed']);
        $this->assertEquals(0, $results['failed']);

        // 4. Verificar que o saldo foi debitado corretamente (1000 - 300 = 700)
        $account = Db::table('account')->where('id', $this->accountId)->first();
        $this->assertEquals(700.00, $account->balance);

        // Cleanup
        foreach ($withdrawIds as $withdrawId) {
            Db::table('account_withdraw_pix')->where('account_withdraw_id', $withdrawId)->delete();
            Db::table('account_withdraw')->where('id', $withdrawId)->delete();
        }
    }

    public function testProcessScheduledWithdrawsIgnoresAlreadyProcessed()
    {
        // 1. Criar saque agendado JÁ PROCESSADO
        $pastDate = date('Y-m-d H:i:s', strtotime('-1 hour'));
        
        $withdrawId = Str::uuid()->toString();
        Db::table('account_withdraw')->insert([
            'id' => $withdrawId,
            'account_id' => $this->accountId,
            'method' => 'pix',
            'amount' => 100.00,
            'scheduled' => true,
            'scheduled_for' => $pastDate,
            'done' => true, // Já processado
            'error' => false,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Db::table('account_withdraw_pix')->insert([
            'account_withdraw_id' => $withdrawId,
            'type' => 'email',
            'key' => 'teste@exemplo.com',
        ]);

        // 2. Processar saques agendados
        $withdrawService = $this->container->get(WithdrawService::class);
        $results = $withdrawService->processScheduledWithdraws();

        // 3. Verificar que NÃO foi processado novamente
        $this->assertEquals(0, $results['processed']);
        $this->assertEquals(0, $results['failed']);

        // 4. Verificar que o saldo NÃO foi alterado
        $account = Db::table('account')->where('id', $this->accountId)->first();
        $this->assertEquals(1000.00, $account->balance);

        // Cleanup
        Db::table('account_withdraw_pix')->where('account_withdraw_id', $withdrawId)->delete();
        Db::table('account_withdraw')->where('id', $withdrawId)->delete();
    }
}