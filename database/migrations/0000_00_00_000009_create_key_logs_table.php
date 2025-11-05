<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('key_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('key_id');
            $table->enum('action', ['checkout', 'checkin']);
            
            // Polymorphic holder relationship
            $table->string('holder_type'); // hr, perm_manual, temp
            $table->unsignedBigInteger('holder_id');
            $table->string('holder_name');
            $table->string('holder_phone');
            
            // Security officer who processed
            $table->unsignedBigInteger('receiver_user_id');
            $table->string('receiver_name'); // Snapshot
            
            $table->timestamp('expected_return_at')->nullable();
            $table->unsignedBigInteger('returned_from_log_id')->nullable();
            
            // Evidence
            $table->string('signature_path')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            
            // Verification flags
            $table->boolean('verified')->default(false);
            $table->boolean('discrepancy')->default(false);
            $table->text('discrepancy_reason')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['key_id', 'created_at']);
            $table->index(['holder_type', 'holder_id']);
            $table->index(['action', 'created_at']);
            $table->index('receiver_user_id');
            $table->index('verified');
            $table->index('discrepancy');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('key_logs');
    }
};

