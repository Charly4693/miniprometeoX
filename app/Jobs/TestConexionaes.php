<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class TestConexionaes implements ShouldQueue
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
            Artisan::call('check-synchronization-servidores');
        } catch (\Exception $e) {
            // Registrar el error en los logs
            Log::error('Error ejecutando check-synchronization-servidores: ' . $e->getMessage());
        }
    }
}
