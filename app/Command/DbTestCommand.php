<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use Throwable;

#[Command]
class DbTestCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('db:test');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Test database connection and list tables');
    }

    public function handle()
    {
        $this->line('Testing database connection...', 'info');

        try {
            // Test connection
            $result = Db::select('SELECT 1 as test');
            $this->line('✓ Connection successful!', 'info');
            $this->line('Result: ' . json_encode($result), 'comment');

            // Get current database
            $database = Db::select('SELECT DATABASE() as db');
            $this->line('✓ Current database: ' . $database[0]->db, 'info');

            // List tables
            $tables = Db::select('SHOW TABLES');
            $this->line('✓ Tables in database:', 'info');
            
            if (empty($tables)) {
                $this->line('  (no tables yet - run migrations)', 'comment');
            } else {
                foreach ($tables as $table) {
                    $tableName = array_values((array) $table)[0];
                    $this->line("  - {$tableName}", 'comment');
                }
            }

            return 0;
        } catch (Throwable $e) {
            $this->error('✗ Database connection failed!');
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
