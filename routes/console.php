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

// se debe ejecutar al principio "la 1ª vez y luego EveryTime" con miniprometeo
Schedule::job(new MoneySynchronizationJob)->everyThirtySeconds();

// se debe ejecutar al principio "la 1ª vez y luego EveryTime" y cada vez que se cambien las auxiliares
Schedule::job(new MoneySynchronizationAuxMoneyStorageJob)->everyThirtySeconds();

// se ejecuta cada 10 segundos
Schedule::job(new ObtenerDatosTablaAcumulados)->everyTenSeconds();

// se debe ejecutar cada 30 seg
Schedule::job(new MoneySynchronizationEveryTimeJob)->everyThirtySeconds();

// cada 24h o cuando hagan falta
Schedule::job(new MoneySynchronization24hJob)->everyThirtySeconds();

// cada vez que se hagan cambios en la configuracion de la money
Schedule::job(new MoneySynchronizationConfigJob)->everyThirtySeconds();

// se ejecutara siempre con poco tiempo para corregir los fallos del Type y su Alias referente a los tickets y las maquinas
Schedule::job(new FixBugsJob)->everyFiveSeconds();

// TRABAJOS QUE SE DEBEN HACER PARA SINCRONIZAR "MINIPROMETEO CON PROMETEO" ENVIO DE DATOS

// se debe ejecutar cada 30 seg
// Schedule::job(new SendFrequentDataJob)->everyThirtySeconds();

// cada 24h o cuando hagan falta
// Schedule::job(new SendModerateDataJob)->everyThirtySeconds();

// cada vez que se hagan cambios en la configuracion de la money
// Schedule::job(new SendCasualDataJob)->everyThirtySeconds();

