<?php

namespace App\Console\Commands;

use App\Models\Local;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class PerformMoneySynchronizationConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'miniprometeo:sync-money-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Script que sincroniza los datos de las máquinas de cambio de los locales para la tabla CONFIG';

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

            $config = DB::connection($connectionName)->table('config')->get();

            // INSERT OR UPDATE para la tabla config
            DB::beginTransaction();
            try {

                foreach ($config as $item) {

                    // Obtener el registro específico que coincide con el local_id
                    $existingRecord = DB::table('config')
                        ->where('local_id', $local->id)
                        ->first();

                    if ($existingRecord) {
                        // Actualizar solo si hay cambios necesarios
                        $needsUpdate = false;

                        // Array con los campos a comparar
                        $fieldsToCompare = [
                            'MoneyLowLimitToCreate',
                            'MoneyAdaptLowValuesOnCreation',
                            'MoneyLimitThatNeedsAuthorization',
                            'MoneyLimitAbsolute',
                            'MoneyLimitInTypeBets',
                            'MoneyDenomination',
                            'RoundPartialPrizes',
                            'RoundPartialPrizesValue',
                            'NumberOfDigits',
                            'NewTicketNumberFormat',
                            'HeaderOfTicketNumber',
                            'HoursBetweenAutopurges',
                            'NumberOfEventsToAutopurge',
                            'DaysToAutopurgeIfEventOlderThan',
                            'LastAutopurgeTimestamp' => 'convertDateTime',
                            'AvatarsCachePath',
                            'AdvancedGUI',
                            'ForceAllowExports',
                            'ExpirationDate' => 'convertDateTime',
                            'LastAutoexpireTimestamp' => 'convertDateTime',
                            'TITOTitle',
                            'TITOTicketType',
                            'TITOStreet',
                            'TITOPlace',
                            'TITOCity',
                            'TITOPostalCode',
                            'TITODescription',
                            'TITOExpirationType',
                            'NumberOfItemsPerPage',
                            'BackupDBPath',
                            'HoursBetweenBackupDB',
                            'DaysToKeepBackupDB',
                            'Aux1Limit',
                            'Aux2Limit',
                            'Aux3Limit',
                            'Aux4Limit',
                            'Aux5Limit',
                            'Aux6Limit',
                            'Aux7Limit',
                            'Aux8Limit',
                            'Aux9Limit',
                            'Aux10Limit',
                            'HideOnTCFilter',
                            'ShowCloseOnlyFromIPs',
                            'AllowIPs',
                            'BanIPs',
                            'AutoAddIPsToBan',
                            'AllowMACs',
                            'BanMACs',
                            'AutoAddMACsToBan',
                            'AllowTicketTypes',
                            'BanTicketTypes',
                            'OnCloseTicketTypeFilter1',
                            'OnCloseTicketTypeAllowIPs1',
                            'OnCloseTicketTypeBanIPs1',
                            'OnCloseTicketTypeIPCreation1',
                            'OnCloseTicketTypeFilter2',
                            'OnCloseTicketTypeAllowIPs2',
                            'OnCloseTicketTypeBanIPs2',
                            'OnCloseTicketTypeIPCreation2',
                            'OnCloseTicketTypeFilter3',
                            'OnCloseTicketTypeAllowIPs3',
                            'OnCloseTicketTypeBanIPs3',
                            'OnCloseTicketTypeIPCreation3',
                            'OnCloseTicketTypeFilter4',
                            'OnCloseTicketTypeAllowIPs4',
                            'OnCloseTicketTypeBanIPs4',
                            'OnCloseTicketTypeIPCreation4',
                            'OnCloseTicketTypeFilter5',
                            'OnCloseTicketTypeAllowIPs5',
                            'OnCloseTicketTypeBanIPs5',
                            'OnCloseTicketTypeIPCreation5'
                        ];

                        foreach ($fieldsToCompare as $field => $conversion) {
                            if (is_string($field)) {
                                $fieldName = $field;
                                $conversionFunction = $conversion;
                            } else {
                                $fieldName = $conversion;
                                $conversionFunction = null;
                            }

                            $existingValue = $existingRecord->$fieldName;
                            $newValue = $conversionFunction ? $this->$conversionFunction($item->$fieldName) : $item->$fieldName;

                            if ($existingValue != $newValue) {
                                $needsUpdate = true;
                                break;
                            }
                        }

                        if ($needsUpdate) {
                            DB::table('config')
                                ->where('id', $existingRecord->id)
                                ->update([
                                    'MoneyLowLimitToCreate' => $item->MoneyLowLimitToCreate,
                                    'MoneyAdaptLowValuesOnCreation' => $item->MoneyAdaptLowValuesOnCreation,
                                    'MoneyLimitThatNeedsAuthorization' => $item->MoneyLimitThatNeedsAuthorization,
                                    'MoneyLimitAbsolute' => $item->MoneyLimitAbsolute,
                                    'MoneyLimitInTypeBets' => $item->MoneyLimitInTypeBets,
                                    'MoneyDenomination' => $item->MoneyDenomination,
                                    'RoundPartialPrizes' => $item->RoundPartialPrizes,
                                    'RoundPartialPrizesValue' => $item->RoundPartialPrizesValue,
                                    'NumberOfDigits' => $item->NumberOfDigits,
                                    'NewTicketNumberFormat' => $item->NewTicketNumberFormat,
                                    'HeaderOfTicketNumber' => $item->HeaderOfTicketNumber,
                                    'HoursBetweenAutopurges' => $item->HoursBetweenAutopurges,
                                    'NumberOfEventsToAutopurge' => $item->NumberOfEventsToAutopurge,
                                    'DaysToAutopurgeIfEventOlderThan' => $item->DaysToAutopurgeIfEventOlderThan,
                                    'LastAutopurgeTimestamp' => $this->convertDateTime($item->LastAutopurgeTimestamp),
                                    'AvatarsCachePath' => $item->AvatarsCachePath,
                                    'AdvancedGUI' => $item->AdvancedGUI,
                                    'ForceAllowExports' => $item->ForceAllowExports,
                                    'ExpirationDate' => $this->convertDateTime($item->ExpirationDate),
                                    'LastAutoexpireTimestamp' => $this->convertDateTime($item->LastAutoexpireTimestamp),
                                    'TITOTitle' => $item->TITOTitle,
                                    'TITOTicketType' => $item->TITOTicketType,
                                    'TITOStreet' => $item->TITOStreet,
                                    'TITOPlace' => $item->TITOPlace,
                                    'TITOCity' => $item->TITOCity,
                                    'TITOPostalCode' => $item->TITOPostalCode,
                                    'TITODescription' => $item->TITODescription,
                                    'TITOExpirationType' => $item->TITOExpirationType,
                                    'NumberOfItemsPerPage' => $item->NumberOfItemsPerPage,
                                    'BackupDBPath' => $item->BackupDBPath,
                                    'HoursBetweenBackupDB' => $item->HoursBetweenBackupDB,
                                    'DaysToKeepBackupDB' => $item->DaysToKeepBackupDB,
                                    'Aux1Limit' => $item->Aux1Limit,
                                    'Aux2Limit' => $item->Aux2Limit,
                                    'Aux3Limit' => $item->Aux3Limit,
                                    'Aux4Limit' => $item->Aux4Limit,
                                    'Aux5Limit' => $item->Aux5Limit,
                                    'Aux6Limit' => $item->Aux6Limit,
                                    'Aux7Limit' => $item->Aux7Limit,
                                    'Aux8Limit' => $item->Aux8Limit,
                                    'Aux9Limit' => $item->Aux9Limit,
                                    'Aux10Limit' => $item->Aux10Limit,
                                    'HideOnTCFilter' => $item->HideOnTCFilter,
                                    'ShowCloseOnlyFromIPs' => $item->ShowCloseOnlyFromIPs,
                                    'AllowIPs' => $item->AllowIPs,
                                    'BanIPs' => $item->BanIPs,
                                    'AutoAddIPsToBan' => $item->AutoAddIPsToBan,
                                    'AllowMACs' => $item->AllowMACs,
                                    'BanMACs' => $item->BanMACs,
                                    'AutoAddMACsToBan' => $item->AutoAddMACsToBan,
                                    'AllowTicketTypes' => $item->AllowTicketTypes,
                                    'BanTicketTypes' => $item->BanTicketTypes,
                                    'OnCloseTicketTypeFilter1' => $item->OnCloseTicketTypeFilter1,
                                    'OnCloseTicketTypeAllowIPs1' => $item->OnCloseTicketTypeAllowIPs1,
                                    'OnCloseTicketTypeBanIPs1' => $item->OnCloseTicketTypeBanIPs1,
                                    'OnCloseTicketTypeIPCreation1' => $item->OnCloseTicketTypeIPCreation1,
                                    'OnCloseTicketTypeFilter2' => $item->OnCloseTicketTypeFilter2,
                                    'OnCloseTicketTypeAllowIPs2' => $item->OnCloseTicketTypeAllowIPs2,
                                    'OnCloseTicketTypeBanIPs2' => $item->OnCloseTicketTypeBanIPs2,
                                    'OnCloseTicketTypeIPCreation2' => $item->OnCloseTicketTypeIPCreation2,
                                    'OnCloseTicketTypeFilter3' => $item->OnCloseTicketTypeFilter3,
                                    'OnCloseTicketTypeAllowIPs3' => $item->OnCloseTicketTypeAllowIPs3,
                                    'OnCloseTicketTypeBanIPs3' => $item->OnCloseTicketTypeBanIPs3,
                                    'OnCloseTicketTypeIPCreation3' => $item->OnCloseTicketTypeIPCreation3,
                                    'OnCloseTicketTypeFilter4' => $item->OnCloseTicketTypeFilter4,
                                    'OnCloseTicketTypeAllowIPs4' => $item->OnCloseTicketTypeAllowIPs4,
                                    'OnCloseTicketTypeBanIPs4' => $item->OnCloseTicketTypeBanIPs4,
                                    'OnCloseTicketTypeIPCreation4' => $item->OnCloseTicketTypeIPCreation4,
                                    'OnCloseTicketTypeFilter5' => $item->OnCloseTicketTypeFilter5,
                                    'OnCloseTicketTypeAllowIPs5' => $item->OnCloseTicketTypeAllowIPs5,
                                    'OnCloseTicketTypeBanIPs5' => $item->OnCloseTicketTypeBanIPs5,
                                    'OnCloseTicketTypeIPCreation5' => $item->OnCloseTicketTypeIPCreation5,
                                    'updated_at' => now(),
                                ]);
                        }
                    } else {
                        // Insertar el nuevo registro
                        DB::table('config')->insert([
                            'local_id' => $local->id,
                            'MoneySymbol' => $item->MoneySymbol,
                            'MoneyLowLimitToCreate' => $item->MoneyLowLimitToCreate,
                            'MoneyAdaptLowValuesOnCreation' => $item->MoneyAdaptLowValuesOnCreation,
                            'MoneyLimitThatNeedsAuthorization' => $item->MoneyLimitThatNeedsAuthorization,
                            'MoneyLimitAbsolute' => $item->MoneyLimitAbsolute,
                            'MoneyLimitInTypeBets' => $item->MoneyLimitInTypeBets,
                            'MoneyDenomination' => $item->MoneyDenomination,
                            'RoundPartialPrizes' => $item->RoundPartialPrizes,
                            'RoundPartialPrizesValue' => $item->RoundPartialPrizesValue,
                            'NumberOfDigits' => $item->NumberOfDigits,
                            'NewTicketNumberFormat' => $item->NewTicketNumberFormat,
                            'HeaderOfTicketNumber' => $item->HeaderOfTicketNumber,
                            'HoursBetweenAutopurges' => $item->HoursBetweenAutopurges,
                            'NumberOfEventsToAutopurge' => $item->NumberOfEventsToAutopurge,
                            'DaysToAutopurgeIfEventOlderThan' => $item->DaysToAutopurgeIfEventOlderThan,
                            'LastAutopurgeTimestamp' => $this->convertDateTime($item->LastAutopurgeTimestamp),
                            'AvatarsCachePath' => $item->AvatarsCachePath,
                            'AdvancedGUI' => $item->AdvancedGUI,
                            'ForceAllowExports' => $item->ForceAllowExports,
                            'ExpirationDate' => $this->convertDateTime($item->ExpirationDate),
                            'LastAutoexpireTimestamp' => $this->convertDateTime($item->LastAutoexpireTimestamp),
                            'TITOTitle' => $item->TITOTitle,
                            'TITOTicketType' => $item->TITOTicketType,
                            'TITOStreet' => $item->TITOStreet,
                            'TITOPlace' => $item->TITOPlace,
                            'TITOCity' => $item->TITOCity,
                            'TITOPostalCode' => $item->TITOPostalCode,
                            'TITODescription' => $item->TITODescription,
                            'TITOExpirationType' => $item->TITOExpirationType,
                            'NumberOfItemsPerPage' => $item->NumberOfItemsPerPage,
                            'BackupDBPath' => $item->BackupDBPath,
                            'HoursBetweenBackupDB' => $item->HoursBetweenBackupDB,
                            'DaysToKeepBackupDB' => $item->DaysToKeepBackupDB,
                            'Aux1Limit' => $item->Aux1Limit,
                            'Aux2Limit' => $item->Aux2Limit,
                            'Aux3Limit' => $item->Aux3Limit,
                            'Aux4Limit' => $item->Aux4Limit,
                            'Aux5Limit' => $item->Aux5Limit,
                            'Aux6Limit' => $item->Aux6Limit,
                            'Aux7Limit' => $item->Aux7Limit,
                            'Aux8Limit' => $item->Aux8Limit,
                            'Aux9Limit' => $item->Aux9Limit,
                            'Aux10Limit' => $item->Aux10Limit,
                            'HideOnTCFilter' => $item->HideOnTCFilter,
                            'ShowCloseOnlyFromIPs' => $item->ShowCloseOnlyFromIPs,
                            'AllowIPs' => $item->AllowIPs,
                            'BanIPs' => $item->BanIPs,
                            'AutoAddIPsToBan' => $item->AutoAddIPsToBan,
                            'AllowMACs' => $item->AllowMACs,
                            'BanMACs' => $item->BanMACs,
                            'AutoAddMACsToBan' => $item->AutoAddMACsToBan,
                            'AllowTicketTypes' => $item->AllowTicketTypes,
                            'BanTicketTypes' => $item->BanTicketTypes,
                            'OnCloseTicketTypeFilter1' => $item->OnCloseTicketTypeFilter1,
                            'OnCloseTicketTypeAllowIPs1' => $item->OnCloseTicketTypeAllowIPs1,
                            'OnCloseTicketTypeBanIPs1' => $item->OnCloseTicketTypeBanIPs1,
                            'OnCloseTicketTypeIPCreation1' => $item->OnCloseTicketTypeIPCreation1,
                            'OnCloseTicketTypeFilter2' => $item->OnCloseTicketTypeFilter2,
                            'OnCloseTicketTypeAllowIPs2' => $item->OnCloseTicketTypeAllowIPs2,
                            'OnCloseTicketTypeBanIPs2' => $item->OnCloseTicketTypeBanIPs2,
                            'OnCloseTicketTypeIPCreation2' => $item->OnCloseTicketTypeIPCreation2,
                            'OnCloseTicketTypeFilter3' => $item->OnCloseTicketTypeFilter3,
                            'OnCloseTicketTypeAllowIPs3' => $item->OnCloseTicketTypeAllowIPs3,
                            'OnCloseTicketTypeBanIPs3' => $item->OnCloseTicketTypeBanIPs3,
                            'OnCloseTicketTypeIPCreation3' => $item->OnCloseTicketTypeIPCreation3,
                            'OnCloseTicketTypeFilter4' => $item->OnCloseTicketTypeFilter4,
                            'OnCloseTicketTypeAllowIPs4' => $item->OnCloseTicketTypeAllowIPs4,
                            'OnCloseTicketTypeBanIPs4' => $item->OnCloseTicketTypeBanIPs4,
                            'OnCloseTicketTypeIPCreation4' => $item->OnCloseTicketTypeIPCreation4,
                            'OnCloseTicketTypeFilter5' => $item->OnCloseTicketTypeFilter5,
                            'OnCloseTicketTypeAllowIPs5' => $item->OnCloseTicketTypeAllowIPs5,
                            'OnCloseTicketTypeBanIPs5' => $item->OnCloseTicketTypeBanIPs5,
                            'OnCloseTicketTypeIPCreation5' => $item->OnCloseTicketTypeIPCreation5,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
                DB::commit();
                echo "Datos sincronizados correctamente.";
            } catch (Exception $e) {
                DB::rollBack();
                Log::error($e);
                echo "Error al insertar los datos: " . $e->getMessage();
            }
        } catch (Exception $e) {
            Log::error($e);
            echo "Error al conectar a la base de datos: " . $e->getMessage();
        }
    }
}
