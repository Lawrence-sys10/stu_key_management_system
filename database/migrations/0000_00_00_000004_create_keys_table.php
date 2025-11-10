<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('keys', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->enum('key_type', ['physical', 'electronic', 'master', 'duplicate'])->default('physical');
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['available', 'checked_out', 'lost', 'maintenance'])->default('available');
            $table->unsignedBigInteger('last_log_id')->nullable(); // Remove foreign key constraint
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['code', 'status']);
            $table->index('location_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('keys');
    }
};

