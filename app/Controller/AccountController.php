<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\InsufficientBalanceException;
use App\Model\Account;
use App\Request\WithdrawRequest;
use App\Service\WithdrawService;
use Hyperf\HttpServer\Contract\ResponseInterface;

class AccountController extends AbstractController
{
    public function __construct(
        protected ResponseInterface $response,
        protected WithdrawService $withdrawService
    ) {
    }

    public function __invoke(string $accountId, WithdrawRequest $request)
    {
        $validated = $request->validated();
        
        $account = Account::find($accountId);
        if (!$account) {
            return $this->response->json([
                'message' => 'Conta não encontrada',
            ])->withStatus(404);
        }
        
        try {
            $withdraw = $this->withdrawService->createWithdraw($account, $validated);
            
            return $this->response->json([
                'amount' => $withdraw->amount,
                'account_id' => $account->id,
                'withdraw_id' => $withdraw->id,
                'new_balance' => $account->balance,
            ])->withStatus(200);
            
        } catch (InsufficientBalanceException $e) {
            return $this->response->json([
                'message' => $e->getMessage(),
                'balance' => $e->getBalance(),
                'requested' => $e->getRequested(),
            ])->withStatus(400);
        }
    }
}
