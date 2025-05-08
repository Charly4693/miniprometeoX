<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Local;
use App\Models\Zone;
use App\Models\Delegation;
use App\Models\Company;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\Client;



class ConfiguracionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user_prometeo = User::where('name', 'prometeo')->first();
        $user_cambio = User::where('name', 'ccm')->first();
        $user_comDataHost = User::where('name', 'admin')->first();

        $users = User::whereNotNull('email')
            ->where('email', '!=', '')
            ->with('clients') // Cargar la relación clients
            ->get();
        // Obtener datos de Local, zona, delegacion
        $disposicion = getDisposicion();

        // Obtener nombre de compania
        $company = getCompany();

        $data = [
            'user_prometeo' => $user_prometeo,
            'user_cambio' => $user_cambio,
            'user_comDataHost' => $user_comDataHost,
            'users' => $users,
            'locales' => $disposicion['locales'],
            'name_zona' =>  $disposicion['name_zona'],
            'name_delegation' => $disposicion['name_delegation'],
            'company' => $company
        ];

        //dd($data['users']);

        // Enviar IP Prometeo Principal

        session()->flash('PROMETEO_PRINCIPAL_IP', PROMETEO_PRINCIPAL_IP);
        session()->flash('PROMETEO_PRINCIPAL_PORT', PROMETEO_PRINCIPAL_PORT);

        // Pasar la variable $data a la vista
        return view('configuration.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'ip_prometeo' => ['required', 'ipv4'],
            'port_prometeo' => ['required', 'numeric', 'max:65535'],
            'ip_cambio' => ['required', 'ipv4'],
            'port_cambio' => ['required', 'numeric', 'max:65535'],
            'ip_comdatahost' => ['required', 'ipv4'],
            'port_comdatahost' => ['required', 'numeric', 'max:65535'],
            'locales' => ['required']
        ], [
            'ip_prometeo.required' => 'Este campo es obligatorio.',
            'port_prometeo.required' => 'Este campo es obligatorio.',
            'ip_cambio.required' => 'Este campo es obligatorio.',
            'port_cambio.required' => 'Este campo es obligatorio.',
            'ip_comdatahost.required' => 'Este campo es obligatorio.',
            'port_comdatahost.required' => 'Este campo es obligatorio.',
            'ip_prometeo.ipv4' => 'En este campo solo IP',
            'ip_cambio.ipv4' => 'En este campo solo IP',
            'ip_comdatahost.ipv4' => 'En este campo solo IP',
            'port_prometeo.numeric' => 'En este campo solo dígitos',
            'port_cambio.numeric' => 'En este campo solo dígitos',
            'port_cambio.min' => 'Número de puerto muy grande',
            'port_comdatahost.numeric' => 'En este campo solo dígitos',
            'port_comdatahost.min' => 'Número de puerto muy grande',
            'locales.required' => 'Este campo es obligatorio.'
        ]);

        try {
            $data = $request->except('_token');

            // Guardamos datos de servidores
            User::where('name', 'prometeo')->update([
                'ip' => $data['ip_prometeo'],
                'port' => $data['port_prometeo']
            ]);
            User::where('name', 'ccm')->update([
                'ip' => $data['ip_cambio'],
                'port' => $data['port_cambio']
            ]);
            User::where('name', 'admin')->update([
                'ip' => $data['ip_comdatahost'],
                'port' => $data['port_comdatahost']
            ]);

            $serialNumberProcessor = getSerialNumber();
            Log::notice($serialNumberProcessor);
            // Comprobar serial Number
            $checkSerialNumber = compartirSerialNumber($serialNumberProcessor, $data['locales']);

            if ($checkSerialNumber !== null && $checkSerialNumber[0]) {
                try {
                    DB::beginTransaction();
                    $local = Local::find($data['locales']);
                    $zone = Zone::find($local->zone_id);
                    $delegation = Delegation::find($zone->delegation_id);

                    $localesParaEliminar = Local::where('id', '!=', $local->id)->get();
                    $zonesParaEliminar = Zone::where('id', '!=', $zone->id)->get();
                    $delegationsParaEliminar = Delegation::where('id', '!=', $delegation->id)->get();

                    DB::statement('SET FOREIGN_KEY_CHECKS=0');
                    foreach ($localesParaEliminar as $loc) {
                        $loc->delete();
                    }
                    foreach ($zonesParaEliminar as $zon) {
                        $zon->delete();
                    }
                    foreach ($delegationsParaEliminar as $del) {
                        $del->delete();
                    }
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');

                    DB::commit();

                    // Mensaje de éxito
                    session()->flash('success', 'Configuración actualizada exitosamente.');
                } catch (\Exception $exception) {
                    DB::rollBack();
                    //Log::error('Error en la transacción de base de datos: ' . $exception->getMessage());

                    // Mensaje de error
                    session()->flash('error', 'Error al actualizar la configuración. Inténtelo nuevamente.');
                    return redirect()->back();
                }
            } else {
                session()->flash('error', 'Error de configuración. Póngase en contacto con servicios técnicos.');
                return redirect()->back();
            }
        } catch (\Exception $exception) {
            //Log::error('Error general en la actualización: ' . $exception->getMessage());

            // Mensaje de error general
            session()->flash('error', 'Ocurrió un error inesperado. Inténtelo de nuevo más tarde.');
            return redirect()->back();
        }

        // Mensaje para reiniciar sesión
        session()->flash('reiniciar', true);

        return redirect()->route('configuration.index');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $user_prometeo = User::where('name', 'prometeo')->first();
            if ($user_prometeo) {
                $user_prometeo->ip = null;
                $user_prometeo->port = null;
                $user_prometeo->save();
            }

            $user_cambio = User::where('name', 'ccm')->first();
            if ($user_cambio) {
                $user_cambio->ip = null;
                $user_cambio->port = null;
                $user_cambio->save();
            }

            $user_comDataHost = User::where('name', 'admin')->first();
            if ($user_comDataHost) {
                $user_comDataHost->ip = null;
                $user_comDataHost->port = null;
                $user_comDataHost->save();
            }

            session()->flash('success', 'Datos eliminados correctamente.');
        } catch (\Exception $exception) {
            //Log::error($exception);
            session()->flash('error', 'Error al eliminar los datos. Inténtelo de nuevo.');
        }

        return redirect()->route('configuration.index');
    }

    // Para obtener datos de servidores en modo automatico
    public function buscar()
    {
        $user_cambio = User::where('name', 'ccm')->first();

        $filePath = 'C:\Gistra\SMI2000\Setup-TicketController\preferences.cfg';

        if (file_exists($filePath)) {


            $fileContent = file_get_contents($filePath);

            if (preg_match('/<ServerIP>(.*?)<\/ServerIP>/', $fileContent, $matches)) {
                $user_cambio->ip = $matches[1];
            } else {
                $user_cambio->ip = '0.0.0.0';
            }

            $user_cambio->port = 3306;
        }

        $user_comDataHost = User::where('name', 'admin')->first();
        if ($user_comDataHost) {
            $user_comDataHost->ip = $this->getLocalIp();
            $user_comDataHost->port = 3506;
        } else {
            // Si no se encuentra, se crea un usuario por defecto
            $user_comDataHost = new User;
            $user_comDataHost->ip = $this->getLocalIp();
            $user_comDataHost->port = 3506;
        }

        $user_prometeo = User::where('name', 'prometeo')->first();
        if (!$user_prometeo) {
            // Si no se encuentra en la BD, se asignan valores por defecto
            $user_prometeo = new User;
            $user_prometeo->ip = "0.0.0.0";
            $user_prometeo->port = 0;
        }
        // Obtener datos de Local, zona, delegacion
        $disposicion = getDisposicion();

        // Obtener nombre de compania
        $company = getCompany();

        $users = User::whereNotNull('email')
            ->where('email', '!=', '')
            ->with('clients') // Cargar la relación clients
            ->get();

        $data = [
            'users' => $users,
            'user_prometeo' => $user_prometeo,
            'user_cambio' => $user_cambio,
            'user_comDataHost' => $user_comDataHost,
            'locales' => $disposicion['locales'],
            'name_zona' =>  $disposicion['name_zona'],
            'name_delegation' => $disposicion['name_delegation'],
            'company' => $company
        ];

        return view('configuration.index', compact('data'));
    }

    // function para obtener datos de local ip
    // @return $localIp
    private function getLocalIp()
    {

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

            $output = shell_exec('ipconfig');  // Para windows

            if (preg_match('/IPv4.*?:\s*([0-9.]+)/', $output, $matches)) {
                $localIp = $matches[1];
            }
            return $localIp;
        } elseif (strtoupper(substr(PHP_OS, 0, 6)) === 'LINUX') {

            // Ejecutar el comando 'ip addr show' y capturar la salida
            $output = shell_exec('ip addr show');

            // Usar preg_match para encontrar la dirección IP que no sea 127.0.0.1
            if (preg_match('/inet\s+([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)\s/', $output, $matches)) {
                $localIp = $matches[1]; // La dirección IP encontrada
            }

            //dd($localIp);

            return isset($localIp) ? $localIp : null; // Retorna la IP o null si no se encontró
        }
    }





    // function para guardar nombre y IP compania
    // @return Response con estado de resultado operación

    public function guardarCompania(Request $request)
    {

        try {

            $company = $request->input('$company');

            $companyNew = new Company();
            $companyNew->id = $request['id'];
            $companyNew->name = $request['name'];
            $companyNew->save();

            User::where('name', 'prometeo')->update([
                'ip' => $request['ip'],
                'port' => $request['port']
            ]);

            return response()->json(['message' => 'success'], 200);
        } catch (Exception $e) {
            //Log::info($e);
            return response()->json(['message' => 'error'], 400);
        }
    }


    // function para guardar datos de compania: delegaciones, zonas y locales
    // @return Response con estado de resultado operación
    public function guardarDatosCompania(Request $request)
    {

        try {

            DB::beginTransaction();

            $delegations = $request->delegations;
            // Log::info($delegations);

            foreach ($delegations as $delegation) {
                $delegationNew = new Delegation();
                $delegationNew->id = $delegation['id'];
                $delegationNew->name = $delegation['name'];
                $delegationNew->company_id = $delegation['company_id'];
                $delegationNew->save();

                $zones = $delegation['zones'];

                foreach ($zones as $zone) {

                    $zoneNew = new Zone();
                    $zoneNew->id = $zone['id'];
                    $zoneNew->name = $zone['name'];
                    $zoneNew->delegation_id = $zone['delegation_id'];
                    $zoneNew->save();

                    $locals = $zone['locals'];

                    foreach ($locals as $local) {

                        $localNew = new Local();
                        $localNew->id = $local['id'];
                        $localNew->name = $local['name'];
                        $localNew->zone_id = $local['zone_id'];
                        $localNew->dbconection = $local['dbconection'];
                        $localNew->idMachines = $local['idMachines'];
                        $localNew->save();
                    }
                }
                // Log::info($zones);
            }
            DB::commit();
            return response()->json(['message' => 'success'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            //Log::info($e);
            return response()->json(['message' => 'error'], 400);
        }
    }

    // metodo para traer los datos de client y guardarlo en la base de datos metodo de pruebas
    public function getDataClient(Request $request)
    {
        //dd($request->all());
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        // Buscar el usuario por su correo
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        // Validar la contraseña
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Contraseña incorrecta'], 401);
        }

        // Crear datos del cliente simulados
        $client = [
            'id' => 2,
            'user_id' => $user->id,
            'name' => "Cliente para {$user->name}",
            'client_secret' => bcrypt($request->password),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return response()->json(['client' => $client], 200);
    }

    // para guardar los datos de la petcion POST para obetener client y guardarlo en la base de datos
    public function saveClientData(Request $request)
    {
        //Log::error($request->all());

        $request->validate([
            'id' => 'required|integer',
            'user_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'client_secret' => 'required|string',
        ]);

        try {

            // buscar el usuario para cambiarle el id para que sea igual que en prometeo
            $user = User::where('email', $request->email)->first();
            $user->id = $request->user_id;
            $user->save();
            // Crear un nuevo registro en la tabla oauth_clients
            Client::create([
                'id' => $request->id,
                'user_id' => $request->user_id,
                'name' => $request->name,
                'secret' => $request->client_secret,
                'personal_access_client' => false,
                'password_client' => true,
                'revoked' => false,
            ]);

            return response()->json(['message' => 'Cliente guardado correctamente.'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al guardar el cliente: ' . $e->getMessage()], 500);
        }
    }
}
