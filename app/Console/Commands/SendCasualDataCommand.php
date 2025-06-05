<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Models\CollectDetailsInfo;
use App\Models\CollectInfo;
use App\Models\HcMoneyStorage;
use App\Models\HcMoneyStorageInfo;
use App\Models\BetMoneyStorage;
use App\Models\BetMoneyStorageInfo;
use App\Models\Config;
use App\Models\Session;
use App\Models\SessionsTicketServer;
use App\Models\User; // Necesario para obtener el usuario
use App\Providers\ApiClient;

class SendCasualDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'miniprometeo:send-casual-data-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía datos de tablas que necesitan actualización casual. Command para seleccionar las tablas que casualmente guardara datos en prometeo.';

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
        $this->sendCollectDetailsInfoData($apiClient, $user, $password);
        $this->sendCollectInfoData($apiClient, $user, $password);
        $this->sendHcMoneyStorageData($apiClient, $user, $password);
        $this->sendHcMoneyStorageInfoData($apiClient, $user, $password);
        $this->sendBetMoneyStorageData($apiClient, $user, $password);
        $this->sendBetMoneyStorageInfoData($apiClient, $user, $password);
        $this->sendConfigData($apiClient, $user, $password);
        //$this->sendSessionsData($apiClient, $user, $password);
        //esta para enviar los datos y procesarlos en el servidor,
        //falta parte del servidor, pero de momenso lo dejamos sin funcionar

        // hacemos un elif o case para cada command, para que dependiendo del coman que se use enviamos una tablas u otras


    }

    // haremos un metodo por cada MODELO para controlar sus datos y luego un metodo en conjunto para separarlo por comands y las tablas correspondientes a cada command

    private function sendCollectDetailsInfoData(ApiClient $apiClient, User $user, string $password)
    {
        $data = CollectDetailsInfo::all()->toArray();
        //Log::info('Datos a enviar de CollectDetailsInfo', ['data' => $data]);

        // Definir el prefijo como el nombre del modelo en minúsculas
        $prefijo = strtolower(class_basename(CollectDetailsInfo::class)); // 'collectdetailsinfo'

        // Agregar prefijo
        $prefixedData = [
            "{$prefijo}" => $data, // Aquí el prefijo es solo el nombre del modelo
        ];
        //Log::notice($prefixedData);
        try {
            $response = $apiClient->sendData($user, $password, 'api/save-data-casual', $prefixedData);
            //Log::info('Respuesta del servidor', ['response' => $response]);

            if ($response) {
                $this->info('Datos de CollectDetailsInfo enviados con éxito.');
            } else {
                $this->error('Error al enviar los datos de CollectDetailsInfo.');
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar datos de CollectDetailsInfo ', ['error' => $e->getMessage()]);
        }
    }

    private function sendCollectInfoData(ApiClient $apiClient, User $user, string $password)
    {
        $data = CollectInfo::all()->toArray(); // Convertir a array para enviar
        //Log::info('Datos a enviar de CollectInfo', ['data' => $data]);

        // Definir el prefijo como el nombre del modelo en minúsculas
        $prefijo = strtolower(class_basename(CollectInfo::class)); // 'collectinfo'

        // Agregar prefijo
        $prefixedData = [
            "{$prefijo}" => $data, // Aquí el prefijo es solo el nombre del modelo
        ];
        //Log::notice($prefixedData);
        try {
            $response = $apiClient->sendData($user, $password, 'api/save-data-casual', $prefixedData);
            //Log::info('Respuesta del servidor', ['response' => $response]);

            if ($response) {
                $this->info('Datos de CollectInfo enviados con éxito.');
            } else {
                $this->error('Error al enviar los datos de CollectInfo.');
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar datos de CollectInfo ', ['error' => $e->getMessage()]);
        }
    }



    private function sendHcMoneyStorageData(ApiClient $apiClient, User $user, string $password)
    {
        $data = HcMoneyStorage::all()->toArray(); // Convertir a array para enviar
        //Log::info('Datos a enviar de HcMoneyStorage', ['data' => $data]);

        // Definir el prefijo como el nombre del modelo en minúsculas
        $prefijo = strtolower(class_basename(HcMoneyStorage::class)); // 'hcmoneystorage'

        // Agregar prefijo
        $prefixedData = [
            "{$prefijo}" => $data,
        ];
        //Log::notice($prefixedData);

        try {
            $response = $apiClient->sendData($user, $password, 'api/save-data-casual', $prefixedData);
            //Log::info('Respuesta del servidor', ['response' => $response]);

            if ($response) {
                $this->info('Datos de HcMoneyStorage enviados con éxito.');
            } else {
                $this->error('Error al enviar los datos de HcMoneyStorage.');
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar datos de HcMoneyStorage', ['error' => $e->getMessage()]);
        }
    }

    private function sendHcMoneyStorageInfoData(ApiClient $apiClient, User $user, string $password)
    {
        $data = HcMoneyStorageInfo::all()->toArray(); // Convertir a array para enviar
        //Log::info('Datos a enviar de HcMoneyStorageInfo', ['data' => $data]);

        // Definir el prefijo como el nombre del modelo en minúsculas
        $prefijo = strtolower(class_basename(HcMoneyStorageInfo::class)); // 'hcmoneystorageinfo'

        // Agregar prefijo
        $prefixedData = [
            "{$prefijo}" => $data,
        ];
        //Log::notice($prefixedData);

        try {
            $response = $apiClient->sendData($user, $password, 'api/save-data-casual', $prefixedData);
            //Log::info('Respuesta del servidor', ['response' => $response]);

            if ($response) {
                $this->info('Datos de HcMoneyStorageInfo enviados con éxito.');
            } else {
                $this->error('Error al enviar los datos de HcMoneyStorageInfo.');
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar datos de HcMoneyStorageInfo', ['error' => $e->getMessage()]);
        }
    }


    private function sendBetMoneyStorageData(ApiClient $apiClient, User $user, string $password)
    {
        $data = BetMoneyStorage::all()->toArray(); // Convertir a array para enviar
        //Log::info('Datos a enviar de BetMoneyStorage', ['data' => $data]);

        // Definir el prefijo como el nombre del modelo en minúsculas
        $prefijo = strtolower(class_basename(BetMoneyStorage::class)); // 'betmoneystorage'

        // Agregar prefijo
        $prefixedData = [
            "{$prefijo}" => $data,
        ];
        //Log::notice($prefixedData);

        try {
            $response = $apiClient->sendData($user, $password, 'api/save-data-casual', $prefixedData);
            //Log::info('Respuesta del servidor', ['response' => $response]);

            if ($response) {
                $this->info('Datos de BetMoneyStorage enviados con éxito.');
            } else {
                $this->error('Error al enviar los datos de BetMoneyStorage.');
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar datos de BetMoneyStorage', ['error' => $e->getMessage()]);
        }
    }

    private function sendBetMoneyStorageInfoData(ApiClient $apiClient, User $user, string $password)
    {
        $data = BetMoneyStorageInfo::all()->toArray(); // Convertir a array para enviar
        //Log::info('Datos a enviar de BetMoneyStorageInfo', ['data' => $data]);

        // Definir el prefijo como el nombre del modelo en minúsculas
        $prefijo = strtolower(class_basename(BetMoneyStorageInfo::class)); // 'betmoneystorageinfo'

        // Agregar prefijo
        $prefixedData = [
            "{$prefijo}" => $data,
        ];
        //Log::notice($prefixedData);

        try {
            $response = $apiClient->sendData($user, $password, 'api/save-data-casual', $prefixedData);
            //Log::info('Respuesta del servidor', ['response' => $response]);

            if ($response) {
                $this->info('Datos de BetMoneyStorageInfo enviados con éxito.');
            } else {
                $this->error('Error al enviar los datos de BetMoneyStorageInfo.');
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar datos de BetMoneyStorageInfo', ['error' => $e->getMessage()]);
        }
    }


    private function sendConfigData(ApiClient $apiClient, User $user, string $password)
    {
        $data = Config::all()->toArray(); // Convertir a array para enviar
        //Log::info('Datos a enviar de Config', ['data' => $data]);

        // Definir el prefijo como el nombre del modelo en minúsculas
        $prefijo = strtolower(class_basename(Config::class)); // 'config'

        // Agregar prefijo
        $prefixedData = [
            "{$prefijo}" => $data,
        ];
        //Log::notice($prefixedData);

        try {
            $response = $apiClient->sendData($user, $password, 'api/save-data-casual', $prefixedData);
            //Log::info('Respuesta del servidor', ['response' => $response]);

            if ($response) {
                $this->info('Datos de Config enviados con éxito.');
            } else {
                $this->error('Error al enviar los datos de Config.');
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar datos de Config', ['error' => $e->getMessage()]);
        }
    }

    private function sendSessionsData(ApiClient $apiClient, User $user, string $password)
    {
        $data = Session::all()->toArray(); // Convertir a array para enviar
        //Log::info('Datos a enviar de Session', ['data' => $data]);

        // Definir el prefijo como el nombre del modelo en minúsculas
        $prefijo = strtolower(class_basename(Session::class)); // 'session'

        // Agregar prefijo
        $prefixedData = [
            "{$prefijo}" => $data,
        ];
        //Log::notice($prefixedData);

        try {
            $response = $apiClient->sendData($user, $password, 'api/save-data-casual', $prefixedData);
            //Log::info('Respuesta del servidor', ['response' => $response]);

            if ($response) {
                $this->info('Datos de Session enviados con éxito.');
            } else {
                $this->error('Error al enviar los datos de Session.');
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar datos de Sessions', ['error' => $e->getMessage()]);
        }
    }
}
