<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hr_staff', function (Blueprint $table) {
            $table->id();
            $table->string('staff_id')->unique();
            $table->string('name');
            $table->string('phone');
            $table->string('dept')->nullable();
            $table->string('email')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('synced_at')->nullable();
            $table->enum('source', ['csv', 'api'])->default('csv');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['staff_id', 'status']);
            $table->index('phone');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hr_staff');
    }
};
