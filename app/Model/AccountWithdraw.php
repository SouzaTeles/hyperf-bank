<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property string $id
 * @property string $account_id
 * @property string $method
 * @property float $amount
 * @property bool $scheduled
 * @property string|null $scheduled_for
 * @property bool $done
 * @property bool $error
 * @property string|null $error_reason
 * @property string $created_at
 * @property string $updated_at
 */
class AccountWithdraw extends Model
{
    public const METHOD_PIX = 'PIX';

    protected ?string $table = 'account_withdraw';

    public bool $timestamps = true;

    protected string $primaryKey = 'id';

    public bool $incrementing = false;

    protected string $keyType = 'string';

    protected array $fillable = [
        'id',
        'account_id',
        'method',
        'amount',
        'scheduled',
        'scheduled_for',
        'done',
        'error',
        'error_reason',
    ];

    protected array $casts = [
        'amount' => 'float',
        'scheduled' => 'boolean',
        'done' => 'boolean',
        'error' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    public function pix()
    {
        return $this->hasOne(AccountWithdrawPix::class, 'account_withdraw_id', 'id');
    }
}
