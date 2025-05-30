<?php

namespace App\Jobs;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class MoneySynchronizationEveryTimeJob implements ShouldQueue
{
    use Queueable;


    /*
        para ejecutarla cada dos por tre con el syncMoney,
        pero no hace falta traer los 15 dias de logs y tickets,
        si no leer en nuestra base de datos por el numero de ticket
        ver si cambia el estado y editarlo y solo traernos los del dia anterior
        por que los restantes 12,12,14...... dias ya los tendremos a no ser
        que se quede algun ticket pendiente o log, por que no sera necesario dulpicar tanto dato cuando ya lo tenemos
    */

    protected $id;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Ejecutar el comando Artisan
            Artisan::call('miniprometeo:perform-money-synchronization-every-time');
        } catch (\Exception $e) {
            // Registrar el error en los logs
            Log::error('Error ejecutando miniprometeo:perform-money-synchronization-every-time: ' . $e->getMessage());
        }
    }
}
