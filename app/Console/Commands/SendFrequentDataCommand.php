<?php

namespace App\Console\Commands;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Models\Acumulado;
use App\Models\CollectDetail;
use App\Models\Collect;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Log as ModelLog; // Alias para el modelo Log

use App\Providers\ApiClient; // Asegúrate de incluir el ApiClient

class SendFrequentDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'miniprometeo:send-frequent-data-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía datos de tablas que necesitan actualización frecuente. Command para seleccionar las tablas que frecuentemente guardara datos en prometeo.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Instanciar el ApiClient
        $apiClient = app(ApiClient::class);
        //Log::info('Instanciando ApiClient', ['apiClient' => json_encode($apiClient)]);

        $user = User::where('name', 'Miniprometeo')->first();
        $password = 'Mini1234';

        if (!$user) {
            $this->error('Usuario no encontrado.');
            return;
        }
        //Log::notice('apiclient ---------------- ' . json_encode($apiClient));
        //Log::info('Usuario encontrado', ['user' => $user]);

        $this->sendCollectDetailsData($apiClient, $user, $password);
        $this->sendCollectsData($apiClient, $user, $password);
        $this->sendLogsData($apiClient, $user, $password);
        $this->sendTicketsData($apiClient, $user, $password);
        $this->sendAcumuladoData($apiClient, $user, $password);

        // hacemos un elif o case para cada command, para que dependiendo del coman que se use enviamos una tablas u otras


    }
    private function sendAcumuladoData(ApiClient $apiClient, User $user, string $password)
    {
        $data = Acumulado::all()->toArray(); // Convertir a array para enviar
        //Log::info('Datos a enviar de Acumulado', ['data' => $data]);

        // Definir el prefijo como el nombre del modelo en minúsculas
        $prefijo = strtolower(class_basename(Acumulado::class)); // 'acumulado'

        // Agregar prefijo
        $prefixedData = [
            "{$prefijo}" => $data,
        ];
        //Log::notice($prefixedData);

        try {
            $response = $apiClient->sendData($user, $password, 'api/save-data-frequent', $prefixedData);
            //Log::info('Respuesta del servidor', ['response' => $response]);

            if ($response) {
                $this->info('Datos de Acumulado enviados con éxito.');
            } else {
                $this->error('Error al enviar los datos de Acumulado.');
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar datos de Acumulado', ['error' => $e->getMessage()]);
        }
    }


    private function sendCollectDetailsData(ApiClient $apiClient, User $user, string $password)
    {
        $data = CollectDetail::all()->toArray(); // Convertir a array para enviar
        //Log::info('Datos a enviar de CollectDetail', ['data' => $data]);

        // Definir el prefijo como el nombre del modelo en minúsculas
        $prefijo = strtolower(class_basename(CollectDetail::class)); // 'collectdetail'

        // Agregar prefijo
        $prefixedData = [
            "{$prefijo}" => $data,
        ];
        //Log::notice($prefixedData);
        try {
            $response = $apiClient->sendData($user, $password, 'api/save-data-frequent', $prefixedData);
            //Log::info('Respuesta del servidor', ['response' => $response]);

            if ($response) {
                $this->info('Datos de CollectDetail enviados con éxito.');
            } else {
                $this->error('Error al enviar los datos de CollectDetail.');
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar datos de CollectDetail', ['error' => $e->getMessage()]);
        }
    }


    private function sendCollectsData(ApiClient $apiClient, User $user, string $password)
    {
        $data = Collect::all()->toArray(); // Convertir a array para enviar
        //Log::info('Datos a enviar de Collect', ['data' => $data]);

        // Definir el prefijo como el nombre del modelo en minúsculas
        $prefijo = strtolower(class_basename(Collect::class)); // 'collect'

        // Agregar prefijo
        $prefixedData = [
            "{$prefijo}" => $data,
        ];
        //Log::notice($prefixedData);
        //Log::notice($prefixedData);

        try {
            $response = $apiClient->sendData($user, $password, 'api/save-data-frequent', $prefixedData);
            //Log::info('Respuesta del servidor', ['response' => $response]);

            if ($response) {
                $this->info('Datos de Collect enviados con éxito.');
            } else {
                $this->error('Error al enviar los datos de Collect.');
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar datos de Collect', ['error' => $e->getMessage()]);
        }
    }


    private function sendLogsData(ApiClient $apiClient, User $user, string $password)
    {
        $data = ModelLog::all()->toArray(); // Convertir a array para enviar
        //Log::info('Datos a enviar de Log', ['data' => $data]);

        // Definir el prefijo como el nombre del modelo en minúsculas
        $prefijo = strtolower(class_basename(Log::class)); // 'log'

        // Agregar prefijo
        $prefixedData = [
            "{$prefijo}" => $data,
        ];
        //Log::notice($prefixedData);

        try {
            $response = $apiClient->sendData($user, $password, 'api/save-data-frequent', $prefixedData);
            //Log::info('Respuesta del servidor', ['response' => $response]);

            if ($response) {
                $this->info('Datos de Log enviados con éxito.');
            } else {
                $this->error('Error al enviar los datos de Log.');
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar datos de Log', ['error' => $e->getMessage()]);
        }
    }


    private function sendTicketsData(ApiClient $apiClient, User $user, string $password)
    {
        $data = Ticket::all()->toArray(); // Convertir a array para enviar
        //Log::info('Datos a enviar de Ticket', ['data' => $data]);

        // Definir el prefijo como el nombre del modelo en minúsculas
        $prefijo = strtolower(class_basename(Ticket::class)); // 'ticket'

        // Agregar prefijo
        $prefixedData = [
            "{$prefijo}" => $data,
        ];
        //Log::notice($prefixedData);

        try {
            $response = $apiClient->sendData($user, $password, 'api/save-data-frequent', $prefixedData);
            //Log::info('Respuesta del servidor', ['response' => $response]);

            if ($response) {
                $this->info('Datos de Ticket enviados con éxito.');
            } else {
                $this->error('Error al enviar los datos de Ticket.');
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar datos de Ticket', ['error' => $e->getMessage()]);
        }
    }
}
