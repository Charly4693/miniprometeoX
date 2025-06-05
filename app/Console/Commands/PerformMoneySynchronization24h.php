<?php

namespace App\Console\Commands;

use App\Models\Local;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class PerformMoneySynchronization24h extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'miniprometeo:sync-money-synchronization24h';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Script que sincroniza los datos de las máquinas de cambio de los locales cada 24H';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $local = Local::first();

        $this->connectToTicketServer($local);
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

    protected function connectToTicketServer(Local $local): void
    {
        $connectionName = nuevaConexionLocal('ccm');

        try {
            // Purgar la conexión y obtener el PDO
            DB::purge($connectionName);
            DB::connection($connectionName)->getPdo();

            // llamada a las tablas para traer los datos
            $accounting = DB::connection($connectionName)->table('accounting')->get();
            $accountinginfo = DB::connection($connectionName)->table('accountinginfo')->get();
            $betmoneystorage = DB::connection($connectionName)->table('betmoneystorage')->get();
            $betmoneystorageinfo = DB::connection($connectionName)->table('betmoneystorageinfo')->get();
            $collectdetailsinfo = DB::connection($connectionName)->table('collectdetailsinfo')->get();
            $hcmoneystorage = DB::connection($connectionName)->table('hcmoneystorage')->get();
            $hcmoneystorageinfo = DB::connection($connectionName)->table('hcmoneystorageinfo')->get();
            $hiddentickets = DB::connection($connectionName)->table('hiddentickets')->get();
            $players = DB::connection($connectionName)->table('players')->get();
            //No hace falta los datos de esta tabla pero la dejamos en funcionamiento de insertar y editar datos
            //$sessions_ticketServer = DB::connection($connectionName)->table('sessions')->get();

            // INSERTAR O ACTULZAR datos de cada tabla
            DB::beginTransaction();
            try {

                // Manejar accounting
                foreach ($accounting as $item) {
                    $existingDetailsInfo = DB::table('accounting')
                        ->where('local_id', $local->id)
                        ->where('Machine', $item->Machine)
                        ->first();

                    if ($existingDetailsInfo) {
                        if ($existingDetailsInfo->LastAccess != $item->LastAccess) {
                            DB::table('accounting')
                                ->where('id', $existingDetailsInfo->id)
                                ->update([
                                    'LastAccess' => $item->LastAccess,
                                    'Counter' => $item->Counter,
                                    'Category' => $item->Category,
                                    'Description' => $item->Description,
                                    'Amount' => $item->Amount,
                                    'Text' => $item->Text,
                                    'updated_at' => now(),
                                ]);
                        }
                    } else {
                        DB::table('accounting')->insert([
                            'local_id' => $local->id,
                            'Machine' => $item->Machine,
                            'Counter' => $item->Counter,
                            'Category' => $item->Category,
                            'Description' => $item->Description,
                            'Amount' => $item->Amount,
                            'Text' => $item->Text,
                            'LastAccess' => $item->LastAccess,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Manejar accountinginfo
                foreach ($accountinginfo as $item) {
                    $existingDetailsInfo = DB::table('accountinginfo')
                        ->where('local_id', $local->id)
                        ->where('Machine', $item->Machine)
                        ->first();

                    if ($existingDetailsInfo) {
                        if ($existingDetailsInfo->LastUpdateDateTime != $item->LastUpdateDateTime) {
                            DB::table('accountinginfo')
                                ->where('id', $existingDetailsInfo->id)
                                ->update([
                                    'LastUpdateDateTime' => $item->LastUpdateDateTime,
                                    'updated_at' => now(),
                                ]);
                        }
                    } else {
                        DB::table('accountinginfo')->insert([
                            'local_id' => $local->id,
                            'Machine' => $item->Machine,
                            'LastUpdateDateTime' => $item->LastUpdateDateTime,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }


                // Manejar betmoneystorage
                foreach ($betmoneystorage as $item) {
                    // Obtener el registro específico que coincide con todos los campos, excepto MoneyIn y MoneyOut
                    $existingRecord = DB::table('betmoneystorage')
                        ->where('local_id', $local->id)
                        ->where('Machine', $item->Machine)
                        ->where('State', $item->State)
                        ->first();

                    if ($existingRecord) {
                        // Actualizar solo si hay cambios necesarios
                        if (
                            $existingRecord->MoneyIn != $item->MoneyIn ||
                            $existingRecord->MoneyOut != $item->MoneyOut
                        ) {
                            DB::table('betmoneystorage')
                                ->where('id', $existingRecord->id)
                                ->update([
                                    'MoneyIn' => $item->MoneyIn,
                                    'MoneyOut' => $item->MoneyOut,
                                    'updated_at' => now(),
                                ]);
                        }
                    } else {
                        // Insertar si no existe el registro
                        DB::table('betmoneystorage')->insert([
                            'local_id' => $local->id,
                            'Machine' => $item->Machine,
                            'MoneyIn' => $item->MoneyIn,
                            'MoneyOut' => $item->MoneyOut,
                            'State' => $item->State,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Manejar betmoneystorageinfo
                foreach ($betmoneystorageinfo as $item) {
                    $existingDetailsInfo = DB::table('betmoneystorageinfo')
                        ->where('local_id', $local->id)
                        ->where('Machine', $item->Machine)
                        ->first();

                    if ($existingDetailsInfo) {
                        if ($existingDetailsInfo->LastUpdateDateTime != $item->LastUpdateDateTime) {
                            DB::table('betmoneystorageinfo')
                                ->where('id', $existingDetailsInfo->id)
                                ->update([
                                    'LastUpdateDateTime' => $item->LastUpdateDateTime,
                                    'updated_at' => now(),
                                ]);
                        }
                    } else {
                        DB::table('betmoneystorageinfo')->insert([
                            'local_id' => $local->id,
                            'Machine' => $item->Machine,
                            'LastUpdateDateTime' => $item->LastUpdateDateTime,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Manejar collectdetailsinfo
                foreach ($collectdetailsinfo as $item) {
                    $existingDetailsInfo = DB::table('collectdetailsinfo')
                        ->where('local_id', $local->id)
                        ->where('Machine', $item->Machine)
                        ->first();

                    if ($existingDetailsInfo) {
                        if ($existingDetailsInfo->LastUpdateDateTime != $item->LastUpdateDateTime) {
                            DB::table('collectdetailsinfo')
                                ->where('id', $existingDetailsInfo->id)
                                ->update([
                                    'LastUpdateDateTime' => $item->LastUpdateDateTime,
                                    'updated_at' => now(),
                                ]);
                        }
                    } else {
                        DB::table('collectdetailsinfo')->insert([
                            'local_id' => $local->id,
                            'Machine' => $item->Machine,
                            'LastUpdateDateTime' => $item->LastUpdateDateTime,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Manejar hcmoneystorage
                foreach ($hcmoneystorage as $item) {
                    // Verificar si ya existe un registro para este local_id y HCName
                    $existingRecord = DB::table('hcmoneystorage')
                        ->where('local_id', $local->id)
                        ->where('HCName', $item->HCName)
                        ->first();

                    if ($existingRecord) {
                        // Actualizar el registro existente
                        DB::table('hcmoneystorage')
                            ->where('id', $existingRecord->id)
                            ->update([
                                'TypeIsHC' => $item->TypeIsHC,
                                'Machine' => $local->idMachines, // Agregar el campo Machine
                                'MoneyIn' => $item->MoneyIn,
                                'MoneyOut' => $item->MoneyOut,
                                'State' => $item->State,
                                'updated_at' => now(),
                            ]);

                        //Log::info('Registro actualizado: local_id=' . $local->id . ', HCName=' . $item->HCName);
                    } else {
                        // Insertar un nuevo registro
                        DB::table('hcmoneystorage')->insert([
                            'local_id' => $local->id,
                            'Machine' => $local->idMachines, // Agregar el campo Machine
                            'TypeIsHC' => $item->TypeIsHC,
                            'HCName' => $item->HCName,
                            'MoneyIn' => $item->MoneyIn,
                            'MoneyOut' => $item->MoneyOut,
                            'State' => $item->State,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        //Log::info('Nuevo registro insertado: local_id=' . $local->id . ', HCName=' . $item->HCName);
                    }
                }

                // Manejar hcmoneystorageinfo
                foreach ($hcmoneystorageinfo as $item) {
                    $existingDetailsInfo = DB::table('hcmoneystorageinfo')
                        ->where('local_id', $local->id)
                        ->where('Machine', $item->Machine)
                        ->first();

                    if ($existingDetailsInfo) {
                        if ($existingDetailsInfo->LastUpdateDateTime != $item->LastUpdateDateTime) {
                            DB::table('hcmoneystorageinfo')
                                ->where('id', $existingDetailsInfo->id)
                                ->update([
                                    'LastUpdateDateTime' => $item->LastUpdateDateTime,
                                    'updated_at' => now(),
                                ]);
                        }
                    } else {
                        DB::table('hcmoneystorageinfo')->insert([
                            'local_id' => $local->id,
                            'Machine' => $item->Machine,
                            'LastUpdateDateTime' => $item->LastUpdateDateTime,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Manejar hiddentickets funcionando bien
                foreach ($hiddentickets as $item) {

                    // Verificar si ya existe un registro para este local_id y LinkedTicketId
                    $existingRecord = DB::table('hiddentickets')
                        ->where('local_id', $local->id)
                        ->where('LinkedTicketId', $item->LinkedTicketId)
                        ->first();

                    if ($existingRecord) {
                        // Actualizar el registro existente
                        DB::table('hiddentickets')
                            ->where('id', $existingRecord->id)
                            ->update([
                                'DateTime' => $this->convertDateTime($item->DateTime),
                                'Value' => $item->Value,
                                'Comment' => $item->Comment,
                                'updated_at' => now(),
                            ]);

                        //Log::info('Registro actualizado: local_id=' . $local->id . ', LinkedTicketId=' . $item->LinkedTicketId);
                    } else {
                        // Insertar un nuevo registro
                        DB::table('hiddentickets')->insert([
                            'local_id' => $local->id,
                            'DateTime' => $item->DateTime,
                            'Value' => $item->Value,
                            'Comment' => $item->Comment,
                            'LinkedTicketId' => $item->LinkedTicketId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        //Log::info('Nuevo registro insertado: local_id=' . $local->id . ', LinkedTicketId=' . $item->LinkedTicketId);
                    }
                }

                // Manejar players funcionando bien
                foreach ($players as $item) {

                    // Verificar si ya existe un registro para este local_id y Player
                    $existingRecord = DB::table('players')
                        ->where('local_id', $local->id)
                        ->where('Player', $item->Player)
                        ->first();

                    if ($existingRecord) {
                        // Actualizar el registro existente
                        DB::table('players')
                            ->where('id', $existingRecord->id)
                            ->update([
                                'Password' => $item->Password,
                                'MoneyIn' => $item->MoneyIn,
                                'MoneyOut' => $item->MoneyOut,
                                'MoneyDrop' => $item->MoneyDrop,
                                'Points' => $item->Points,
                                'PID' => $item->PID,
                                'NickName' => $item->NickName,
                                'Avatar' => $item->Avatar,
                                'updated_at' => now(),
                            ]);

                        //Log::info('Registro actualizado: local_id=' . $local->id . ', Player=' . $item->Player);
                    } else {
                        // Insertar un nuevo registro
                        DB::table('players')->insert([
                            'local_id' => $local->id,
                            'Player' => $item->Player,
                            'Password' => $item->Password,
                            'MoneyIn' => $item->MoneyIn,
                            'MoneyOut' => $item->MoneyOut,
                            'MoneyDrop' => $item->MoneyDrop,
                            'Points' => $item->Points,
                            'PID' => $item->PID,
                            'NickName' => $item->NickName,
                            'Avatar' => $item->Avatar,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        //Log::info('Nuevo registro insertado: local_id=' . $local->id . ', Player=' . $item->Player);
                    }
                }

                // Manejar sessions_ticketServer funcionando bien
                /*foreach ($sessions_ticketServer as $session) {
                    DB::beginTransaction();
                    try {
                        // Verificar si ya existe un registro para este Id en la tabla local 'sessions_ticketServer'
                        $existingRecord = DB::table('sessions_ticketServer')
                            ->where('Id', $session->Id)
                            ->first();

                        if ($existingRecord) {
                            // Actualizar el registro existente en 'sessions_ticketServer'
                            DB::table('sessions_ticketServer')
                                ->where('Id', $session->Id)
                                ->update([
                                    'local_id' => $local->id,
                                    'Access' => $session->Access,
                                    'Data' => $session->Data,
                                    'updated_at' => now(),
                                ]);

                            //Log::info('Registro actualizado en sessions_ticketServer: Id=' . $session->Id . ', local_id=' . $local->id);
                        } else {
                            // Insertar un nuevo registro en 'sessions_ticketServer'
                            DB::table('sessions_ticketServer')->insert([
                                'Id' => $session->Id,
                                'local_id' => $local->id,
                                'Access' => $session->Access,
                                'Data' => $session->Data,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            //Log::info('Nuevo registro insertado en sessions_ticketServer: Id=' . $session->Id . ', local_id=' . $local->id);
                        }

                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Error sincronizando sessions para Id: ' . $session->Id . ' - ' . $e->getMessage());
                    }
                }*/



                DB::commit();
                echo "Datos sincronizados correctamente.";
            } catch (Exception $e) {
                DB::rollBack();
                echo "Error al insertar los datos: " . $e->getMessage();
            }
        } catch (Exception $e) {
            echo "Error al conectar a la base de datos: " . $e->getMessage();
        }
    }
}
