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

        $this->connectToTicketServer($local);
    }

    protected function connectToTicketServer(Local $local): void
    {
        //Log::info('88888888888888888888888888888888888888888888888888888888888888888888888888888888');
        $connectionName = nuevaConexionLocal('ccm');

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


            // TABLAS PARA PARA INSERETAR DATOS O ACTUALIZARLOS, SEUGUN SI HAY CAMBIOS O NO


            DB::beginTransaction();
            try {
                // INSERT OR UPDATE para la tabla collects
                foreach ($collects as $item) {
                    // Usar el valor de Machine directamente ya que no es un array
                    $machine = $item->Machine;

                    // Buscar registro existente basándose en la combinación de local_id, LocationType, MoneyType, MoneyValue, State, y UserMoney
                    $existingRecord = DB::table('collects')
                        ->where('local_id', $local->id)
                        ->where('LocationType', $item->LocationType)
                        ->where('MoneyType', $item->MoneyType)
                        ->where('MoneyValue', $item->MoneyValue)
                        ->where('State', $item->State)
                        ->where('UserMoney', $machine)
                        ->first();

                    if ($existingRecord) {
                        // Actualizar registro existente
                        DB::table('collects')
                            ->where('id', $existingRecord->id)
                            ->update([
                                'Quantity' => $item->Quantity,
                                'Amount' => $item->Amount,
                                'UserMoney' => $machine,  // Actualizar UserMoney con el valor específico
                                'updated_at' => now(),
                            ]);

                        //Log::info('Registro actualizado en collects: id=' . $existingRecord->id . ', local_id=' . $local->id . ', LocationType=' . $item->LocationType . ', UserMoney=' . $machine);
                    } else {
                        // Insertar nuevo registro
                        DB::table('collects')->insert([
                            'local_id' => $local->id,  // Insertar local_id
                            'LocationType' => $item->LocationType,
                            'MoneyType' => $item->MoneyType,
                            'MoneyValue' => $item->MoneyValue,
                            'Quantity' => $item->Quantity,
                            'Amount' => $item->Amount,
                            'UserMoney' => $machine,  // Insertar UserMoney con el valor específico
                            'State' => $item->State,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        //Log::info('Nuevo registro insertado en collects: local_id=' . $local->id . ', LocationType=' . $item->LocationType . ', UserMoney=' . $machine);
                    }
                }
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Error al insertar los datos en la tabla COLLECTS');
                echo "Error al insertar los datos: " . $e->getMessage();
            }

            DB::beginTransaction();
            try {

                // INSERT OR UPDATE para la tabla collectdetails
                foreach ($collectDetails as $item) {
                    // Usar el valor de UserMoney directamente ya que es un string
                    $userMoney = $item->Machine;

                    // Buscar registro existente basándose en la combinación de local_id, CollectDetailType, Name, y UserMoney
                    $existingDetail = DB::table('collectdetails')
                        ->where('local_id', $local->id)
                        ->where('CollectDetailType', $item->CollectDetailType)
                        ->where('Name', $item->Name)
                        ->where('UserMoney', $userMoney)
                        ->first();

                    if ($existingDetail) {
                        // Actualizar registro existente
                        DB::table('collectdetails')
                            ->where('id', $existingDetail->id)
                            ->update([
                                'Money1' => $item->Money1,
                                'Money2' => $item->Money2,
                                'Money3' => $item->Money3,
                                'State' => $item->State,
                                'updated_at' => now(),
                            ]);

                        //Log::info('Registro actualizado en collectdetails: id=' . $existingDetail->id  . ', CollectDetailType=' . $item->CollectDetailType . ', UserMoney=' . $userMoney);
                    } else {
                        // Insertar nuevo registro
                        DB::table('collectdetails')->insert([
                            'local_id' => $local->id,  // Insertar local_id
                            'UserMoney' => $userMoney, // Insertar UserMoney
                            'CollectDetailType' => $item->CollectDetailType,
                            'Name' => $item->Name,
                            'Money1' => $item->Money1,
                            'Money2' => $item->Money2,
                            'Money3' => $item->Money3,
                            'State' => $item->State,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        //Log::info('Nuevo registro insertado en collectdetails: CollectDetailType=' . $item->CollectDetailType . ', UserMoney=' . $userMoney);
                    }
                }
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Error al insertar los datos en tabla collectdetails');
                echo "Error al insertar los datos: " . $e->getMessage();
            }

            // manejando los tickets, para ejecutarse cada 30sec o 1min
            // solo se traera lo necesario para actualizar lo de la base de datos local
            // y añadir lo que este pendiente en el servidor

            // Obtener el último ticket local sin importar su estado
            $ultimoTicketLocal = DB::table('tickets')
                ->where('local_id', $local->id)
                ->orderBy('DateTime', 'desc')
                ->first();

            // Obtener la fecha y hora del último ticket local
            $fechaLimite = $ultimoTicketLocal ? $ultimoTicketLocal->DateTime : now()->subDays(15); // Si no hay tickets, toma hace 15 días

            // Convertir $fechaLimite al formato Y-m-d H:i:s si no está en ese formato
            $fechaLimite = $fechaLimite instanceof \DateTime ? $fechaLimite->format('Y-m-d H:i:s') : $fechaLimite;

            // Obtener tickets remotos que tengan una fecha mayor a la del último ticket local para insertar
            $ticketsRemotosParaInsertar = DB::connection($connectionName)
                ->table('tickets')
                ->where('DateTime', '>', $fechaLimite)
                //->where('DateTime', '>', $fechaLimite) // Obtener tickets generados después de la fecha del último ticket local
                ->get();

            // Obtener tickets locales que no están marcados como 'EXTRACTED'
            $ticketsLocalesParaActualizar = Ticket::where('local_id', $local->id)
                ->where('Status', 'NOT LIKE', 'EXTRACTED%')
                ->get();


            // Iniciar la transacción
            DB::beginTransaction();
            try {
                // Crear un array de TicketNumbers de los tickets locales para actualización
                $localTicketNumbers = $ticketsLocalesParaActualizar->pluck('TicketNumber')->toArray();

                // Contadores
                $contadorActualizados = 0;
                $contadorInsertados = 0;

                // Comparar y actualizar tickets locales en función de los remotos
                foreach ($ticketsLocalesParaActualizar as $ticketLocal) {
                    $ticketRemoto = DB::connection($connectionName)
                        ->table('tickets')
                        ->where('TicketNumber', $ticketLocal->TicketNumber)
                        ->first();

                    if ($ticketRemoto) {
                        // Lista de campos a comparar, excluyendo los campos de fecha
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

                        // Verificar si algún campo ha cambiado
                        $fieldsToUpdate = [];
                        foreach ($fields as $field) {
                            // Comparar valores
                            if ($ticketLocal->$field != $ticketRemoto->$field) {
                                $fieldsToUpdate[$field] = $ticketRemoto->$field;
                            }
                        }

                        // Actualizar solo si hay campos diferentes
                        if (!empty($fieldsToUpdate)) {
                            // Convertir campos de fecha del ticket remoto antes de actualizar
                            $fieldsToUpdate['DateTime'] = $this->convertDateTime($ticketRemoto->DateTime);
                            $fieldsToUpdate['LastCommandChangeDateTime'] = $this->convertDateTime($ticketRemoto->LastCommandChangeDateTime);
                            $fieldsToUpdate['UsedDateTime'] = $this->convertDateTime($ticketRemoto->UsedDateTime);
                            $fieldsToUpdate['ExpirationDate'] = $this->convertDateTime($ticketRemoto->ExpirationDate);

                            // Evitar NULL en TypeIsAux
                            if (array_key_exists('TypeIsAux', $fieldsToUpdate) && is_null($fieldsToUpdate['TypeIsAux'])) {
                                $fieldsToUpdate['TypeIsAux'] = 0;
                            }

                            $fieldsToUpdate['updated_at'] = now();
                            DB::table('tickets')->where('TicketNumber', $ticketRemoto->TicketNumber)
                                ->where('DateTime', $ticketRemoto->DateTime)
                                ->update($fieldsToUpdate);

                            $contadorActualizados++; // Incrementar el contador de tickets actualizados
                        }
                    }
                }

                // Insertar nuevos tickets
                foreach ($ticketsRemotosParaInsertar as $ticketRemoto) {
                    if (!in_array($ticketRemoto->TicketNumber, $localTicketNumbers)) {
                        // Convertir campos de fecha del ticket remoto antes de insertar
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

                        $contadorInsertados++; // Incrementar el contador de tickets insertados
                    }
                }

                // Mostrar los contadores
                dump($local->name . ' Cantidad de tickets actualizados: ' . $contadorActualizados);
                dump($local->name . ' Cantidad de nuevos tickets insertados: ' . $contadorInsertados);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al insertar los datos en tabla tickets' . $e->getMessage());
                dump($local->name . 'Error sincronizando tickets: ' . $e->getMessage());
            }

            // los LOGS traremos lo necesario para mostrarlo
            // Manejar logs funcionando bien

            // Obtener la última fecha de log en la base de datos local
            $ultimaFechaLogLocal = DB::table('logs')
                ->where('local_id', $local->id)
                ->orderBy('DateTime', 'desc')
                ->value('DateTime');
            //dd($ultimaFechaLogLocal);

            // Obtener los logs remotos con fecha superior a la última fecha de log local
            $logsRemotos = DB::connection($connectionName)
                ->table('logs')
                ->where('DateTime', '>', $ultimaFechaLogLocal) // Traer solo logs posteriores
                //->whereNotIn('Type', ['doorOpened', 'doorClosed', 'error', 'warning','powerOn', 'powerOff']) // Excluir estos tipos
                ->where('Type', '!=', 'doorOpened')
                ->where('Type', '!=', 'doorClosed')
                ->where('Type', '!=', 'error')
                ->where('Type', '!=', 'warning')
                ->where('Type', '!=', 'powerOn')
                ->where('Type', '!=', 'powerOff')
                ->where(function ($query) {
                    // Excluir 'movementChange' con 'TRETA' en el texto
                    $query->where('Type', '!=', 'movementChange')
                        ->orWhere(function ($query) {
                            $query->where('Type', '=', 'movementChange')
                                ->where('Text', 'not like', '%TRETA%'); // Excluir solo ciertos 'movementChange'
                        });
                })
                ->where(function ($query) {
                    // Excluir los 'log' donde el 'Text' contenga "Estado ticket"
                    $query->where('Type', '!=', 'log')
                        ->orWhere(function ($query) {
                            $query->where('Type', '=', 'log')
                                ->where('Text', 'like', '%creado%') // Excluir "Estado ticket" en 'log'
                                ->where('Text', 'not like', '%BETS%'); // Excluir "Ticket cerrado" en 'log'
                        });
                })
                ->get();
            // dd($logsRemotos);
            // Obtener la cantidad de logs a insertar
            $cantidadLogsAInsertar = $logsRemotos->count();
            dd('Cantidad de logs a insertar: ' . $cantidadLogsAInsertar);


            DB::beginTransaction();
            try {
                foreach ($logsRemotos as $item) {
                    // Insertar un nuevo registro
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
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al insertar los datos en tabla logs' . $e->getMessage());
                dump('Error sincronizando logs para local_id: ' . $local->id . ' - ' . $e->getMessage());
            }

            // Manejar collectinfo funcionando bien

            DB::beginTransaction();
            try {
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
                        }
                    } else {
                        DB::table('collectinfo')->insert([
                            'local_id' => $local->id,
                            'Machine' => $item->Machine,
                            'LastUpdateDateTime' => $item->LastUpdateDateTime,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Manejar auxmoneystorageinfo
                foreach ($auxmoneystorageinfo as $item) {
                    $existingDetailsInfo = DB::table('auxmoneystorageinfo')
                        ->where('local_id', $local->id)
                        ->where('Machine', $item->Machine)
                        ->first();

                    if ($existingInfo) {
                        if ($existingInfo->LastUpdateDateTime != $item->LastUpdateDateTime) {
                            DB::table('auxmoneystorageinfo')
                                ->where('id', $existingInfo->id)
                                ->update([
                                    'LastUpdateDateTime' => $item->LastUpdateDateTime,
                                    'updated_at' => now(),
                                ]);
                        }
                    } else {
                        DB::table('auxmoneystorageinfo')->insert([
                            'local_id' => $local->id,
                            'Machine' => $item->Machine,
                            'LastUpdateDateTime' => $item->LastUpdateDateTime,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
                DB::commit();
                echo "Datos sincronizados correctamente.";
            } catch (Exception $e) {
                DB::rollBack();
                LOG::error('Error al insertar los datos en las tablas collectinfo y auxmoneystorageinfo' . $e->getMessage());
                echo "Error al insertar los datos: " . $e->getMessage();
            }
        } catch (Exception $e) {
            Log::error('Error al conectar a la base de datos: ' . $e->getMessage());
            echo "Error al conectar a la base de datos: " . $e->getMessage();
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
