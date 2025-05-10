<?php

namespace App\Console\Commands;

use App\Models\Local;
use App\Models\SyncLogsLocal;
use App\Models\SyncLogsLocals;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Crypt;

class PerformMoneySynchronization extends Command
{
    protected $signature = 'miniprometeo:sync-money';
    protected $description = 'Script que sincroniza los datos de las máquinas de cambio de los locales se ejecutara cuando se cree el Local/Salon';

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

            // fecha para logs y tickets
            $fechaLimite = Carbon::now()->subDays(60);

            // Obtener los datos de las tablas para traer los datos
            $collects = DB::connection($connectionName)->table('collect')->where('State', 'A')->get();
            $collectDetails = DB::connection($connectionName)->table('collectdetails')->get();
            $tickets = DB::connection($connectionName)->table('tickets')->where('DateTime', '>=', $fechaLimite)->get();
            //$logs = DB::connection($connectionName)->table('logs')->where('DateTime', '>=', $fechaLimite)->get();
            $collectinfo = DB::connection($connectionName)->table('collectinfo')->get();
            $auxmoneystorageinfo = DB::connection($connectionName)->table('auxmoneystorageinfo')->get();

            // Obtener los logs remotos que tengan una fecha posterior a la última fecha de log local
            $logs = DB::connection($connectionName)
                ->table('logs')
                ->where('DateTime', '>', $fechaLimite) // Traer solo logs posteriores
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
            // dd($logs);
            Log::info($tickets);
            Log::info($logs);
            Log::info($collectinfo);
            Log::info($auxmoneystorageinfo);


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

                        Log::info('Registro actualizado en collects: id=' . $existingRecord->id . ', local_id=' . $local->id . ', LocationType=' . $item->LocationType . ', UserMoney=' . $machine);
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

                        Log::info('Nuevo registro insertado en collects: local_id=' . $local->id . ', LocationType=' . $item->LocationType . ', UserMoney=' . $machine);
                    }
                }
                //dd($collectDetails);
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

                        Log::info('Registro actualizado en collectdetails: id=' . $existingDetail->id . ', local_id=' . $local->id . ', CollectDetailType=' . $item->CollectDetailType . ', UserMoney=' . $userMoney);
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

                        Log::info('Nuevo registro insertado en collectdetails: local_id=' . $local->id . ', CollectDetailType=' . $item->CollectDetailType . ', UserMoney=' . $userMoney);
                    }
                }

                // Manejar tickets
                foreach ($tickets as $ticket) {
                    DB::beginTransaction();
                    try {
                        // Verificar si ya existe un registro para este local_id y TicketNumber (o cualquier otro campo único que necesites)
                        $existingTicket = DB::table('tickets')
                            ->where('local_id', $local->id)
                            ->where('idMachine', $local->idMachines)
                            ->where('TicketNumber', $ticket->TicketNumber)
                            ->first();

                        if ($existingTicket) {
                            // Actualizar el registro existente
                            DB::table('tickets')
                                ->where('id', $existingTicket->id)
                                ->update([
                                    'Command' => $ticket->Command,
                                    'Mode' => $ticket->Mode,
                                    'DateTime' => $this->convertDateTime($ticket->DateTime),
                                    'LastCommandChangeDateTime' => $this->convertDateTime($ticket->LastCommandChangeDateTime),
                                    'LastIP' => $ticket->LastIP,
                                    'LastUser' => $ticket->LastUser,
                                    'Value' => $ticket->Value,
                                    'Residual' => $ticket->Residual,
                                    'IP' => $ticket->IP,
                                    'User' => $ticket->User,
                                    'Comment' => $ticket->Comment,
                                    'Type' => $ticket->Type,
                                    'TypeIsBets' => $ticket->TypeIsBets,
                                    'TypeIsAux' => $ticket->TypeIsAux,
                                    'AuxConcept' => $ticket->AuxConcept,
                                    'HideOnTC' => $ticket->HideOnTC,
                                    'Used' => $ticket->Used,
                                    'UsedFromIP' => $ticket->UsedFromIP,
                                    'UsedAmount' => $ticket->UsedAmount,
                                    'UsedDateTime' => $this->convertDateTime($ticket->UsedDateTime),
                                    'MergedFromId' => $ticket->MergedFromId,
                                    'Status' => $ticket->Status,
                                    'ExpirationDate' => $this->convertDateTime($ticket->ExpirationDate),
                                    'TITOTitle' => $ticket->TITOTitle,
                                    'TITOTicketType' => $ticket->TITOTicketType,
                                    'TITOStreet' => $ticket->TITOStreet,
                                    'TITOPlace' => $ticket->TITOPlace,
                                    'TITOCity' => $ticket->TITOCity,
                                    'TITOPostalCode' => $ticket->TITOPostalCode,
                                    'TITODescription' => $ticket->TITODescription,
                                    'TITOExpirationType' => $ticket->TITOExpirationType,
                                    'PersonalIdentifier' => $ticket->PersonalIdentifier ?? '',
                                    'PersonalPIN' => $ticket->PersonalPIN ?? '',
                                    'PersonalExtraData' => $ticket->PersonalExtraData ?? '',
                                    'updated_at' => now(),
                                ]);

                            Log::info('Ticket actualizado: local_id=' . $local->id . ', TicketNumber=' . $ticket->TicketNumber);
                        } else {
                            // Insertar un nuevo registro
                            DB::table('tickets')->insert([
                                'local_id' => $local->id,
                                'idMachine' => $local->idMachines,
                                'Command' => $ticket->Command,
                                'TicketNumber' => $ticket->TicketNumber,
                                'Mode' => $ticket->Mode,
                                'DateTime' => $this->convertDateTime($ticket->DateTime),
                                'LastCommandChangeDateTime' => $this->convertDateTime($ticket->LastCommandChangeDateTime),
                                'LastIP' => $ticket->LastIP,
                                'LastUser' => $ticket->LastUser,
                                'Value' => $ticket->Value,
                                'Residual' => $ticket->Residual,
                                'IP' => $ticket->IP,
                                'User' => $ticket->User,
                                'Comment' => $ticket->Comment,
                                'Type' => $ticket->Type,
                                'TypeIsBets' => $ticket->TypeIsBets,
                                'TypeIsAux' => $ticket->TypeIsAux,
                                'AuxConcept' => $ticket->AuxConcept,
                                'HideOnTC' => $ticket->HideOnTC,
                                'Used' => $ticket->Used,
                                'UsedFromIP' => $ticket->UsedFromIP,
                                'UsedAmount' => $ticket->UsedAmount,
                                'UsedDateTime' => $this->convertDateTime($ticket->UsedDateTime),
                                'MergedFromId' => $ticket->MergedFromId,
                                'Status' => $ticket->Status,
                                'ExpirationDate' => $this->convertDateTime($ticket->ExpirationDate),
                                'TITOTitle' => $ticket->TITOTitle,
                                'TITOTicketType' => $ticket->TITOTicketType,
                                'TITOStreet' => $ticket->TITOStreet,
                                'TITOPlace' => $ticket->TITOPlace,
                                'TITOCity' => $ticket->TITOCity,
                                'TITOPostalCode' => $ticket->TITOPostalCode,
                                'TITODescription' => $ticket->TITODescription,
                                'TITOExpirationType' => $ticket->TITOExpirationType,
                                'PersonalIdentifier' => $ticket->PersonalIdentifier ?? '',
                                'PersonalPIN' => $ticket->PersonalPIN ?? '',
                                'PersonalExtraData' => $ticket->PersonalExtraData ?? '',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            Log::info('Nuevo ticket insertado: local_id=' . $local->id . ', TicketNumber=' . $ticket->TicketNumber);
                        }

                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Error sincronizando tickets para local_id: ' . $local->id . ' - ' . $e->getMessage());
                    }
                }
                //dd($logs);

                // Manejar logs funcionando bien
                foreach ($logs as $item) {

                    DB::beginTransaction();
                    try {
                        // Verificar si ya existe un registro para este local_id y DateTime (o cualquier otro campo único que necesites)
                        $existingRecord = DB::table('logs')
                            ->where('local_id', $local->id)
                            ->where('DateTime', $item->DateTime)
                            ->first();

                        if ($existingRecord) {
                            // Actualizar el registro existente
                            DB::table('logs')
                                ->where('id', $existingRecord->id)
                                ->update([
                                    'Type' => $item->Type,
                                    'Text' => $item->Text,
                                    'Link' => $item->Link,
                                    'DateTimeEx' => $item->DateTimeEx,
                                    'IP' => $item->IP,
                                    'User' => $item->User,
                                    'updated_at' => now(),
                                ]);

                            Log::info('Registro actualizado en logs: local_id=' . $local->id . ', DateTime=' . $item->DateTime);
                        } else {
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

                            Log::info('Nuevo registro en logs insertado: local_id=' . $local->id . ', DateTime=' . $item->DateTime);
                        }

                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Error sincronizando logs para local_id: ' . $local->id . ' - ' . $e->getMessage());
                    }
                }


                // dejamos esto comentado por si hace falta algun dia transpasar user pero ya todo esto se hace desde prometeo
                // tabla de usuarios de ticket server
                // Manejar users funcionando bien
                /*foreach ($users_ticket_server as $user) {
                    DB::beginTransaction();
                    try {
                        // Excluir usuarios específicos de la asignación de rol
                        $excludedRoles = ['root', 'ccm', 'pc', 'caja', 'pcAdmin', 'pda'];

                        // Convertir el nombre de usuario a minúsculas para comparar
                        $userName = strtolower($user->User);

                        if (in_array($userName, $excludedRoles)) {
                            // No asignar rol a los usuarios excluidos
                            $user->rol = '';
                        } else {
                            // Asignar rol según los derechos en Rights
                            if (strpos($user->Rights, 'CREATETICKET') !== false && strpos($user->Rights, 'COLLECT') !== false) {
                                $user->rol = 'Personal sala';
                            } elseif (strpos($user->Rights, 'COLLECT') !== false && strpos($user->Rights, 'CREATETICKET') === false) {
                                $user->rol = 'Caja';
                            } elseif (strpos($user->Rights, 'CONFIRMTICKET') !== false && strpos($user->Rights, 'CREATETICKET') === false && strpos($user->Rights, 'COLLECT') === false) {
                                $user->rol = 'Técnicos';
                            } else {
                                $user->rol = '';
                            }
                        }

                        // Verificar si ya existe un registro para este usuario en la tabla 'users_ticket_server'
                        $existingRecord = DB::table('users_ticket_server')
                            ->where('User', $user->User)
                            ->first();

                        $userData = [
                            'Password' => Crypt::encrypt($user->Password),
                            'Rights' => $user->Rights,
                            'IsRoot' => $user->IsRoot,
                            'RightsCanBeModified' => $user->RightsCanBeModified,
                            'CurrentBalance' => $user->CurrentBalance,
                            'ReloadBalance' => $user->ReloadBalance,
                            'ReloadEveryXMinutes' => $user->ReloadEveryXMinutes,
                            'LastReloadDate' => $this->convertDateTime($user->LastReloadDate),
                            'ResetBalance' => $user->ResetBalance,
                            'ResetAtHour' => $user->ResetAtHour,
                            'LastResetDate' => $this->convertDateTime($user->LastResetDate),
                            'MaxBalance' => $user->MaxBalance,
                            'TicketTypesAllowed' => $user->TicketTypesAllowed,
                            'PID' => $user->PID,
                            'NickName' => $user->NickName,
                            'Avatar' => $user->Avatar,
                            'PIN' => $user->PIN,
                            'SessionType' => $user->SessionType,
                            'AdditionalOptionsAllowed' => $user->AdditionalOptionsAllowed,
                            'rol' => $user->rol, // Añadimos el campo rol
                            'updated_at' => now(),
                        ];

                        if ($existingRecord) {
                            // Actualizar el registro existente en 'users_ticket_server'
                            DB::table('users_ticket_server')
                                ->where('User', $user->User)
                                ->update($userData);

                            Log::info('Registro actualizado en users_ticket_server: User=' . $user->User);
                            $user_id = $existingRecord->id;
                        } else {
                            // Insertar un nuevo registro en 'users_ticket_server'
                            $user_id = DB::table('users_ticket_server')->insertGetId(array_merge($userData, ['User' => $user->User, 'created_at' => now()]));

                            Log::info('Nuevo registro insertado en users_ticket_server: User=' . $user->User);
                        }

                        // Verificar y guardar la relación en 'usersmc_locals'
                        $exists = DB::table('usersmc_locals')
                            ->where('users_ticket_server_id', $user_id)
                            ->where('local_id', $local->id)
                            ->exists();

                        if (!$exists) {
                            DB::table('usersmc_locals')->insert([
                                'users_ticket_server_id' => $user_id,
                                'local_id' => $local->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                        // Obtener la delegación y guardar la relación en 'usersmc_delegations'
                        $delegation = $local->first()->zone->delegation ?? null;

                        if ($delegation) {
                            $exists = DB::table('usersmc_delegations')
                                ->where('users_ticket_server_id', $user_id)
                                ->where('delegation_id', $delegation->id)
                                ->exists();

                            if (!$exists) {
                                DB::table('usersmc_delegations')->insert([
                                    'users_ticket_server_id' => $user_id,
                                    'delegation_id' => $delegation->id,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }

                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Error sincronizando users para User: ' . $user->User . ' - ' . $e->getMessage());
                        echo 'Error sincronizando users para User: ' . $user->User . ' - ' . $e->getMessage();
                    }
                }*/


                // Manejar collectinfo funcionando bien
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
                echo "Error al insertar los datos: " . $e->getMessage();
            }
        } catch (Exception $e) {
            echo "Error al conectar a la base de datos: " . $e->getMessage();
        }
    }
}
