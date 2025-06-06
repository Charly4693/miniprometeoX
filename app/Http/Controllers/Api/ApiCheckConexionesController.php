<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Jobs\TestConexionaes;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ApiCheckConexionesController extends Controller
{
    public function index()
    {

        //Log::info('🔍 Verificando caché antes de obtener conexiones chekConexiones antes del metodo:', ['conexiones' => Cache::get('conexiones')]);

        // Verifica si el job ya está en ejecución
        $isDuplicate = buscarJob('App\\Jobs\\TestConexionaes');
        if (!$isDuplicate) {
            TestConexionaes::dispatch();
        }

        // Obtener estados de conexión y tiempo de última conexión
        $conexiones = getEstadoConexiones();
        //Log::info('Estado de conexiones ApiCheckConexiones despues del metodo:', ['conexiones' => $conexiones]);

        $lastTimeConexiones = getTimeConexiones();

        // Convertir lastTimeConexiones a Carbon
        //$lastTimeCarbon = Carbon::createFromTimestamp($lastTimeConexiones); // Asegúrate de que esto sea correcto

        // Calcular la diferencia en segundos
        //$diferenciaTiempo = now()->diffInSeconds($lastTimeConexiones);
        $diferenciaTiempo = now()->diffInSeconds(Carbon::createFromTimestamp($lastTimeConexiones));


        if ($diferenciaTiempo > 45) desconectMachines(); // si tiempo mas de 45 segundos - desconectamos machines en tabla acumulados

        if ($conexiones[1] === false) desconectMachines(); // si no hay conexiones con COMDATA - desconectamos machines en tabla acumulados

        // Devolvemos datos de tabala acumulado
        try {
            return response()->json(['conexiones' => $conexiones]); // ✅ SIEMPRE DEVUELVE JSON
        } catch (\Exception $e) {
            Log::error('Error con las conexiones');
        }
    }
}
