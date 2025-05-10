<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use Carbon\Carbon;

class CheckSynchronizationServidores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check-synchronization-servidores';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Script para probar conexiónes con servidores Prometeo, TicketServer y ComDataHost';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {

        global $conexiones;

        // variable que devolvemos de metodo con estados de conexiones
        $conexiones = [false, false, false];

        // Probar conexiones con prometeo
        $urlPrometeo = User::where('name', 'prometeo')->first();
        $url = 'http://' .  $urlPrometeo->ip . ':' . $urlPrometeo->port . '/api/checkConexion';
        try {
            $conPrometeo = Http::get($url);
            if ($conPrometeo) {
                $conexiones[0] = true;
            }
        } catch (\Exception $e) {

            Log::info($e);
        }
        //$this-> changeDatosConexiones ($conexiones);
        // Probamos otro conexiones
        $conexionConComData = nuevaConexionLocal('admin');
        $conexiones[1] = $this->checkConexion($conexionConComData);
        //$this-> changeDatosConexiones ($conexiones);
        $conexionConTicketServer = nuevaConexionLocal('ccm');
        $conexiones[2] = $this->checkConexion($conexionConTicketServer);
        //log::info('3');

        // Guardamos información sobre resultados ultimos conexiones
        //$this-> changeDatosConexiones ($conexiones);

        // Guardamos información sobre resultados ultimos conexiones
        $lastTimeConnexiones = now();
        setTimeConexiones($lastTimeConnexiones);
        setEstadoConexiones($conexiones);
    }


    // function para comprobar conexion. Entra: nobre de conexion y devulve estado
    /* private function checkConexion($nameConexion)
    {
        try {

            DB::connection($nameConexion)->select('SELECT 1');
            // Log::info($name);

            return true;
        } catch (\Exception $e) {
            Log::info($e);
            return false;
        }
    }*/

    // Function para Guardar información sobre resultados ultimos conexiones
    private function changeDatosConexiones($conexiones)
    {
        $lastTimeConnexiones = now();
        setTimeConexiones($lastTimeConnexiones);
        setEstadoConexiones($conexiones);
    }

    private function checkConexion($nameConexion)
    {
        try {
            $config = config('database.connections.' . $nameConexion);

            Log::info("?? Verificando conexión: {$nameConexion}");
            Log::info("?? Detalles de conexión:", [
                'host' => $config['host'] ?? null,
                'port' => $config['port'] ?? null,
                'database' => $config['database'] ?? null,
                'username' => $config['username'] ?? null,
                'password' => $config['password'] ?? 'NO DEFINIDA',
                'driver' => $config['driver'] ?? null
            ]);

            DB::connection($nameConexion)->select('SELECT 1');
            Log::info("? Conexión exitosa: {$nameConexion}");

            return true;
        } catch (\Exception $e) {
            Log::error("? Error al conectar con '{$nameConexion}': " . $e->getMessage());
            return false;
        }
    }
}
