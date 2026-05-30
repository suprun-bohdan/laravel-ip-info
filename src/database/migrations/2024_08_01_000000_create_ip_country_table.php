<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ip_country')) {
            return;
        }

        Schema::create('ip_country', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('first_ip');
            $table->bigInteger('last_ip');
            $table->string('country', 8);
            $table->timestamps();

            $table->index(['first_ip', 'last_ip']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_country');
    }
};
