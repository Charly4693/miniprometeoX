<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class FixBugsJob implements ShouldQueue
{
    use Queueable;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @param array $data
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
            Artisan::call('miniprometeo:fix-bugs-command');
        } catch (\Exception $e) {
            // Registrar el error en los logs
            Log::error('Error ejecutando miniprometeo:fix-bugs-command: ' . $e->getMessage());
        }
    }
}
