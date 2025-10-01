<?php

declare(strict_types=1);

use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $accounts = [
            [
                'id' => '550e8400-e29b-41d4-a716-446655440001',
                'name' => 'João Silva',
                'balance' => 1000.00,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440002',
                'name' => 'Maria Santos',
                'balance' => 2500.50,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440003',
                'name' => 'Pedro Costa',
                'balance' => 500.00,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($accounts as $account) {
            Db::table('account')->insert($account);
        }

        echo 'Seeded ' . count($accounts) . ' accounts.' . PHP_EOL;
    }
}
