<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('security_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['start_at', 'end_at']);
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('security_shifts');
    }
};
