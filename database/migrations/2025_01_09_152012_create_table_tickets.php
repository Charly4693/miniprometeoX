<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->bigIncrements('id'); // Cambiado a bigIncrements para PostgreSQL
            $table->foreignId('local_id')
                ->constrained()
                ->onDelete('cascade')
                ->onUpdate('cascade'); // Definir la clave foránea después del campo id
            $table->string('idMachine', 10); // Definir la columna como string
            $table->foreign('idMachine')->references('idMachines')->on('locals'); // Definir la clave foránea
            $table->string('Command', 45);
            $table->string('TicketNumber', 256);
            $table->string('Mode', 45); // Modificado de `Mode` a `Command`
            $table->timestamp('DateTime')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('LastCommandChangeDateTime')->default(DB::raw('CURRENT_TIMESTAMP')); // Usar valor predeterminado compatible con PostgreSQL
            $table->string('LastIP', 15)->default('');
            $table->string('LastUser', 45)->default('');
            $table->decimal('Value', 10, 2);
            $table->decimal('Residual', 10, 2);
            $table->string('IP', 15)->default('');
            $table->string('User', 45)->default('');
            $table->text('Comment');
            $table->string('Type', 32)->default('');
            $table->tinyInteger('TypeIsBets');
            $table->char('TypeIsAux')->default(0);
            $table->string('AuxConcept', 64)->default(''); // Modificado para especificar la longitud máxima del campo
            $table->tinyInteger('HideOnTC');
            $table->tinyInteger('Used');
            $table->string('UsedFromIP', 15)->default('');
            $table->decimal('UsedAmount', 10, 2)->default(0.00);
            $table->timestamp('UsedDateTime')->default(DB::raw('CURRENT_TIMESTAMP')); // Usar valor predeterminado compatible con PostgreSQL
            $table->unsignedBigInteger('MergedFromId')->nullable();
            $table->foreign('MergedFromId')->references('id')->on('tickets')->onDelete('set null')->onUpdate('cascade'); // Clave foránea a la misma tabla
            $table->string('Status', 45)->default('');
            $table->timestamp('ExpirationDate')->default(DB::raw('CURRENT_TIMESTAMP')); // Usar valor predeterminado compatible con PostgreSQL
            $table->string('TITOTitle', 64)->default('');
            $table->string('TITOTicketType', 32)->default('');
            $table->string('TITOStreet', 64)->default('');
            $table->string('TITOPlace', 32)->default('');
            $table->string('TITOCity', 32)->default('');
            $table->string('TITOPostalCode', 8)->default('');
            $table->string('TITODescription', 8)->default('');
            $table->tinyInteger('TITOExpirationType');
            $table->string('PersonalIdentifier', 32)->nullable();
            $table->string('PersonalPIN', 4)->nullable();
            $table->text('PersonalExtraData')->nullable(); // Permitir nulos
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};

