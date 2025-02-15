<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('eeg_raw_readings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            // JSON polje za sirove podatke
            $table->json('raw_data')->nullable();

            // Fokus izračunat tokom snimanja
            $table->float('focus')->nullable();

            // Vreme kada je EEG snimljen (može se razlikovati od created_at)
            $table->timestamp('recorded_at')->index();

            $table->timestamps();

            // Foreign key ka users (ako imas standardnu users tabelu)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('eeg_raw_readings');
    }
};
