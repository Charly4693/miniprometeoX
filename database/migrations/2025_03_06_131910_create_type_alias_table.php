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
        Schema::create('type_alias', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // Nombre del tipo del ticket (de la base de datos remota)
            $table->foreignId('id_machine')
                ->constrained('machines')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('alias'); // Alias que se guardará en la base de datos remota
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('type_alias');
    }
};
