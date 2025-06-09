<?php

namespace App\Console\Commands;

use \Exception;
use App\Models\Local;
use App\Models\SyncLogsLocals;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformMoneySynchronizationEveryTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'miniprometeo:perform-money-synchronization-every-time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Script que sincroniza los datos de las máquinas de cambio de los locales se ejecutara cada 30seg 1min +o-';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $local = Local::first();
        if (!$local) {
            Log::error('No se encontró ningún local.');
            return;
        }

        //Log::info('Local encontrado: ' . $local->name); // o cualquier campo
        $this->connectToTicketServer($local);
    }

    protected function connectToTicketServer(Local $local): void
    {
        $connectionName = nuevaConexionLocal('ccm');
        //Log::info($connectionName);

        try {
            // Purgar la conexión y obtener el PDO
            DB::purge($connectionName);
            DB::connection($connectionName)->getPdo();
            // fecha para logs y tickets
            $fechaLimite = Carbon::now()->subDays(15);

            // Obtener los datos de las tablas para traer los datos
            $collects = DB::connection($connectionName)->table('collect')->where('State', 'A')->get();
            $collectDetails = DB::connection($connectionName)->table('collectdetails')->orderBy('id', 'ASC')->get();
            $collectinfo = DB::connection($connectionName)->table('collectinfo')->get();
            $auxmoneystorageinfo = DB::connection($connectionName)->table('auxmoneystorageinfo')->get();

            // COLLECT con los cambios sugeridos de gpt y para calcular los tiempo
            $startCollects = microtime(true);

            $insertedCount = 0;
            $updatedCount = 0;

            DB::beginTransaction();
            try {
                foreach ($collects as $item) {
                    $machine = $item->Machine;

                    // Buscar registro existente
                    $existingRecord = DB::table('collects')
                        ->where('local_id', $local->id)
                        ->where('LocationType', $item->LocationType)
                        ->where('MoneyType', $item->MoneyType)
                        ->where('MoneyValue', $item->MoneyValue)
                        ->where('State', $item->State)
                        ->where('UserMoney', $machine)
                        ->first();

                    if ($existingRecord) {
                        // Actualizar solo si cambia algo
                        if (
                            $existingRecord->Quantity != $item->Quantity ||
                            $existingRecord->Amount != $item->Amount ||
                            $existingRecord->UserMoney != $machine
                        ) {
                            DB::table('collects')
                                ->where('id', $existingRecord->id)
                                ->update([
                                    'Quantity' => $item->Quantity,
                                    'Amount' => $item->Amount,
                                    'UserMoney' => $machine,
                                    'updated_at' => now(),
                                ]);
                            $updatedCount++;
                        }
                    } else {
                        // Insertar nuevo registro
                        DB::table('collects')->insert([
                            'local_id' => $local->id,
                            'LocationType' => $item->LocationType,
                            'MoneyType' => $item->MoneyType,
                            'MoneyValue' => $item->MoneyValue,
                            'Quantity' => $item->Quantity,
                            'Amount' => $item->Amount,
                            'UserMoney' => $machine,
                            'State' => $item->State,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $insertedCount++;
                    }
                }

                DB::commit();
                $endCollects = microtime(true);
                $durationCollects = $endCollects - $startCollects;
                //dump("Tiempo en procesar collects: {$durationCollects} segundos");
                //dump("Registros insertados: $insertedCount");
                //dump("Registros actualizados: $updatedCount");
                //Log::info("Proceso de collects tomó $durationCollects segundos para " . count($collects) . " registros");
                //Log::info("Registros insertados: $insertedCount");
                //Log::info("Registros actualizados: $updatedCount");
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Error al insertar los datos en la tabla COLLECTS', ['exception' => $e]);
            }



            // COLLECDETAILS
            $startCollectDetails = microtime(true);

            $insertedCount = 0;
            $updatedCount = 0;

            DB::beginTransaction();
            try {
                foreach ($collectDetails as $item) {
                    $userMoney = $item->Machine;

                    // Buscar registro existente
                    $existingDetail = DB::table('collectdetails')
                        ->where('local_id', $local->id)
                        ->where('CollectDetailType', $item->CollectDetailType)
                        ->where('Name', $item->Name)
                        ->where('UserMoney', $userMoney)
                        ->first();

                    if ($existingDetail) {
                        // Solo actualizar si alguno de los campos ha cambiado
                        if (
                            $existingDetail->Money1 != $item->Money1 ||
                            $existingDetail->Money2 != $item->Money2 ||
                            $existingDetail->Money3 != $item->Money3 ||
                            $existingDetail->State  != $item->State
                        ) {
                            DB::table('collectdetails')
                                ->where('id', $existingDetail->id)
                                ->update([
                                    'Money1' => $item->Money1,
                                    'Money2' => $item->Money2,
                                    'Money3' => $item->Money3,
                                    'State' => $item->State,
                                    'updated_at' => now(),
                                ]);
                            $updatedCount++;
                        }
                    } else {
                        // Insertar nuevo registro
                        DB::table('collectdetails')->insert([
                            'local_id' => $local->id,
                            'UserMoney' => $userMoney,
                            'CollectDetailType' => $item->CollectDetailType,
                            'Name' => $item->Name,
                            'Money1' => $item->Money1,
                            'Money2' => $item->Money2,
                            'Money3' => $item->Money3,
                            'State' => $item->State,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $insertedCount++;
                    }
                }

                DB::commit();
                $endCollectDetails = microtime(true);
                $durationCollectDetails = $endCollectDetails - $startCollectDetails;
                //dump("Tiempo en procesar collectdetails: {$durationCollectDetails} segundos");
                //dump("Registros insertados: $insertedCount");
                //dump("Registros actualizados: $updatedCount");
                //Log::info("Proceso de collectdetails tomó $durationCollectDetails segundos para " . count($collectDetails) . " registros");
                //Log::info("Registros insertados: $insertedCount");
                //Log::info("Registros actualizados: $updatedCount");
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Error al insertar los datos en la tabla collectdetails', ['exception' => $e]);
            }


            // TICKETS
            $startTicketsSync = microtime(true);

            // Obtener el último ticket local sin importar su estado
            $ultimoTicketLocal = DB::table('tickets')
                ->where('local_id', $local->id)
                ->orderBy('DateTime', 'desc')
                ->first();

            // Obtener la fecha y hora del último ticket local
            $fechaLimite = $ultimoTicketLocal ? $ultimoTicketLocal->DateTime : now()->subDays(15);

            // Convertir $fechaLimite al formato Y-m-d H:i:s si no está en ese formato
            $fechaLimite = $fechaLimite instanceof \DateTime ? $fechaLimite->format('Y-m-d H:i:s') : $fechaLimite;

            // Obtener tickets remotos que tengan una fecha mayor a la del último ticket local para insertar
            $ticketsRemotosParaInsertar = DB::connection($connectionName)
                ->table('tickets')
                ->where('DateTime', '>', $fechaLimite)
                ->get();

            // Obtener tickets locales que no están marcados como 'EXTRACTED'
            $ticketsLocalesParaActualizar = Ticket::where('local_id', $local->id)
                ->where('Status', 'NOT LIKE', 'EXTRACTED%')
                ->get();

            DB::beginTransaction();
            try {
                $localTicketNumbers = $ticketsLocalesParaActualizar->pluck('TicketNumber')->toArray();

                $contadorActualizados = 0;
                $contadorInsertados = 0;

                foreach ($ticketsLocalesParaActualizar as $ticketLocal) {
                    $ticketRemoto = DB::connection($connectionName)
                        ->table('tickets')
                        ->where('TicketNumber', $ticketLocal->TicketNumber)
                        ->first();

                    if ($ticketRemoto) {
                        $fields = [
                            'Command',
                            'Mode',
                            'LastIP',
                            'LastUser',
                            'Value',
                            'Residual',
                            'IP',
                            'User',
                            'Comment',
                            'Type',
                            'TypeIsBets',
                            'TypeIsAux',
                            'AuxConcept',
                            'HideOnTC',
                            'Used',
                            'UsedFromIP',
                            'UsedAmount',
                            'MergedFromId',
                            'Status',
                            'TITOTitle',
                            'TITOTicketType',
                            'TITOStreet',
                            'TITOPlace',
                            'TITOCity',
                            'TITOPostalCode',
                            'TITODescription',
                            'TITOExpirationType',
                            'PersonalIdentifier',
                            'PersonalPIN',
                            'PersonalExtraData'
                        ];

                        $fieldsToUpdate = [];
                        foreach ($fields as $field) {
                            if ($ticketLocal->$field != $ticketRemoto->$field) {
                                $fieldsToUpdate[$field] = $ticketRemoto->$field;
                            }
                        }

                        if (!empty($fieldsToUpdate)) {
                            $fieldsToUpdate['DateTime'] = $this->convertDateTime($ticketRemoto->DateTime);
                            $fieldsToUpdate['LastCommandChangeDateTime'] = $this->convertDateTime($ticketRemoto->LastCommandChangeDateTime);
                            $fieldsToUpdate['UsedDateTime'] = $this->convertDateTime($ticketRemoto->UsedDateTime);
                            $fieldsToUpdate['ExpirationDate'] = $this->convertDateTime($ticketRemoto->ExpirationDate);

                            if (array_key_exists('TypeIsAux', $fieldsToUpdate) && is_null($fieldsToUpdate['TypeIsAux'])) {
                                $fieldsToUpdate['TypeIsAux'] = 0;
                            }

                            $fieldsToUpdate['updated_at'] = now();

                            DB::table('tickets')
                                ->where('TicketNumber', $ticketRemoto->TicketNumber)
                                ->update($fieldsToUpdate);

                            $contadorActualizados++;
                        }
                    }
                }

                // Insertar nuevos tickets
                foreach ($ticketsRemotosParaInsertar as $ticketRemoto) {
                    if (!in_array($ticketRemoto->TicketNumber, $localTicketNumbers)) {
                        DB::table('tickets')->insert([
                            'local_id' => $local->id,
                            'idMachine' => $local->idMachines,
                            'Command' => $ticketRemoto->Command,
                            'TicketNumber' => $ticketRemoto->TicketNumber,
                            'Mode' => $ticketRemoto->Mode,
                            'DateTime' => $this->convertDateTime($ticketRemoto->DateTime),
                            'LastCommandChangeDateTime' => $this->convertDateTime($ticketRemoto->LastCommandChangeDateTime),
                            'LastIP' => $ticketRemoto->LastIP,
                            'LastUser' => $ticketRemoto->LastUser,
                            'Value' => $ticketRemoto->Value,
                            'Residual' => $ticketRemoto->Residual,
                            'IP' => $ticketRemoto->IP,
                            'User' => $ticketRemoto->User,
                            'Comment' => $ticketRemoto->Comment,
                            'Type' => $ticketRemoto->Type,
                            'TypeIsBets' => $ticketRemoto->TypeIsBets,
                            'TypeIsAux' => $ticketRemoto->TypeIsAux ?? 0,
                            'AuxConcept' => $ticketRemoto->AuxConcept,
                            'HideOnTC' => $ticketRemoto->HideOnTC,
                            'Used' => $ticketRemoto->Used,
                            'UsedFromIP' => $ticketRemoto->UsedFromIP,
                            'UsedAmount' => $ticketRemoto->UsedAmount,
                            'UsedDateTime' => $this->convertDateTime($ticketRemoto->UsedDateTime),
                            'MergedFromId' => $ticketRemoto->MergedFromId,
                            'Status' => $ticketRemoto->Status,
                            'ExpirationDate' => $this->convertDateTime($ticketRemoto->ExpirationDate),
                            'TITOTitle' => $ticketRemoto->TITOTitle,
                            'TITOTicketType' => $ticketRemoto->TITOTicketType,
                            'TITOStreet' => $ticketRemoto->TITOStreet,
                            'TITOPlace' => $ticketRemoto->TITOPlace,
                            'TITOCity' => $ticketRemoto->TITOCity,
                            'TITOPostalCode' => $ticketRemoto->TITOPostalCode,
                            'TITODescription' => $ticketRemoto->TITODescription,
                            'TITOExpirationType' => $ticketRemoto->TITOExpirationType,
                            'PersonalIdentifier' => $ticketRemoto->PersonalIdentifier ?? '',
                            'PersonalPIN' => $ticketRemoto->PersonalPIN ?? '',
                            'PersonalExtraData' => $ticketRemoto->PersonalExtraData ?? '',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $contadorInsertados++;
                    }
                }

                DB::commit();

                $endTicketsSync = microtime(true);
                $durationTicketsSync = $endTicketsSync - $startTicketsSync;

                //dump("Tiempo en procesar tickets: {$durationTicketsSync} segundos");
                //dump("Tickets actualizados: {$contadorActualizados}");
                //dump("Tickets insertados: {$contadorInsertados}");

                //Log::info("Duración sincronización tickets local {$local->name}: {$durationTicketsSync} segundos");
                //Log::info("Sync tickets para local {$local->name}: Actualizados = {$contadorActualizados}, Insertados = {$contadorInsertados}");
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al insertar/actualizar tickets', ['exception' => $e]);
            }


            // LOGS
            // Obtener la última fecha de log en la base de datos local
            $startLogs = microtime(true);

            $insertedCount = 0;
            $updatedCount = 0;

            $ultimaFechaLogLocal = DB::table('logs')
                ->where('local_id', $local->id)
                ->orderBy('DateTime', 'desc')
                ->value('DateTime');

            if (!$ultimaFechaLogLocal) {
                $ultimaFechaLogLocal = now()->subDays(15)->format('Y-m-d H:i:s');
            }

            $logsRemotos = DB::connection($connectionName)
                ->table('logs')
                ->where('DateTime', '>', $ultimaFechaLogLocal)
                ->where('Type', '!=', 'doorOpened')
                ->where('Type', '!=', 'doorClosed')
                ->where('Type', '!=', 'error')
                ->where('Type', '!=', 'warning')
                ->where('Type', '!=', 'powerOn')
                ->where('Type', '!=', 'powerOff')
                ->where(function ($query) {
                    $query->where('Type', '!=', 'movementChange')
                        ->orWhere(function ($query) {
                            $query->where('Type', '=', 'movementChange')
                                ->where('Text', 'not like', '%TRETA%');
                        });
                })
                ->where(function ($query) {
                    $query->where('Type', '!=', 'log')
                        ->orWhere(function ($query) {
                            $query->where('Type', '=', 'log')
                                ->where('Text', 'like', '%creado%')
                                ->where('Text', 'not like', '%BETS%');
                        });
                })
                ->get();

            DB::beginTransaction();
            try {
                foreach ($logsRemotos as $item) {
                    // Buscar registro existente con clave única aproximada (local_id + DateTime + Type)
                    $existingLog = DB::table('logs')
                        ->where('local_id', $local->id)
                        ->where('DateTime', $item->DateTime)
                        ->where('Type', $item->Type)
                        ->first();

                    if ($existingLog) {
                        // Actualizar solo si cambian campos relevantes
                        if (
                            $existingLog->Text !== $item->Text ||
                            $existingLog->Link !== $item->Link ||
                            $existingLog->DateTimeEx !== $item->DateTimeEx ||
                            $existingLog->IP !== $item->IP ||
                            $existingLog->User !== $item->User
                        ) {
                            DB::table('logs')
                                ->where('id', $existingLog->id)
                                ->update([
                                    'Text' => $item->Text,
                                    'Link' => $item->Link,
                                    'DateTimeEx' => $item->DateTimeEx,
                                    'IP' => $item->IP,
                                    'User' => $item->User,
                                    'updated_at' => now(),
                                ]);
                            $updatedCount++;
                        }
                    } else {
                        // Insertar nuevo registro
                        DB::table('logs')->insert([
                            'local_id' => $local->id,
                            'Type' => $item->Type,
                            'Text' => $item->Text,
                            'Link' => $item->Link,
                            'DateTime' => $item->DateTime,
                            'DateTimeEx' => $item->DateTimeEx,
                            'IP' => $item->IP,
                            'User' => $item->User,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $insertedCount++;
                    }
                }

                DB::commit();

                $endLogs = microtime(true);
                $durationLogs = $endLogs - $startLogs;

                //dump("Tiempo en procesar logs: {$durationLogs} segundos");
                //dump("Registros insertados: $insertedCount");
                //dump("Registros actualizados: $updatedCount");

                //Log::info("Proceso de logs tomó $durationLogs segundos para " . count($logsRemotos) . " registros");
                //Log::info("Registros insertados en logs: $insertedCount");
                //Log::info("Registros actualizados en logs: $updatedCount");
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al insertar o actualizar logs', ['exception' => $e]);
            }


            // COLLECTSINFO y AUSMONEYSTORAGEINFO
            DB::beginTransaction();
            try {
                $remoteMachines = collect($collectinfo)->pluck('Machine')->toArray();

                // ===== COLLECTINFO =====
                $startCollectInfo = microtime(true);
                $insertedCollect = 0;
                $updatedCollect = 0;

                foreach ($collectinfo as $item) {
                    $existingInfo = DB::table('collectinfo')
                        ->where('local_id', $local->id)
                        ->where('Machine', $item->Machine)
                        ->first();

                    if ($existingInfo) {
                        if ($existingInfo->LastUpdateDateTime != $item->LastUpdateDateTime) {
                            DB::table('collectinfo')
                                ->where('id', $existingInfo->id)
                                ->update([
                                    'LastUpdateDateTime' => $item->LastUpdateDateTime,
                                    'updated_at' => now(),
                                ]);
                            $updatedCollect++;
                        }
                    } else {
                        DB::table('collectinfo')->insert([
                            'local_id' => $local->id,
                            'Machine' => $item->Machine,
                            'LastUpdateDateTime' => $item->LastUpdateDateTime,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $insertedCollect++;
                    }
                }

                // ===== NUEVO BLOQUE: contar y eliminar obsoletos =====
                $deletedCollect = DB::table('collectinfo')
                    ->where('local_id', $local->id)
                    ->whereNotIn('Machine', $remoteMachines)
                    ->count();

                DB::table('collectinfo')
                    ->where('local_id', $local->id)
                    ->whereNotIn('Machine', $remoteMachines)
                    ->delete();
                // ====================================================

                $endCollectInfo = microtime(true);
                $elapsedCollectInfo = $endCollectInfo - $startCollectInfo;

                //dump("Tiempo en procesar collectinfo: {$elapsedCollectInfo} segundos");
                //dump("Registros insertados en collectinfo: $insertedCollect");
                //dump("Registros actualizados en collectinfo: $updatedCollect");
                //dump("Registros eliminados en collectinfo: $deletedCollect"); // NUEVO

                //Log::info("Proceso collectinfo tomó $elapsedCollectInfo segundos");
                //Log::info("Insertados: $insertedCollect | Actualizados: $updatedCollect | Eliminados: $deletedCollect"); // NUEVO

                // ===== AUXMONEYSTORAGEINFO =====
                $startAuxMoney = microtime(true);
                $insertedAux = 0;
                $updatedAux = 0;

                foreach ($auxmoneystorageinfo as $item) {
                    $existingDetailsInfo = DB::table('auxmoneystorageinfo')
                        ->where('local_id', $local->id)
                        ->where('Machine', $item->Machine)
                        ->first();

                    if ($existingDetailsInfo) {
                        if ($existingDetailsInfo->LastUpdateDateTime != $item->LastUpdateDateTime) {
                            DB::table('auxmoneystorageinfo')
                                ->where('id', $existingDetailsInfo->id)
                                ->update([
                                    'LastUpdateDateTime' => $item->LastUpdateDateTime,
                                    'updated_at' => now(),
                                ]);
                            $updatedAux++;
                        }
                    } else {
                        DB::table('auxmoneystorageinfo')->insert([
                            'local_id' => $local->id,
                            'Machine' => $item->Machine,
                            'LastUpdateDateTime' => $item->LastUpdateDateTime,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $insertedAux++;
                    }
                }

                // ===== NUEVO BLOQUE: contar y eliminar obsoletos =====
                $remoteAuxMachines = collect($auxmoneystorageinfo)->pluck('Machine')->toArray();

                $deletedAux = DB::table('auxmoneystorageinfo')
                    ->where('local_id', $local->id)
                    ->whereNotIn('Machine', $remoteAuxMachines)
                    ->count();

                DB::table('auxmoneystorageinfo')
                    ->where('local_id', $local->id)
                    ->whereNotIn('Machine', $remoteAuxMachines)
                    ->delete();
                // ====================================================

                $endAuxMoney = microtime(true);
                $elapsedAuxMoney = $endAuxMoney - $startAuxMoney;

                //dump("Tiempo en procesar auxmoneystorageinfo: {$elapsedAuxMoney} segundos");
                //dump("Registros insertados en auxmoneystorageinfo: $insertedAux");
                //dump("Registros actualizados en auxmoneystorageinfo: $updatedAux");
                //dump("Registros eliminados en auxmoneystorageinfo: $deletedAux"); // NUEVO

                //Log::info("Proceso auxmoneystorageinfo tomó $elapsedAuxMoney segundos");
                //Log::info("Insertados: $insertedAux | Actualizados: $updatedAux | Eliminados: $deletedAux"); // NUEVO

                DB::commit();
                echo "Datos sincronizados correctamente.";
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al insertar los datos en las tablas collectinfo y auxmoneystorageinfo', ['exception' => $e]);
            }
        } catch (Exception $e) {
            Log::error('Error al conectar a la base de datos: ' . $e->getMessage());
        }
    }

    protected function convertDateTime($datetime)
    {
        // Si el valor de datetime es nulo, vacío o una fecha no válida
        if (empty($datetime) || $datetime === '0000-00-00 00:00:00' || $datetime === '0001-01-01 00:00:00') {
            return '1970-01-01 01:01:01'; // Retorna una fecha válida en MySQL
        }

        // También puedes validar si el formato de fecha es correcto
        $dateTimeObj = \DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        if ($dateTimeObj === false) {
            return '1970-01-01 01:01:01'; // Retorna una fecha válida si el formato no es válido
        }

        return $datetime; // Retorna el datetime original si es válido
    }


    protected function convertDateTimeServidor($datetime)
    {
        // Si la fecha es '1000-01-01 00:00:00', la convertimos a '1970-01-01 01:01:01' como valor válido
        if ($datetime === '1000-01-01 00:00:00') {
            return '1970-01-01 01:01:01'; // Para usar como valor "vacío" o inválido
        }

        // También puedes validar si el formato de fecha es correcto
        $dateTimeObj = \DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        if ($dateTimeObj === false) {
            return '1970-01-01 01:01:01'; // Retorna una fecha válida si el formato no es válido
        }

        return $datetime; // Retorna el datetime original si es válido
    }
}
