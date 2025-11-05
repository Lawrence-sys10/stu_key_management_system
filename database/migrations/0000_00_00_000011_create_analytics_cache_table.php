<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('analytics_cache', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('hour_bucket'); // 0-23
            $table->integer('total_checkouts')->default(0);
            $table->integer('avg_duration_minutes')->default(0);
            $table->boolean('busiest_flag')->default(false);
            $table->timestamps();
            
            $table->unique(['date', 'hour_bucket']);
            $table->index(['date', 'busiest_flag']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('analytics_cache');
    }
};
