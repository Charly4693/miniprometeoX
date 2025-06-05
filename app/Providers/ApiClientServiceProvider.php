<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Providers\ApiClient;
use App\Models\User;
use Illuminate\Support\Facades\Log;


class ApiClientServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Vincular ApiClient como un singleton
        $this->app->singleton(ApiClient::class, function ($app) {
            //Log::info('Intentando obtener el usuario Miniprometeo.');

            // Obtener el usuario 'Miniprometeo'
            $user = User::where('name', 'Miniprometeo')->first();

            if ($user) {
                //Log::info('Usuario Miniprometeo encontrado.', ['user_id' => $user->id,'name' => $user->name]);

                // Crear la instancia de ApiClient
                //Log::info('Creando instancia de ApiClient.', ['baseUrl' => config('app.api_server_url'),'clientId' => config('app.passport_client_id'),'clientSecret' => config('app.passport_client_secret')]);

                return new ApiClient(
                    config('app.api_server_url'),        // URL del servidor API desde config/app.php o .env
                    config('app.passport_client_id'),   // ID del cliente Passport
                    config('app.passport_client_secret'), // Secreto del cliente Passport
                    $user                                // Pasar el objeto User
                );
            }

            // Manejar el caso en que el usuario no exista
            Log::error('Usuario Miniprometeo no encontrado.');
            throw new \Exception('Usuario Miniprometeo no encontrado.');
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
