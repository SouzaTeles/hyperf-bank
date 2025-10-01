<?php

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class WithdrawRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'method' => 'required|string|in:PIX',
            'pix' => 'required_if:method,PIX|array',
            'pix.type' => 'required|in:email',
            'pix.key' => 'required|email',
            'amount' => 'required|numeric|min:1',
            'schedule' => 'nullable|date_format:Y-m-d H:i',
        ];
    }

    public function messages(): array
    {
        return [
            'method.in' => 'O método deve ser PIX.',
            'method.required' => 'O método é obrigatório.',
            'pix.required' => 'A chave PIX é obrigatória.',
            'pix.type.in' => 'Apenas chaves PIX do tipo email são aceitas no momento.',
            'pix.key.email' => 'A chave PIX deve ser um email válido.',
            'amount.required' => 'O valor é obrigatório.',
            'amount.numeric' => 'O valor deve ser numérico.',
            'amount.min' => 'O valor mínimo é 1.',
            'accountId.required' => 'O ID da conta é obrigatório.',
            'accountId.integer' => 'O ID da conta deve ser inteiro.',
            'accountId.exists' => 'Conta não encontrada.',
            'schedule.date_format' => 'O agendamento deve estar no formato YYYY-MM-DD HH:MM.',
        ];
    }
}
