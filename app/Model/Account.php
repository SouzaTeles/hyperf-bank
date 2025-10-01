<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property string $id
 * @property string $name
 * @property float $balance
 * @property string $created_at
 * @property string $updated_at
 */
class Account extends Model
{
    public bool $timestamps = true;

    public bool $incrementing = false;

    protected ?string $table = 'account';

    protected string $primaryKey = 'id';

    protected string $keyType = 'string';

    protected array $fillable = [
        'id',
        'name',
        'balance',
    ];

    protected array $casts = [
        'balance' => 'float',
    ];
}
