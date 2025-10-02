<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_withdraw', function (Blueprint $table) {
            $table->string('id', 36)->primary(); // UUID
            $table->string('account_id', 36);
            $table->string('method', 10);
            $table->decimal('amount', 15, 2)->unsigned();
            $table->boolean('scheduled')->default(false);
            $table->dateTime('scheduled_for')->nullable();
            $table->boolean('done')->default(false);
            $table->boolean('error')->default(false);
            $table->string('error_reason')->nullable();
            $table->timestamps();

            $table->index('account_id');
            $table->foreign('account_id')
                ->references('id')
                ->on('account')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_withdraw');
    }
};
