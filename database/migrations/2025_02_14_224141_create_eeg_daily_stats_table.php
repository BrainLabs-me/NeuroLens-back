<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('eeg_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            // Datum (dan) na koji se odnosi statistika
            $table->date('stat_date')->index();

            // Suma fokusa i broj zapisa, da možemo izvući prosek
            $table->double('sum_focus')->default(0);
            $table->unsignedBigInteger('count_records')->default(0);

            // Opciono: ako želiš da snimiš i min/max fokusa ili nešto drugo
            $table->float('min_focus')->nullable();
            $table->float('max_focus')->nullable();

            $table->timestamps();

            // Unikatan indeks da ne dupliramo (user_id, stat_date)
            $table->unique(['user_id', 'stat_date']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('eeg_daily_stats');
    }
};
