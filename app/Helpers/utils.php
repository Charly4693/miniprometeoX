<?php

use App\Models\Local;
use App\Models\Zone;
use App\Models\User;
use App\Models\Delegation;
use App\Models\Company;
use App\Models\Job;
use App\Models\Acumulado;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;


// IP y Puerto prometeo Principal

define('PROMETEO_PRINCIPAL_IP', "192.168.1.41");
define('PROMETEO_PRINCIPAL_PORT', "8000");

// Funciones para manejar el estado de las conexiones
// variable $estadoConexiones - es array con estados
function setEstadoConexiones($estadoConexiones)
{
    Log::info('utils:', $estadoConexiones);
    Cache::put('conexiones', $estadoConexiones);
}

function getEstadoConexiones()
{
    return Cache::get('conexiones') ?? []; // Garantiza un array, incluso si la caché es null
}

// functioes para controlar el tiempo de la ultima verificacón de conexiones
// variable $lastTime - es tiempo de ultimo conexion

function setTimeConexiones($lastTime)
{
    Cache::put('lastTime', $lastTime);
}

function getTimeConexiones()
{
    return Cache::get('lastTime', 0); // Devuelve 0 si no existe
}

// function para hacer nuevo conexion
// @return nombre de conexion  $connectionName
function nuevaConexion($local)
{
    $localDate = Local::find($local);
    $connectionName = 'mariadb';

    // Decodificar el JSON de la conexión y obtener la primera conexión (índice 0)
    $datosConexion = json_decode($localDate->dbconection);

    $conexionCero = $datosConexion[0];  // Asegúrate de que el JSON sea un array y accede al primer elemento

    // Modificar la configuración de la conexión de base de datos
    config([
        'database.connections.' . $connectionName . '.host' => $conexionCero->ip,
        'database.connections.' . $connectionName . '.port' => $conexionCero->port,
        'database.connections.' . $connectionName . '.database' => $conexionCero->database,
        'database.connections.' . $connectionName . '.username' => $conexionCero->username,
        'database.connections.' . $connectionName . '.password' => $conexionCero->password,

    ]);

    // Limpiar la conexión para que se apliquen los nuevos valores
    DB::purge($connectionName);

    return $connectionName;
}

//


// function para conexion depende de nombre usuario
// @return nombre de conexion  $connectionName

function nuevaConexionLocal($name)
{
    $user = User::where('name', $name)->first();
    $connectionName = 'mariadb' . '-' . $name;

    if ($name === 'admin') {
        $database = 'comdata';
    } else {
        $database = "ticketserver";
    }

    $passDecrypt = Crypt::decryptString($user->password);
    Log::info($passDecrypt);
    DB::purge($connectionName);

    try {
        // Modificar la configuración de la conexión de base de datos
        config([
            'database.connections.' . $connectionName . '.host' => $user->ip,
            'database.connections.' . $connectionName . '.port' => $user->port,
            'database.connections.' . $connectionName . '.database' => $database,
            'database.connections.' . $connectionName . '.username' => $user->name,
            'database.connections.' . $connectionName . '.password' => $passDecrypt,
            'database.connections.' . $connectionName . '.driver' => 'mysql'
        ]);

        $config = config('database.connections' . $database);
    } catch (\Exception $e) {
        Log::info($e);
    }

    return $connectionName;
}


// function para obtener serial numer de Procesador
// @return serial number $serial o null
function getSerialNumber(): string
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $serial = shell_exec('wmic cpu get ProcessorId');

        // Limpiar el serial eliminando saltos de línea y espacios extra
        $serial = preg_replace('/\s+/', ' ', trim($serial));

        // Explodemos el serial por el espacio para obtener la parte deseada
        $parts = explode(' ', $serial);

        // Extraemos el primer valor que debería ser el ProcessorId
        $processorId = $parts[1]; // Asumiendo que el ProcessorId es el segundo elemento

        return $processorId;
    }

    // Para Linux
    /*elseif (strtoupper(substr(PHP_OS, 0, 6)) === 'LINUX') {
        // Esto es solo para pruebas
        $output = shell_exec('sudo /usr/sbin/dmidecode -t system | grep Serial');

        if ($output) {
            preg_match('/Serial Number:\s*([a-zA-Z0-9]+)/', $output, $matches);
            $serial = isset($matches[1]) ? trim($matches[1]) : null;
        } else {
            $serial = null;
        }
        return trim($serial);
    }*/

    // Para Linux
    elseif (strtoupper(substr(PHP_OS, 0, 6)) === 'LINUX') {
        // Usamos el comando más fiable
        $serial = shell_exec('sudo dmidecode -s system-serial-number');

        if ($serial) {
            $serial = trim($serial);
            //Log::notice("Serial Linux: $serial");
            return $serial;
        }

        //Log::warning("No se pudo obtener el serial en Linux");
        return '';
    }

    //Log::notice("SO no compatible con la obtención del serial.");
    return ''; // Si el SO no es Windows ni Linux, retorna una cadena vacía
}

// Para obtener datos de local, zona, delegation
// @return array $disposision con local, zona, delegation

function getDisposicion()
{
    $locales = Local::all();
    $zones = Zone::all();
    $delegation = Delegation::all();
    $name_zona = "";
    $name_delegation = "";

    if (count($zones) == 1) {
        $name_zona = $zones[0]->name;
    }

    if (count($delegation) == 1) {
        $name_delegation = $delegation[0]->name;
    }

    return $disposicion = [
        'locales' => $locales,
        'name_zona' =>  $name_zona,
        'name_delegation' => $name_delegation
    ];
}

// function para comprobar si hay Compania en BD
// @return nombre company $company o null
function getCompany()
{
    try {
        $company = Company::first();

        if ($company) return $company->name;
        else return null;
    } catch (\Exception $e) {
        Log::info($e);
        return null;
    }
}


// function para conexion con prometeo y comprobar licencia
// @return array [resultado de comprobación, error]
function compartirSerialNumber($serialNumberProcessor, $local_id)
{
    Log::notice('Compartir serial number ' . $serialNumberProcessor . ' --- ' . $local_id);
    try {

        // Probar conexiones con prometeo
        $urlPrometeo = User::where('name', 'prometeo')->first();
        $company = Company::first();
        $local = Local::find($local_id);
        $url = 'http://' . PROMETEO_PRINCIPAL_IP . ':' . PROMETEO_PRINCIPAL_PORT . '/api/verify-licence-company';

        Log::notice($local_id);
        Log::notice($serialNumberProcessor);
        Log::notice($company);
        Log::notice($local);


        try {
            $response = Http::post($url, [
                'local_id' => $local_id,
                'serialNumber' => $serialNumberProcessor,
                'company' => $company->name,
                'local_name' => $local->name
            ]);
            Log::notice($response->body());
        } catch (\Exception $e) {

            Log::info($e);
        }

        $result = $response->json();

        if ($result !== null && $result['success']) {

            return [true, null];
        } else {

            $error = "Serial numero de processador es incorrecto";
            session(['localId' => $local->id, "serialNumberProcessor" => $serialNumberProcessor]);

            return [false, $error];
        }
    } catch (\Illuminate\Database\QueryException $ex) {
        Log::info($ex);
        $error = "No hay conexión.";
        return [false, $error];
    } catch (\Exception $exception) {
        $error = "Hay algun error desconocido";
        return [false, $error];
    }
}


// function para buscar job en la tabla jobs para no repetir
// @$isDuplicate - boolean con resultado comprobacón

function buscarJob($nameJob)
{

    $jobs = Job::all();

    $isDuplicate = false;

    foreach ($jobs as $job) {
        $payload = json_decode($job->payload, true);

        if (isset($payload['data']['commandName']) && $payload['data']['commandName'] === $nameJob) {
            $isDuplicate = true;
            break;
        }
    }

    return $isDuplicate;
}

// function para desconectar todos machines en tabla acumulados cuando no hay conexiones
// @return void

function desconectMachines()
{

    $machinesAcumulados = Acumulado::all();
    // Desconectamos cada machine
    foreach ($machinesAcumulados as $machine) {

        $machine->update(['EstadoMaquina' => 'DESCONECTADA']);
    }
}

/*OBTER IP */
function getRealIpAddr()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]; // Toma solo la primera IP en caso de múltiples proxies
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } else {
        $ip = 'UNKNOWN';
    }

    // Si la IP es 127.0.0.1, intenta obtener la IP real de la red local
    if ($ip == "127.0.0.1" || $ip == "::1") {
        $ip = getHostByName(getHostName());
    }

    return $ip;
}

function GenerateNewNumberFormat($NumberOfDigits)
{
    $number = "";
    for ($i = 0; $i < $NumberOfDigits - 8; $i++) {
        $num = mt_rand() % 10;
        $number .= $num;
    }

    return "00000000" . $number;
}
