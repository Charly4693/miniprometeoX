<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ConfigMoneyController;
use App\Http\Controllers\ConfigurationTypeAliasController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PedirAyudaController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\ConfigurationAccountantsController;
use App\Http\Controllers\SyncMoneyController;
use App\Http\Controllers\TicketsController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckProcessorSerial;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|
*/

// Breeze auth
require __DIR__ . '/auth.php';

// Pagina Inicio
Route::get('/', function () {
    $error = session()->get('error');
    if (Auth::check()) {
        return redirect()->route('home')->with('error', $error);
    }
    return redirect('/login');
});

// Pagina home
Route::get('/home', [HomeController::class, 'index'])->name('home')->middleware('auth', CheckProcessorSerial::class);

// Gestion de maquinas
Route::get('/machines/search', [MachineController::class, 'search'])->name('machines.search');
Route::resource('machines', MachineController::class)->middleware(['auth']);

// Import datos
Route::get('/import', [ImportController::class, 'index'])->name('import.index')->middleware(['auth']);
Route::get('/import/store', [ImportController::class, 'store'])->name('import.store')->middleware(['auth']);

Route::get('/syncmoney', SyncMoneyController::class);

// Configurationes
Route::post('/configuration/save_company', [ConfiguracionController::class, 'guardarCompania'])->name('configuration.save_company')->middleware(['auth']);
Route::post('/configuration/company', [ConfiguracionController::class, 'guardarDatosCompania'])->name('configuration.company')->middleware(['auth']);
Route::get('/configuration/buscar', [ConfiguracionController::class, 'buscar'])->name('configuration.buscar')->middleware(['auth']);
Route::resource('configuration', ConfiguracionController::class)->names('configuration')->middleware(['auth']);

// configuraciones y cambios de la money
Route::resource('/configurationMoney', ConfigMoneyController::class);
Route::get('/sync-auxiliares', [ConfigMoneyController::class, 'syncAuxiliares'])->name('sync.auxiliares');
Route::get('/sync-config', [ConfigMoneyController::class, 'syncConfig'])->name('sync.config');
Route::get('/sync-hcinfo', [ConfigMoneyController::class, 'syncHcInfo'])->name('sync.hcinfo');
Route::get('/sync-acumulados', [ConfigMoneyController::class, 'syncAcumulados'])->name('sync.acumulados');

// configuraciones ComData
Route::resource('/configurationAccountants', ConfigurationAccountantsController::class);
Route::post('/configurationAccountants/storeAll', [ConfigurationAccountantsController::class, 'storeAll'])->name('configurationAccountants.storeAll');
Route::post('/configurationAccountants/clearAll', [ConfigurationAccountantsController::class, 'clearAll']);

// configuraciones de type/alias  para los tipos de tickets vayan asociados a una maquina con su alias en Type de tickets "Type(ticket)"="Machine(alias)"
Route::resource('/configurationTypeAlias', ConfigurationTypeAliasController::class);

// enviar las auxiliares al archivo de texto de ticketServer
Route::post('/send-auxiliares', [MachineController::class, 'sendAuxiliares'])->name('sendAuxiliares');

// traer datos de CLIENT ruta de pruebas
Route::post('/getDataClient', [ConfiguracionController::class, 'getDataClient']);
Route::delete('/clients/{id}', action: [ClientController::class, 'destroy'])->name('clients.destroy');
//Route::delete('/clients/{id}', [ConfiguracionController::class, 'destroyClient'])->name('clients.destroy');

// para guardar los datos que recibimos de la peticion
Route::post('/saveClientData', [ConfiguracionController::class, 'saveClientData']);

Route::get('syncTypesTickets', [MachineController::class, 'syncTypesTickets'])->name('syncTypesTickets');

// tickets
Route::resource('tickets', TicketsController::class);
Route::post('abortTicket/{local}', [TicketsController::class, 'abortTickets'])->name('abortTicket');
Route::post('confirmTicket/{local}', [TicketsController::class, 'confirmTicket'])->name('confirmTicket');
Route::post('generarTicket/{local}', [TicketsController::class, 'generarTicket'])->name('generarTicket');

use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

Route::post('/test-connection', function (Request $request) {
    $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
        'ip_address' => 'required|ip',
    ]);

    $username = escapeshellarg($request->input('username'));
    $password = escapeshellarg($request->input('password'));
    $ip = $request->input('ip_address');

    Log::info("🔹 Probando conexión con IP: {$ip}");

    $driveLetter = "Z:";
    $networkPath = "\\\\{$ip}\\Gistra";
    $sharedPath = "{$driveLetter}\\SMI2000\\Setup-TicketController\\TicketControllerPreferences.cfg";

    // Desmontar cualquier unidad previa
    exec("net use {$driveLetter} /delete /y");

    // Intentar conectar
    $command = "net use {$driveLetter} \"{$networkPath}\" /user:{$username} {$password}";
    exec($command, $output, $result);

    if ($result !== 0) {
        return response()->json(['error' => 'No se pudo conectar a la carpeta compartida.'], 500);
    }

    // Verificar que el archivo existe
    if (!File::exists($sharedPath)) {
        return response()->json(['error' => 'Archivo no encontrado.'], 404);
    }

    return response()->json(['success' => 'Conexión y acceso al archivo exitosos.']);
});
