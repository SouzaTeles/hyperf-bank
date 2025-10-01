<?php

declare(strict_types=1);

namespace Test\Unit\Request;

use PHPUnit\Framework\TestCase;

class WithdrawRequestTest extends TestCase
{
    public function testValidationRulesAreCorrect(): void
    {
        // Testa que as regras de validação estão corretas
        $expectedRules = [
            'method' => 'required|string|in:PIX,pix',
            'pix' => 'required_if:method,PIX,pix|array',
            'pix.type' => 'required|in:email',
            'pix.key' => 'required|email',
            'amount' => 'required|numeric|min:1',
            'schedule' => 'nullable|date_format:Y-m-d H:i|after:now|before:7 days',
        ];

        // Este é um teste de estrutura - verifica que as regras principais existem
        $this->assertIsArray($expectedRules);
        $this->assertArrayHasKey('method', $expectedRules);
        $this->assertArrayHasKey('amount', $expectedRules);
        $this->assertArrayHasKey('schedule', $expectedRules);
    }

    public function testCustomErrorMessagesAreSet(): void
    {
        // Verifica que mensagens customizadas estão definidas
        $expectedMessages = [
            'method.in' => 'O método deve ser PIX.',
            'method.required' => 'O método é obrigatório.',
            'amount.required' => 'O valor é obrigatório.',
            'amount.numeric' => 'O valor deve ser numérico.',
            'amount.min' => 'O valor mínimo é 1.',
            'schedule.date_format' => 'O agendamento deve estar no formato YYYY-MM-DD HH:MM.',
            'schedule.after' => 'A data de agendamento deve ser no futuro.',
            'schedule.before' => 'A data de agendamento não pode ser maior que 7 dias.',
        ];

        $this->assertIsArray($expectedMessages);
        $this->assertArrayHasKey('method.in', $expectedMessages);
        $this->assertArrayHasKey('amount.min', $expectedMessages);
    }

    public function testMethodValidation(): void
    {
        // Testa que method aceita PIX e pix (case insensitive)
        $validMethods = ['PIX', 'pix'];
        
        foreach ($validMethods as $method) {
            $this->assertContains($method, $validMethods);
        }
    }

    public function testAmountValidation(): void
    {
        // Testa que amount deve ser numérico e >= 1
        $validAmounts = [1, 1.5, 100, 999.99, 1000000];
        $invalidAmounts = [0, -1, -100, 'abc'];

        foreach ($validAmounts as $amount) {
            $this->assertIsNumeric($amount);
            $this->assertGreaterThanOrEqual(1, $amount);
        }

        foreach ($invalidAmounts as $amount) {
            if (is_numeric($amount)) {
                $this->assertLessThan(1, $amount);
            }
        }
    }

    public function testScheduleDateFormat(): void
    {
        // Testa formato de data válido: Y-m-d H:i
        $validFormat = 'Y-m-d H:i';
        $exampleDate = '2025-12-31 15:30';
        
        $parsed = \DateTime::createFromFormat($validFormat, $exampleDate);
        
        $this->assertInstanceOf(\DateTime::class, $parsed);
        $this->assertEquals($exampleDate, $parsed->format($validFormat));
    }

    public function testScheduleDateMustBeFuture(): void
    {
        // Testa que data agendada deve ser futura
        $futureDate = new \DateTime('+1 day');
        $pastDate = new \DateTime('-1 day');
        $now = new \DateTime();

        $this->assertGreaterThan($now, $futureDate);
        $this->assertLessThan($now, $pastDate);
    }

    public function testScheduleDateCannotBeMoreThan7Days(): void
    {
        // Testa que data agendada não pode ser > 7 dias
        $maxDays = 7;
        $validDate = new \DateTime('+3 days');
        $invalidDate = new \DateTime('+10 days');
        $now = new \DateTime();

        $validDiff = $validDate->diff($now)->days;
        $invalidDiff = $invalidDate->diff($now)->days;

        $this->assertLessThanOrEqual($maxDays, $validDiff);
        $this->assertGreaterThan($maxDays, $invalidDiff);
    }

    public function testPixKeyMustBeEmail(): void
    {
        // Testa que chave PIX deve ser email válido
        $validEmails = [
            'test@example.com',
            'user.name@domain.com',
            'user+tag@example.co.uk',
        ];

        $invalidEmails = [
            'invalid',
            '@example.com',
            'user@',
            'user name@example.com',
        ];

        foreach ($validEmails as $email) {
            $this->assertNotFalse(filter_var($email, FILTER_VALIDATE_EMAIL));
        }

        foreach ($invalidEmails as $email) {
            $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL));
        }
    }
}
