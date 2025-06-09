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
        //Log::info("Procesamiento de tickets completado.");
        // Mensaje en la consola
        //echo "Procesamiento de tickets completado." . PHP_EOL; // Muestra en la consola
    }



    // Método para corregir fallos de los tickets y sus recargas auxiliares Tecnausa
    public function TicketsTecnausa($conexion, $tickets)
    {
        // Contadores
        $editedCount = 0;
        $totalProcessed = 0;
        $noUpdateCount = 0;

        foreach ($tickets as $ticket) {
            // TRY-CATCH INICIO
            try {
                $totalProcessed++;

                // Extraer el alias del campo Comment
                if (preg_match('/Pago Manual:\s*(.+)$/', $ticket->Comment, $matches)) {
                    $alias = trim($matches[1]); // Obtenemos el alias limpio

                    // Buscar la máquina en la base de datos por alias
                    $machine = Machine::where('alias', $alias)->first();
                    $updateType = true; // Permitimos actualizar 'Type'
                } else {
                    // Si no se pudo extraer el alias, buscar en TypeAlias
                    $typeAlias = TypeAlias::where('type', $ticket->Type)->first();
                    if ($typeAlias) {
                        $machine = Machine::find($typeAlias->id_machine);
                        $updateType = false; // En este caso, no actualizaremos 'Type', solo 'TypeIsAux'
                    } else {
                        continue; // Pasar al siguiente ticket si no hay alias ni asociación
                    }
                }

                if ($machine) {
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
                        $noUpdateCount++;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error procesando el ticket con TicketNumber: {$ticket->TicketNumber}. Mensaje: " . $e->getMessage());
            }
            // TRY-CATCH FIN
        }

        // Mensaje en consola
        echo "Procesamiento de tickets TECNAUSA completado." . PHP_EOL .
            "Total de tickets procesados: {$totalProcessed}." . PHP_EOL .
            "Total de tickets editados: {$editedCount}." . PHP_EOL .
            "Total de tickets sin actualización: {$noUpdateCount}." . PHP_EOL;
    }


    // Método para corregir fallos de los tickets y sus recargas auxiliares de cualquier tipo de máquina
    public function TicketsGeneral($conexion, $tickets)
    {
        try {
            // Contadores
            $editedCount = 0;
            $totalProcessed = 0;
            $noUpdateCount = 0;

            foreach ($tickets as $ticket) {
                try {
                    $totalProcessed++;

                    // Buscar la asociación en type_alias utilizando el tipo de ticket
                    $typeAlias = TypeAlias::where('type', $ticket->Type)->first();

                    if ($typeAlias) {
                        $machine = Machine::find($typeAlias->id_machine);

                        if ($machine) {
                            $oldTypeIsAux = $ticket->TypeIsAux;

                            if ($oldTypeIsAux != $machine->r_auxiliar) {
                                DB::connection($conexion)->table('tickets')
                                    ->where('TicketNumber', $ticket->TicketNumber)
                                    ->update(['TypeIsAux' => $machine->r_auxiliar]);

                                $editedCount++;
                            } else {
                                $noUpdateCount++;
                            }
                        }
                    } else {
                        // Si no se encontró en type_alias, buscar en Machine por alias
                        $machineByAlias = Machine::where('alias', $ticket->Type)->first();

                        if ($machineByAlias) {
                            if ($ticket->TypeIsAux != $machineByAlias->r_auxiliar) {
                                DB::connection($conexion)->table('tickets')
                                    ->where('TicketNumber', $ticket->TicketNumber)
                                    ->update(['TypeIsAux' => $machineByAlias->r_auxiliar]);

                                $editedCount++;
                            } else {
                                $noUpdateCount++;
                            }
                        } else {
                            // Si no se encuentra en ningún sitio, establecer TypeIsAux a 0
                            if ($ticket->TypeIsAux != 0) {
                                DB::connection($conexion)->table('tickets')
                                    ->where('TicketNumber', $ticket->TicketNumber)
                                    ->update(['TypeIsAux' => 0]);

                                $editedCount++;
                            } else {
                                $noUpdateCount++;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error procesando el ticket con TicketNumber: {$ticket->TicketNumber}. Mensaje: " . $e->getMessage() . "\nTrace:\n" . $e->getTraceAsString());
                    continue;
                }
            }

            echo "Procesamiento de tickets general completado." . PHP_EOL .
                "Total de tickets procesados: {$totalProcessed}." . PHP_EOL .
                "Total de tickets editados: {$editedCount}." . PHP_EOL .
                "Total de tickets sin actualización: {$noUpdateCount}." . PHP_EOL;
        } catch (\Exception $e) {
            Log::error("Error general al procesar tickets en TicketsGeneral: " . $e->getMessage() . "\nTrace:\n" . $e->getTraceAsString());
        }
    }
}
