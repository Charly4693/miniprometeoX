<?php

namespace App\Console\Commands;

use Dom\Comment;
use Carbon\Carbon;
use App\Models\Ticket;
use App\Models\Machine;
use App\Models\TypeAlias;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FixBugsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'miniprometeo:fix-bugs-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corregir errores de los tickets y mandarls a la auxiliar que toca cada ticket en ve de descontarlo de la auxiliar 0 y del total de la máquina de cambio';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $conexion = nuevaConexionLocal('ccm');

        // Obtener la última fecha de procesamiento desde el cache
        $lastProcessedDateTime = Cache::get('last_processed_datetime');

        // Si no hay fecha en el cache, usar una fecha de 6 meses atrás
        if (is_null($lastProcessedDateTime)) {
            $lastProcessedDateTime = now()->subMonths(6); // Seis meses atrás
        }

        // Obtener solo los tickets que han sido creados o actualizados después de la última fecha de procesamiento
        $tickets = DB::connection($conexion)
            ->table('tickets')
            ->where('DateTime', '>', $lastProcessedDateTime) // Nuevos tickets
            ->get();

        // Procesar tickets TECNAUSA
        $ticketsTecnausa = $tickets->filter(function ($ticket) {
            return $ticket->Type === 'TECNAUSA';
        });
        $this->TicketsTecnausa($conexion, $ticketsTecnausa);

        // Procesar tickets de todo tipo excepto TECNAUSA con las máquinas
        $ticketsFiltrados = $tickets->filter(function ($ticket) {
            return $ticket->Type !== 'TECNAUSA'; // Filtramos los tickets que no son de tipo TECNAUSA
        });
        $this->TicketsGeneral($conexion, $ticketsFiltrados);

        // Actualizar la última fecha de procesamiento en el cache
        if ($tickets->isNotEmpty()) {
            $lastTicketDateTime = $tickets->max('DateTime'); // Obtener la fecha máxima de los tickets procesados
            Cache::put('last_processed_datetime', $lastTicketDateTime);
        }

        // Log para indicar que el procesamiento se completó
        Log::info("Procesamiento de tickets completado.");
        // Mensaje en la consola
        echo "Procesamiento de tickets completado." . PHP_EOL; // Muestra en la consola
    }



    // Método para corregir fallos de los tickets y sus recargas auxiliares Tecnausa
    public function TicketsTecnausa($conexion, $tickets)
    {
        // Contadores
        $editedCount = 0;
        $totalProcessed = 0;
        $noUpdateCount = 0;

        foreach ($tickets as $ticket) {
            $totalProcessed++;

            // Extraer el alias del campo Comment
            if (preg_match('/Pago Manual:\s*(.+)$/', $ticket->Comment, $matches)) {
                $alias = trim($matches[1]); // Obtenemos el alias limpio

                // Buscar la máquina en la base de datos por alias
                $machine = Machine::where('alias', $alias)->first();
                $updateType = true; // Permitimos actualizar 'Type'
            } else {
                Log::warning("No se pudo extraer el alias del campo Comment: {$ticket->Comment}");

                // Si no se pudo extraer el alias, buscar en TypeAlias
                $typeAlias = TypeAlias::where('type', $ticket->Type)->first();
                if ($typeAlias) {
                    Log::info("Asociación encontrada en type_alias para Type: {$ticket->Type}. ID de máquina asociada: {$typeAlias->id_machine}");
                    $machine = Machine::find($typeAlias->id_machine);
                    $updateType = false; // En este caso, no actualizaremos 'Type', solo 'TypeIsAux'
                } else {
                    Log::warning("No se encontró asociación en type_alias para Type: {$ticket->Type}");
                    continue; // Pasar al siguiente ticket si no hay alias ni asociación
                }
            }

            if ($machine) {
                Log::info("Máquina encontrada: {$machine->alias} - r_auxiliar: {$machine->r_auxiliar}");

                // Guardar valores anteriores del ticket
                $oldType = $ticket->Type;
                $oldTypeIsAux = $ticket->TypeIsAux;
                $rAuxiliar = $machine->r_auxiliar;

                // Comparar el TypeIsAux actual con el r_auxiliar
                if ($ticket->TypeIsAux !== $rAuxiliar) {
                    // Construir los datos a actualizar
                    $updateData = ['TypeIsAux' => $rAuxiliar];

                    // Solo actualizar 'Type' si no estamos en el caso especial
                    if ($updateType) {
                        $updateData['Type'] = $machine->alias;
                    }

                    // Actualizar el ticket usando TicketNumber
                    DB::connection($conexion)->table('tickets')
                        ->where('TicketNumber', $ticket->TicketNumber)
                        ->update($updateData);

                    Log::info("TicketNumber: {$ticket->TicketNumber} actualizado con TypeIsAux: {$rAuxiliar}" . ($updateType ? " y Type: {$machine->alias}" : ""));

                    // Obtener la IP del ordenador
                    $ip = gethostbyname(gethostname());
                    $currentTime = Carbon::now();
                    $micro = sprintf("%06d", ($currentTime->micro / 1000));

                    // Registrar en la tabla de logs
                    DB::connection($conexion)->table('logs')->insert([
                        'Type' => 'log miniprometeo',
                        'Text' => "Ticket anterior:\nComment - {$ticket->Comment}\nType - {$oldType}\nTypeIsAux - {$oldTypeIsAux}\n\n" .
                            "Ticket corregido:\nComment - {$ticket->Comment}\n" .
                            ($updateType ? "Type - {$machine->alias}\n" : "") .
                            "TypeIsAux - {$rAuxiliar}",
                        'Link' => '',
                        'DateTime' => $currentTime,
                        'DateTimeEx' => $micro,
                        'IP' => $ip,
                        'User' => 'Miniprometeo',
                    ]);

                    $editedCount++;
                } else {
                    Log::info("No se requiere actualización para TicketNumber: {$ticket->TicketNumber}, TypeIsAux ya es correcto.");
                    $noUpdateCount++;
                }
            } else {
                Log::warning("Máquina no encontrada para el TicketNumber: {$ticket->TicketNumber}");
            }
        }

        // Log final del proceso
        Log::info("Procesamiento de tickets TECNAUSA completado. Total de tickets procesados: {$totalProcessed}, tickets editados: {$editedCount}, tickets sin actualización: {$noUpdateCount}.");

        // Mensaje en consola
        echo "Procesamiento de tickets TECNAUSA completado." . PHP_EOL .
            "Total de tickets procesados: {$totalProcessed}." . PHP_EOL .
            "Total de tickets editados: {$editedCount}." . PHP_EOL .
            "Total de tickets sin actualización: {$noUpdateCount}." . PHP_EOL;
    }

    // Método para corregir fallos de los tickets y sus recargas auxiliares de cualquier tipo de máquina
    public function TicketsGeneral($conexion, $tickets)
    {
        // Contadores
        $editedCount = 0;
        $totalProcessed = 0; // Contador de tickets procesados
        $noUpdateCount = 0;  // Contador de tickets que no requieren actualización

        foreach ($tickets as $ticket) {
            $totalProcessed++; // Incrementar el contador de tickets procesados
            Log::info("Procesando ticket: {$ticket->TicketNumber} con Type: {$ticket->Type}");

            // Buscar la asociación en type_alias utilizando el tipo de ticket
            $typeAlias = TypeAlias::where('type', $ticket->Type)->first();

            if ($typeAlias) {
                Log::info("Asociación encontrada en type_alias para Type: {$ticket->Type}. ID de máquina asociada: {$typeAlias->id_machine}");

                // Si hay una asociación, obtener la máquina correspondiente
                $machine = Machine::find($typeAlias->id_machine);

                if ($machine) {
                    Log::info("Máquina encontrada: {$machine->alias}. r_auxiliar: {$machine->r_auxiliar}");

                    // Guardar valores anteriores
                    $oldTypeIsAux = $ticket->TypeIsAux;
                    Log::info("TypeIsAux anterior: {$oldTypeIsAux}");

                    // Comparar TypeIsAux del ticket con r_auxiliar de la máquina
                    if ($oldTypeIsAux != $machine->r_auxiliar) {
                        // Actualizar el TypeIsAux del ticket
                        DB::connection($conexion)->table('tickets')
                            ->where('TicketNumber', $ticket->TicketNumber)
                            ->update(['TypeIsAux' => $machine->r_auxiliar]);

                        Log::info("TypeIsAux actualizado de {$oldTypeIsAux} a {$machine->r_auxiliar} para TicketNumber: {$ticket->TicketNumber}");
                        $editedCount++;
                    } else {
                        Log::info("No se requiere actualización para TicketNumber: {$ticket->TicketNumber}, TypeIsAux ya es correcto.");
                        $noUpdateCount++; // Incrementar contador de tickets que no requieren actualización
                    }
                } else {
                    Log::warning("No se encontró una máquina con id: {$typeAlias->id_machine} para TicketNumber: {$ticket->TicketNumber}");
                }
            } else {
                Log::warning("No se encontró una asociación en type_alias para Type: {$ticket->Type} y TicketNumber: {$ticket->TicketNumber}");

                // Nueva comprobación: buscar en Machine si el Type coincide con un alias
                $machineByAlias = Machine::where('alias', $ticket->Type)->first();

                if ($machineByAlias) {
                    Log::info("Se encontró una máquina con alias coincidente: {$machineByAlias->alias}. r_auxiliar: {$machineByAlias->r_auxiliar}");

                    if ($ticket->TypeIsAux != $machineByAlias->r_auxiliar) {
                        DB::connection($conexion)->table('tickets')
                            ->where('TicketNumber', $ticket->TicketNumber)
                            ->update(['TypeIsAux' => $machineByAlias->r_auxiliar]);

                        Log::info("TypeIsAux actualizado a {$machineByAlias->r_auxiliar} para TicketNumber: {$ticket->TicketNumber} por coincidencia en alias.");
                        $editedCount++;
                    } else {
                        Log::info("No se requiere actualización para TicketNumber: {$ticket->TicketNumber}, TypeIsAux ya es correcto.");
                        $noUpdateCount++;
                    }
                } else {
                    // Si no se encuentra en TypeAlias ni en Machine por alias, establecer TypeIsAux en 0
                    if ($ticket->TypeIsAux != 0) {
                        DB::connection($conexion)->table('tickets')
                            ->where('TicketNumber', $ticket->TicketNumber)
                            ->update(['TypeIsAux' => 0]);

                        Log::info("TypeIsAux actualizado a 0 para TicketNumber: {$ticket->TicketNumber} ya que no se encontró en TypeAlias ni en Machine.");
                        $editedCount++;
                    } else {
                        Log::info("No se requiere actualización para TicketNumber: {$ticket->TicketNumber}, TypeIsAux ya es 0.");
                        $noUpdateCount++;
                    }
                }
            }
        }

        Log::info("Procesamiento de tickets general completado. Total de tickets procesados: {$totalProcessed}, tickets editados: {$editedCount}, tickets sin actualización: {$noUpdateCount}.");
        echo "Procesamiento de tickets general completado." . PHP_EOL .
            "Total de tickets procesados: {$totalProcessed}." . PHP_EOL .
            "Total de tickets editados: {$editedCount}." . PHP_EOL .
            "Total de tickets sin actualización: {$noUpdateCount}." . PHP_EOL;
    }

}
