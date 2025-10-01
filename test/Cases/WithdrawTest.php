<?php

declare(strict_types=1);

namespace Test\Cases;

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
        $response->assertStatus(200);
        
        $data = json_decode($response->getBody()->getContents(), true);
        
        $this->assertArrayHasKey('account_id', $data);
        $this->assertEquals($this->accountId, $data['account_id']);
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
        $response->assertStatus(400);
        
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertArrayHasKey('errors', $data);
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
        $response->assertStatus(400);
        
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('Insufficient balance', $data['message']);
        
        // Verificar que o saldo não foi alterado
        $account = Db::table('account')->where('id', $this->accountId)->first();
        $this->assertEquals(1000.00, $account->balance);
    }
}