<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Local;
use App\Models\User;
use App\Models\Job;
use Carbon\Carbon;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Jobs\TestConexionaes;

class ApiCheckConexionesController extends Controller
{
    public function index () {

        // Probamos si hay el mismo job en cola
        $isDuplicate = buscarJob('App\\Jobs\\TestConexionaes');  // function en util.php

        // Si no existe añadimos nuevo job
        if (!$isDuplicate) {
            TestConexionaes::dispatch();
        }

        $conexiones = getEstadoConexiones();   // resultados de ultimos prubos de conexiones
        $lastTimeConexiones = getTimeConexiones(); // tiempo de ultimos prubos de conexiones
        Log::info($lastTimeConexiones);
        Log::info(now());

        //$lastTimeCarbon = Carbon::createFromTimestamp($lastTimeConexiones); // Asegúrate de que esto sea correcto

        // Calcular la diferencia en segundos
        //$diferenciaTiempo = now()->diffInSeconds($lastTimeConexiones);
        $diferenciaTiempo = now()->diffInSeconds(Carbon::createFromTimestamp($lastTimeConexiones));


        Log::info($diferenciaTiempo);

        if ($diferenciaTiempo < -45) return null;
        if (!$conexiones) return $conexiones = [false, false, false];
        Log::info('conexiones'.$conexiones);
        return $conexiones;


    }

}
