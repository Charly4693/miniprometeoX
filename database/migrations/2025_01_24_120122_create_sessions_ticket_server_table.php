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
        Schema::create('sessions_ticketServer', function (Blueprint $table) {
            $table->string('Id', 32)->primary(); // Campo Id de tipo varchar(32) y clave primaria
            $table->foreignId('local_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->onUpdate('cascade'); // Clave foránea después del campo Id
            $table->unsignedInteger('Access')->nullable()->default(null); // Campo Access de tipo int(10) unsigned, nullable con valor por defecto null
            $table->text('Data')->nullable(); // Campo Data de tipo text, nullable
            $table->timestamps(); // Campos created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions_ticketServer');
    }
};
