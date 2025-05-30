<?php

namespace App\Http\Controllers;

use App\Models\Local;
use App\Models\Machine;
use App\Models\TypeAlias;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfigurationTypeAliasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $local = Local::first();

        // Conexión remota a la base de datos
        $conexion = nuevaConexionLocal('ccm');

        // Obtener todos los tipos de tickets
        $tickets = DB::connection($conexion)
            ->table('tickets')
            ->orderBy('Type', 'DESC')
            ->get();

        // Obtener todos los alias de tipo asociado a las máquinas
        $typeAlias = DB::table('type_alias')->get()->keyBy('type'); // Agrupa los alias por tipo

        // Obtener todas las máquinas
        $machines = Machine::all(); // Recupera todos los registros del modelo Machine

        // Crear una colección para almacenar los tickets únicos
        $uniqueTickets = collect();

        foreach ($tickets as $ticket) {
            // Verifica si ya existe en la colección
            if (!$uniqueTickets->contains('Type', $ticket->Type)) {
                $uniqueTickets->push($ticket); // Agrega el ticket completo
            }
        }

        // Pasar los datos a la vista
        return view('configurationTypeAlias.index', compact('local', 'uniqueTickets', 'machines', 'typeAlias'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        //dd($request->all());

        DB::beginTransaction();

        try {
            // Validar la entrada
            $request->validate([
                'type' => 'required|string',
                'alias' => 'required|string',
                'id_machine' => 'required|exists:machines,id',
            ]);

            // Crear un nuevo registro en la tabla type_alias
            $typeAlias = new TypeAlias();
            $typeAlias->type = $request->type;
            $typeAlias->alias = $request->alias;
            $typeAlias->id_machine = $request->id_machine;
            $typeAlias->save(); // Guardar el registro en la base de datos

            // Mensaje de éxito con el tipo de ticket y alias
            session()->flash('success', "Configuración actualizada exitosamente: tipo de ticket '{$request->type}' asociado a su alias '{$request->alias}'.");

            // Confirmar la transacción
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();

            // Mensaje de error con el tipo de ticket y alias
            session()->flash('error', "Error al actualizar la configuración para el tipo de ticket '{$request->type}' y alias '{$request->alias}'. Inténtelo nuevamente.");
            // Aquí puedes registrar el error si es necesario
            // Log::error('Error en la transacción de base de datos: ' . $exception->getMessage());

            return redirect()->back();
        }

        return redirect()->route('configurationTypeAlias.index');
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $type)
    {
        // Iniciar la transacción de la base de datos
        DB::beginTransaction();

        try {
            // Validar los datos de entrada
            $request->validate([
                'id_machine' => 'required|exists:machines,id',
                'alias' => 'required|string|max:255',
            ]);

            // Buscar el registro por el campo 'type'
            $typeAlias = TypeAlias::where('type', $type)->first();

            // Verificar si se encontró el registro
            if (!$typeAlias) {
                session()->flash('error', "No se encontró el tipo de ticket '{$type}'.");
                return redirect()->back();
            }

            // Actualizar los campos del registro
            $typeAlias->id_machine = $request->id_machine;
            $typeAlias->alias = $request->alias;
            $typeAlias->save(); // Guardar los cambios

            // Mensaje de éxito con el tipo y alias
            session()->flash('success', "Configuración actualizada exitosamente: tipo de ticket '{$type}' asociado a su alias '{$typeAlias->alias}'.");

            // Confirmar la transacción
            DB::commit();
        } catch (\Exception $exception) {
            // Revertir la transacción en caso de error
            DB::rollBack();

            // Mensaje de error con el tipo de ticket y alias
            session()->flash('error', "Error al actualizar la configuración para el tipo de ticket '{$type}' y alias '{$request->alias}'. Inténtelo nuevamente.");
            // Aquí puedes registrar el error si es necesario
            // Log::error('Error en la transacción de base de datos: ' . $exception->getMessage());

            return redirect()->back();
        }

        // Redirigir a la lista de configuraciones
        return redirect()->route('configurationTypeAlias.index');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $type)
    {
        // Buscar el alias antes de eliminarlo, usando una comparación que distinga mayúsculas y minúsculas
        $typeAlias = TypeAlias::whereRaw('BINARY type = ?', [$type])->first();

        if ($typeAlias) {
            $alias = $typeAlias->alias; // Guardar alias antes de eliminar
            $typeAlias->delete(); // Eliminar el registro

            return redirect()->back()->with('success', "Eliminada la asociación de Tipo \"$type\" y el Alias \"$alias\" correctamente.");
        } else {
            return redirect()->back()->with('error', "No se encontró la asociación para el Tipo \"$type\".");
        }
    }
}
