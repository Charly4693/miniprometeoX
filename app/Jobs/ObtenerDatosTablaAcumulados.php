<?php

namespace App\Jobs;

use function Pest\version;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ObtenerDatosTablaAcumulados implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Ejecutar el comando Artisan
            Artisan::call('perform-acumulado-synchronization');
        } catch (\Exception $e) {
            // Registrar el error en los logs
            Log::error('Error ejecutando perform-acumulado-synchronization: ' . $e->getMessage());
        }
    }
}
