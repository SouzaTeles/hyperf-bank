<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property string $account_withdraw_id
 * @property string $type
 * @property string $key
 */
class AccountWithdrawPix extends Model
{
    public const TYPE_EMAIL = 'email';

    public bool $timestamps = false;

    public bool $incrementing = false;

    protected ?string $table = 'account_withdraw_pix';

    protected string $primaryKey = 'account_withdraw_id';

    protected string $keyType = 'string';

    protected array $fillable = [
        'account_withdraw_id',
        'type',
        'key',
    ];

    public function withdraw()
    {
        return $this->belongsTo(AccountWithdraw::class, 'account_withdraw_id', 'id');
    }
}
