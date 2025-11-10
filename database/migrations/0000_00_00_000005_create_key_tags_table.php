<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('key_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('key_id');
            $table->uuid('uuid')->unique();
            $table->timestamp('printed_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['uuid', 'is_active']);
            $table->index('key_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('key_tags');
    }
};

