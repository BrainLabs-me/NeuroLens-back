<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id(); // Automatski primarni ključ za ID poruke
            $table->foreignId('user_id')  // Polje za ID korisnika (odnos prema korisnicima)
                ->constrained()            // Pretpostavljamo da postoji tabela 'users' za korisnike
                ->onDelete('cascade');     // Ako korisnik bude obrisan, briše se i njegova poruka
            $table->text('prompt');        // Sadržaj poruke
            $table->text('message');        // Sadržaj poruke
            $table->timestamps();                  // Datum kada je poruka poslana/kreirana
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
