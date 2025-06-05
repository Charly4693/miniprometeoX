<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Models\AuxMoneyStorage;
use App\Models\AuxMoneyStorageInfo;
use App\Models\Machine;
use App\Models\User; // Necesario para obtener el usuario
use App\Providers\ApiClient;

class SendModerateDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'miniprometeo:send-moderate-data-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía datos de tablas que necesitan actualización moderada. Command para seleccionar las tablas que moderadamente guardara datos en prometeo.';

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
        $this->sendAuxMoneyStorageData($apiClient, $user, $password);
        $this->sendAuxMoneyStorageInfoData($apiClient, $user, $password);

        // las Machines siempre se enviaran desde prometeo a miniprometeo, miniprometeo no puede guardar las machines en prometeo si no al reves
        //$this->sendMachinesData($apiClient, $user, $password);


        // hacemos un elif o case para cada command, para que dependiendo del coman que se use enviamos una tablas u otras


    }
    private function sendAuxMoneyStorageData(ApiClient $apiClient, User $user, string $password)
    {
        $data = AuxMoneyStorage::all()->toArray(); // Convertir a array para enviar
        //Log::info('Datos a enviar de AuxMoneyStorage', ['data' => $data]);

        // Definir el prefijo como el nombre del modelo en minúsculas
        $prefijo = strtolower(class_basename(AuxMoneyStorage::class)); // 'auxmoneystorage'

        // Agregar prefijo
        $prefixedData = [
            "{$prefijo}" => $data,
        ];
        //Log::notice($prefixedData);

        try {
            $response = $apiClient->sendData($user, $password, 'api/save-data-moderate', $prefixedData);
            //Log::info('Respuesta del servidor', ['response' => $response]);

            if ($response) {
                $this->info('Datos de AuxMoneyStorage enviados con éxito.');
            } else {
                $this->error('Error al enviar los datos de AuxMoneyStorage.');
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar datos de AuxMoneyStorage', ['error' => $e->getMessage()]);
        }
    }
    private function sendAuxMoneyStorageInfoData(ApiClient $apiClient, User $user, string $password)
    {
        $data = AuxMoneyStorageInfo::all()->toArray(); // Convertir a array para enviar
        //Log::info('Datos a enviar de AuxMoneyStorageInfo', ['data' => $data]);

        // Definir el prefijo como el nombre del modelo en minúsculas
        $prefijo = strtolower(class_basename(AuxMoneyStorageInfo::class)); // 'auxmoneystorageinfo'

        // Agregar prefijo
        $prefixedData = [
            "{$prefijo}" => $data,
        ];
        //Log::notice($prefixedData);

        try {
            $response = $apiClient->sendData($user, $password, 'api/save-data-moderate', $prefixedData);
            //Log::info('Respuesta del servidor', ['response' => $response]);

            if ($response) {
                $this->info('Datos de AuxMoneyStorageInfo enviados con éxito.');
            } else {
                $this->error('Error al enviar los datos de AuxMoneyStorageInfo.');
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar datos de AuxMoneyStorageInfo', ['error' => $e->getMessage()]);
        }
    }

        // las Machines siempre se enviaran desde prometeo a miniprometeo, miniprometeo no puede guardar las machines en prometeo si no al reves
    private function sendMachinesData(ApiClient $apiClient, User $user, string $password)
    {
        $data = Machine::all()->toArray(); // Convertir a array para enviar
        //Log::info('Datos a enviar de Machine', ['data' => $data]);

        // Definir el prefijo como el nombre del modelo en minúsculas
        $prefijo = strtolower(class_basename(Machine::class)); // 'machine'

        // Agregar prefijo
        $prefixedData = [
            "{$prefijo}" => $data,
        ];
        //Log::notice($prefixedData);

        try {
            $response = $apiClient->sendData($user, $password, 'api/save-data-moderate', $prefixedData);
            //Log::info('Respuesta del servidor', ['response' => $response]);

            if ($response) {
                $this->info('Datos de Machine enviados con éxito.');
            } else {
                $this->error('Error al enviar los datos de Machine.');
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar datos de Machine', ['error' => $e->getMessage()]);
        }
    }
}
