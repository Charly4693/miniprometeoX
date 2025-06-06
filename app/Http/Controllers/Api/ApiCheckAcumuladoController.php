<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;


use App\Jobs\ObtenerDatosTablaAcumulados;

use App\Models\Acumulado;
use App\Models\Job;
use Illuminate\Support\Facades\Cache;

class ApiCheckAcumuladoController extends Controller
{
    public function index()
    {
        //Log::info('🔍 Verificando caché antes de obtener conexiones checkAcumulados antes del metodo:', ['conexiones' => Cache::get('conexiones')]);


        // Probamos si hay el mismo job en cola
        $isDuplicate = buscarJob('App\\Jobs\\ObtenerDatosTablaAcumulados');  // function en util.php

        // Si no existe añadimos nuevo job
        if (!$isDuplicate) {
            ObtenerDatosTablaAcumulados::dispatch();
        }

        // Probamos tiempo de ultimo prueba de conexión
        $lastTimeConexiones = getTimeConexiones(); // tiempo de ultimos pruebos de conexiones
        // Convertir lastTimeConexiones a Carbon
        //$lastTimeCarbon = Carbon::createFromTimestamp($lastTimeConexiones); // Asegúrate de que esto sea correcto

        // Calcular la diferencia en segundos
        //$diferenciaTiempo = now()->diffInSeconds($lastTimeConexiones);
        $diferenciaTiempo = now()->diffInSeconds(Carbon::createFromTimestamp($lastTimeConexiones));

        //Log::info($diferenciaTiempo);
        if ($diferenciaTiempo > 45) desconectMachines(); // si tiempo mas de 45 segundos - desconectamos machines en tabla acumulados

        // Comprobamos estado de conexion con TicketServer
        $conexiones = getEstadoConexiones();   // resultados de ultimos prubos de conexiones
        //Log::info('Estado de conexiones ApiCheckAcumulado despues del metodo:', ['conexiones' => $conexiones]);

        if ($conexiones[1] === false) desconectMachines(); // si no hay conexiones con COMDATA - desconectamos machines en tabla acumulados

        // Devolvemos datos de tabala acumulado
        try {
            //$acumulados = Acumulado::all();
            $acumulados = Acumulado::orderBy('NumPlaca', 'asc')->get();
        } catch (\Exception $e) {
            Log::error('Error de leyendo la tabla Acumulados');
        }
        return $acumulados;
    }
}
