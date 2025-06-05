<?php

namespace App\Console\Commands;

use App\Models\Local;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class PerformMoneySynchronizationAuxmoneystorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'miniprometeo:sync-money-auxmoneystorage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Script que sincroniza los datos de las máquinas de cambio de los locales para la tabla AUXMONEYSTORAGE';

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
        $connectionName = nuevaConexionLocal('ccm');

        try {
            DB::purge($connectionName);
            DB::connection($connectionName)->getPdo();

            $auxmoneystorage = DB::connection($connectionName)->table('auxmoneystorage')->get();
            //Log::info(message: $auxmoneystorage);

            DB::beginTransaction();
            try {

                foreach ($auxmoneystorage as $item) {
                    $existingRecord = DB::table('auxmoneystorage')
                        ->where('local_id', $local->id)
                        ->where('Machine', $item->Machine)
                        ->where('TypeIsAux', $item->TypeIsAux)
                        ->where('AuxName', $item->AuxName)
                        ->first();

                    if (!$existingRecord) {
                        DB::table('auxmoneystorage')->insert([
                            'local_id' => $local->id,
                            'Machine' => $item->Machine,
                            'TypeIsAux' => $item->TypeIsAux,
                            'AuxName' => $item->AuxName,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        //Log::info('Registro insertado:', ['local_id' => $local->id, 'Machine' => $item->Machine]);
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
