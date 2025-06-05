<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Machine;
use App\Models\Config;
use App\Models\Local;
use App\Models\User;

class PerformSyncTypesTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'miniprometeo:perform-sync-types-tickets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza los tipos de tickets con los alias de las maquinas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::where('name', 'ccm')->first();
        $connectionName = nuevaConexionLocal($user->name);

        try {
            DB::connection($connectionName)->beginTransaction();
            //Log::info("Conexión establecida y transacción iniciada.", ['conexion' => $connectionName]);

            // Obtener los tipos de máquinas sin usar GROUP BY
            $tipos = Machine::select('alias')->distinct()->get();

            $config = Config::first();
            if (!$config) {
                throw new \Exception("No se encontró configuración en la base de datos.");
            }

            foreach ($tipos as $tipo) {
                $this->insertTicket($connectionName, $config, $tipo);
            }

            DB::connection($connectionName)->commit();
            //Log::info("Sincronización completada exitosamente.");

            return 0; // Éxito
        } catch (\Exception $e) {
            DB::connection($connectionName)->rollBack();
            Log::error("Error durante la sincronización: " . $e->getMessage(), ['trace' => $e->getTrace()]);

            return 1; // Error
        }
    }



    private function insertTicket($database, $config, $tipo)
    {
        $ticketNumber = $this->generateNewNumberFormat($config->NumberOfDigits);
        $realIp = $this->getRealIpAddr();

        // Buscar el usuario 'miniprometeo'
        $user = User::where('name', 'miniprometeo')->first();

        // Si no se encuentra, usar un valor por defecto
        $userName = $user ? $user->name : 'Prometeo';

        $insertData = [
            'Command' => 'ABORT',
            'TicketNumber' => $ticketNumber,
            'Mode' => 'webPost',
            'DateTime' => now(),
            'LastCommandChangeDateTime' => now(),
            'LastIP' => $realIp,
            'LastUser' => $userName,
            'Value' => 1,
            'Residual' => 0,
            'IP' => $realIp,
            'User' => $userName,
            'Comment' => 'Creado mediante ' . $userName,
            'Type' => $tipo->alias,
            'TypeIsBets' => 0,
            'TypeIsAux' => 1,
            'HideOnTC' => 0,
            'Used' => 0,
            'TITOExpirationType' => 0,
        ];

        // Verificar si ya existe un ticket con el mismo Type (alias)
        $existingTicket = DB::connection($database)->table('tickets')
            ->where('Type', $tipo->alias)
            ->exists();

        if ($existingTicket) {
            //Log::info("Ya existe un ticket con el Type '{$tipo->alias}', no se insertará otro.");
            return;
        }

        try {
            DB::connection($database)->table('tickets')->insert($insertData);
            //Log::info("Ticket insertado correctamente en la base de datos.", ['database' => $database, 'ticket' => $insertData]);
        } catch (\Exception $e) {
            Log::error("Error al insertar ticket: " . $e->getMessage(), ['ticket' => $insertData]);
            throw $e;
        }
    }



    private function generateNewNumberFormat($digits)
    {
        return str_pad(mt_rand(1, pow(10, $digits) - 1), $digits, '0', STR_PAD_LEFT);
    }

    private function getRealIpAddr()
    {
        if (php_sapi_name() === 'cli') {
            return '127.0.0.1';
        }
        return request()->ip() ?? '127.0.0.1';
    }
}
