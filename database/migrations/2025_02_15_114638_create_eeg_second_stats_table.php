<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('eeg_second_stats', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->dateTime('recorded_at');
        $table->double('avg_eeg');
        $table->timestamps();

        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $table->unique(['user_id', 'recorded_at']); // Sprečava duplikate za istu sekundu
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eeg_second_stats');
    }
};
