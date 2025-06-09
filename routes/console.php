<?php

use App\Jobs\ObtenerDatosTablaAcumulados;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\MoneySynchronizationEveryTimeJob;
use App\Jobs\MoneySynchronizationJob;
use App\Jobs\MoneySynchronization24hJob;
use App\Jobs\MoneySynchronizationAuxMoneyStorageJob;
use App\Jobs\MoneySynchronizationConfigJob;
use App\Jobs\FixBugsJob;
use App\Jobs\SendCasualDataJob;
use App\Jobs\SendFrequentDataJob;
use App\Jobs\SendModerateDataJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// TRABAJOS QUE SE DEBEN HACER PARA SINCRONIZAR "MONEY CON MINIPROMETEO"

// Estos son los JOBS que trabajamos con el ticketcServer

// se debe ejecutar cada dia a las 9 de la mañana
Schedule::job(new MoneySynchronizationJob)->dailyAt('09:00');

// se debe ejecutar al principio "la 1ª vez y luego EveryTime" y cada vez que se cambien las auxiliares "pero esto desde los botones que hayq en la vista si cambian la auxiliares"
Schedule::job(new MoneySynchronizationAuxMoneyStorageJob)->dailyAt('09:00');

// cada 24h o cuando hagan falta
Schedule::job(new MoneySynchronization24hJob)->dailyAt('09:00');

// cada vez que se hagan cambios en la configuracion de la money
Schedule::job(new MoneySynchronizationConfigJob)->dailyAt('09:00');

// se debe ejecutar cada 30 seg (salvo de 3 a 9)
Schedule::job(new MoneySynchronizationEveryTimeJob)
    ->everyThirtySeconds()
    ->when(fn () => now()->hour >= 9 || now()->hour < 4);

// entre 3 y 9, cada 5 minutos
Schedule::job(new MoneySynchronizationEveryTimeJob)
    ->everyFiveMinutes()
    ->when(fn () => now()->hour >= 4 && now()->hour < 9);

// se ejecutara siempre con poco tiempo para corregir los fallos del Type y su Alias referente a los tickets y las maquinas cada segundo
Schedule::job(new FixBugsJob)->everySecond();

// Estos son los JOBS que trabajamos con el ComData
// Ejecutar cada 10 segundos excepto entre 4 y 9 AM
Schedule::job(new ObtenerDatosTablaAcumulados)
    ->everyTenSeconds()
    ->when(fn () => now()->hour < 4 || now()->hour >= 9);

// Entre 4 y 9 AM, ejecutarlo cada 1 minuto
Schedule::job(new ObtenerDatosTablaAcumulados)
    ->everyMinute()
    ->when(fn () => now()->hour >= 4 && now()->hour < 9);


// TRABAJOS QUE SE DEBEN HACER PARA SINCRONIZAR "MINIPROMETEO CON PROMETEO" ENVIO DE DATOS

// se debe ejecutar cada 30 seg (salvo de 3 a 9)
Schedule::job(new SendFrequentDataJob)
    ->everyThirtySeconds()
    ->when(fn () => now()->hour >= 9 || now()->hour < 4);

// entre 3 y 9, cada 5 minutos
Schedule::job(new SendFrequentDataJob)
    ->everyFiveMinutes()
    ->when(fn () => now()->hour >= 4 && now()->hour < 9);

// cada 24h o cuando hagan falta
Schedule::job(new SendModerateDataJob)->dailyAt('09:00');

// cada vez que se hagan cambios en la configuracion de la money
Schedule::job(new SendCasualDataJob)->dailyAt('09:00');

