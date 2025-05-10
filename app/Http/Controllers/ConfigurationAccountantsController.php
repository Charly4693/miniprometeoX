<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\Acumulado;
use App\Models\Local;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfigurationAccountantsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {

            // Conexión con la base de datos externa
            $conexionComdata = nuevaConexionLocal('admin');

            if (!$conexionComdata) {
                return redirect()->back()->with("error", "Error: la conexión con ComData es nula o inválida.");
            }

            // Obtener todos los acumulados desde ComData
            try {
                $acumulados = DB::connection($conexionComdata)
                    ->table('acumulado')
                    ->orderBy('NumPlaca', 'ASC')
                    ->get();

                // Extraer valores únicos de NumPlaca desde ComData
                $numPlacas = $acumulados->pluck('NumPlaca')->unique()->values();
            } catch (\Exception $e) {
                return redirect()->back()->with("error", "Error leyendo la tabla Acumulados: " . $e->getMessage());
            }

            // Obtener máquinas
            $machines = Machine::where('type', 'single')
                ->orWhere('type', null)
                ->get();

            // Obtener los numPlacas ya asignados en nuestra base de datos
            $acumuladosLocales = Acumulado::pluck('NumPlaca', 'machine_id');

            // 🔹 Obtener los valores de `AnularPM` desde la tabla `nombres` del ComData
            $anularPMs = [];
            if ($acumuladosLocales->isNotEmpty()) {
                $anularPMs = DB::connection($conexionComdata)
                    ->table('nombres')
                    ->whereIn('NumPlaca', $acumuladosLocales)
                    ->pluck('AnularPM', 'NumPlaca'); // [NumPlaca => AnularPM]
            }

            // 🔹 Asignar `AnularPM` a cada máquina
            foreach ($machines as $machine) {
                $machine->AnularPM = $anularPMs[$acumuladosLocales[$machine->id] ?? null] ?? 0;
            }

            return view("configurationAccountants.index", compact("machines", "acumulados", "numPlacas", "acumuladosLocales"));
        } catch (\Exception $e) {
            return redirect()->back()->with("error", $e->getMessage());
        }
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
        try {
            $id_machine = (int) ($request->machine_id ?? 0);
            $machine = Machine::find($request->machine_id);

            if (!$id_machine) {
                return back()->with('error', 'No se proporcionó una máquina válida.');
            }

            // Obtener el número de placa desde el request
            $numPlaca = $request->numPlaca[$id_machine] ?? null;

            // Si NumPlaca es 0 o vacío, eliminar asociaciones y salir
            if (empty($numPlaca) || $numPlaca == "0") {
                if ($machine) {
                    $conexionComdata = nuevaConexionLocal('admin');

                    $existeEnExterna = DB::connection($conexionComdata)
                        ->table('nombres')
                        ->where('nombre', $machine->alias)
                        ->exists();

                    if ($existeEnExterna) {
                        DB::connection($conexionComdata)
                            ->table('nombres')
                            ->where('nombre', $machine->alias)
                            ->delete();
                    }
                }

                // Eliminar cualquier otra placa asociada a esta máquina en nuestra BD
                Acumulado::where('machine_id', $id_machine)->delete();

                session()->flash('success', 'La máquina ha sido desvinculada de sus antiguos contadores y de la tabla nombres del ComData.');
                return back();
            }

            // Verificar si ya existe un registro con el mismo NumPlaca en otra máquina
            $existe = Acumulado::where('NumPlaca', $numPlaca)
                ->where('machine_id', '!=', $id_machine)
                ->exists();

            if ($existe) {
                return back()->with('error', "La placa Nº$numPlaca ya está asociada a otra máquina, deberá desvincularla antes de reasignarla.");
            }

            // ✅ Verificar si NumPlaca es válido antes de llamar a checkAccumulated
            if (!empty($numPlaca) && $numPlaca != "0") {
                $resultado = $this->checkAccumulated($numPlaca, $machine->alias);

                if (!$resultado['success']) {
                    return back()->with('error', $resultado['message']);
                }

                $acumuladoExterno = $resultado['data']; // Datos obtenidos de la BD externa
            } else {
                $acumuladoExterno = null;
            }

            DB::transaction(function () use ($numPlaca, $request, $acumuladoExterno, $id_machine) {
                $machine = Machine::find($request->machine_id);
                $nombre = $machine->alias;
                $r_auxiliar = $request->r_auxiliar[$id_machine] ?? $machine->r_auxiliar ?? null;
                $AnularPM = $request->AnularPM[$id_machine] ?? null;

                $resultado = $this->sendAnularPM($nombre, $numPlaca, $r_auxiliar, $AnularPM);

                if (!$resultado['success']) {
                    throw new \Exception($resultado['message']);
                }

                // Guardar en la base de datos
                $camposActualizar = collect([
                    'NumPlaca' => $numPlaca,
                    'nombre' => $machine->alias ?? null,
                    'local_id' => $machine->local_id,
                    'machine_id' => $machine->id,
                    'updated_at' => now(),
                ])->merge(collect($acumuladoExterno)->only([
                    'entradas',
                    'salidas',
                    'CEntradas',
                    'CSalidas',
                    'acumulado',
                    'CAcumulado',
                    'OrdenPago',
                    'factor',
                    'PagoManual',
                    'HoraActual',
                    'EstadoMaquina',
                    'comentario',
                    'TipoProtocolo',
                    'version',
                    'e1c',
                    'e2c',
                    'e5c',
                    'e10c',
                    'e20c',
                    'e50c',
                    'e1e',
                    'e2e',
                    'e5e',
                    'e10e',
                    'e20e',
                    'e50e',
                    'e100e',
                    'e200e',
                    'e500e',
                    's1c',
                    's2c',
                    's5c',
                    's10c',
                    's20c',
                    's50c',
                    's1e',
                    's2e',
                    's5e',
                    's10e',
                    's20e',
                    's50e',
                    's100e',
                    's200e',
                    's500e',
                    'c10c',
                    'c20c',
                    'c50c',
                    'c1e',
                    'c2e',
                ])->map(fn($val) => $val ?? 0));

                Acumulado::updateOrCreate(
                    ['NumPlaca' => $numPlaca, 'local_id' => $machine->local_id],
                    $camposActualizar->toArray()
                );
            });

            return redirect()->route('configurationAccountants.index', $request->delegation_id)
                ->with('success', 'Máquina actualizada correctamente.');
        } catch (\Exception $e) {
            Log::error("❌ Error al actualizar la máquina", ['error' => $e->getMessage()]);
            return redirect()->route('configurationAccountants.index', $request->delegation_id)
                ->with('error', 'Error al actualizar la máquina: ' . $e->getMessage());
        }
    }





    /*public function storeAll(Request $request)
    {
        //Log::info($request->all());

        try {
            // Validar la solicitud
            $request->validate([
                'machines' => 'required|array',
                'machines.*.machine_id' => 'required|exists:machines,id',
                'machines.*.alias' => 'required',
                'machines.*.numPlaca' => 'required',
            ]);

            $local = Local::first();
            $conexionComdata = nuevaConexionLocal('admin');
            if (!$conexionComdata) {
                return response()->json(['success' => false, 'message' => 'Error de conexión con ComData.'], 500);
            }

            $deletedCount = 0;
            $createdCount = 0;

            foreach ($request->machines as $machineData) {
                $machineId = $machineData['machine_id'];
                $numPlaca = $machineData['numPlaca'];
                $alias = $machineData['alias'];

                // Si `numPlaca` es "0" o está vacío, eliminar todas las asociaciones de esa máquina
                if (empty($numPlaca) || $numPlaca == "0") {
                    $deletedCount += Acumulado::where('machine_id', $machineId)->count();
                    Acumulado::where('machine_id', $machineId)->delete();
                    continue;
                }

                // Verificar si la placa ya está en uso por otra máquina
                $existingAssociation = Acumulado::where('NumPlaca', $numPlaca)
                    ->where('machine_id', '!=', $machineId)
                    ->first();

                if ($existingAssociation) {
                    return response()->json([
                        'success' => false,
                        'message' => "El número de placa $numPlaca ya está asociado a otra máquina."
                    ], 400);
                }

                // Obtener los datos desde la base de datos externa
                $datosAcumulado = DB::connection($conexionComdata)
                    ->table('acumulado')
                    ->where('NumPlaca', $numPlaca)
                    ->first();

                // Si no se encuentran datos en la tabla externa, devolver error
                if (!$datosAcumulado) {
                    return response()->json([
                        'success' => false,
                        'message' => "No se encontraron datos en la tabla acumulado para el NumPlaca: $numPlaca."
                    ], 404);
                }

                // Eliminar cualquier otra placa asociada a esta máquina antes de guardar la nueva
                Acumulado::where('machine_id', $machineId)->delete();
                $createdCount++;

                // Insertar o actualizar los datos en la base de datos local
                Acumulado::updateOrCreate(
                    ['machine_id' => $machineId],
                    [
                        'NumPlaca' => $numPlaca,
                        'local_id' => $local->id,
                        'nombre' => $alias,
                        'entradas' => $datosAcumulado->entradas ?? 0,
                        'salidas' => $datosAcumulado->salidas ?? 0,
                        'CEntradas' => $datosAcumulado->CEntradas ?? 0,
                        'CSalidas' => $datosAcumulado->CSalidas ?? 0,
                        'acumulado' => $datosAcumulado->acumulado ?? 0,
                        'CAcumulado' => $datosAcumulado->CAcumulado ?? 0,
                        'OrdenPago' => $datosAcumulado->OrdenPago ?? 0,
                        'factor' => $datosAcumulado->factor ?? 1,
                        'PagoManual' => $datosAcumulado->PagoManual ?? 0,
                        'HoraActual' => $datosAcumulado->HoraActual ?? now(),
                        'EstadoMaquina' => $datosAcumulado->EstadoMaquina ?? 'Desconocido',
                        'comentario' => $datosAcumulado->comentario ?? null,
                        'TipoProtocolo' => $datosAcumulado->TipoProtocolo ?? null,
                        'version' => $datosAcumulado->version ?? null,
                        'e1c' => $datosAcumulado->e1c ?? 0,
                        'e2c' => $datosAcumulado->e2c ?? 0,
                        'e5c' => $datosAcumulado->e5c ?? 0,
                        'e10c' => $datosAcumulado->e10c ?? 0,
                        'e20c' => $datosAcumulado->e20c ?? 0,
                        'e50c' => $datosAcumulado->e50c ?? 0,
                        'e1e' => $datosAcumulado->s1e ?? 0,
                        'e2e' => $datosAcumulado->s2e ?? 0,
                        'e5e' => $datosAcumulado->s5e ?? 0,
                        'e10e' => $datosAcumulado->s10e ?? 0,
                        'e20e' => $datosAcumulado->s20e ?? 0,
                        'e50e' => $datosAcumulado->s50e ?? 0,
                        'e100e' => $datosAcumulado->s100e ?? 0,
                        'e200e' => $datosAcumulado->s200e ?? 0,
                        'e500e' => $datosAcumulado->s500e ?? 0,
                        's1c' => $datosAcumulado->s1c ?? 0,
                        's2c' => $datosAcumulado->s2c ?? 0,
                        's5c' => $datosAcumulado->s5c ?? 0,
                        's10c' => $datosAcumulado->s10c ?? 0,
                        's20c' => $datosAcumulado->s20c ?? 0,
                        's50c' => $datosAcumulado->s50c ?? 0,
                        's1e' => $datosAcumulado->s1e ?? 0,
                        's2e' => $datosAcumulado->s2e ?? 0,
                        's5e' => $datosAcumulado->s5e ?? 0,
                        's10e' => $datosAcumulado->s10e ?? 0,
                        's20e' => $datosAcumulado->s20e ?? 0,
                        's50e' => $datosAcumulado->s50e ?? 0,
                        's100e' => $datosAcumulado->s100e ?? 0,
                        's200e' => $datosAcumulado->s200e ?? 0,
                        's500e' => $datosAcumulado->s500e ?? 0,
                        'updated_at' => now(),
                    ]
                );
            }

            // Construcción del mensaje de respuesta
            if ($deletedCount > 0 && $createdCount > 0) {
                $message = "Se eliminaron $deletedCount asociaciones y se asociaron $createdCount placas exitosamente.";
            } elseif ($deletedCount > 0) {
                $message = "Se eliminaron $deletedCount asociaciones de placas.";
            } elseif ($createdCount > 0) {
                $message = "Se asociaron $createdCount placas exitosamente.";
            } else {
                $message = "No se realizaron cambios.";
            }

            return response()->json(['success' => true, 'message' => $message], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }*/

    public function storeAll(Request $request)
    {
        try {
            Log::info("📌 Iniciando storeAll", ['request' => $request->all()]);

            // Comenzar una transacción para garantizar la atomicidad
            DB::transaction(function () use ($request) {
                foreach ($request->machines as $machineData) {
                    Log::info("🔍 Procesando máquina", ['machineData' => $machineData]);

                    $id_machine = $machineData['machine_id'] ?? null;
                    Log::info("🔹 ID de máquina extraído", ['id_machine' => $id_machine]);

                    if (!$id_machine) {
                        throw new \Exception("ID de máquina no válido");
                    }

                    $machine = Machine::find($id_machine);
                    Log::info("🔍 Máquina encontrada en BD", ['machine' => $machine]);

                    if (!$machine) {
                        throw new \Exception("La máquina con ID $id_machine no existe.");
                    }

                    $numPlaca = $machineData['numPlaca'] ?? null;
                    Log::info("🔹 Número de placa extraído", ['numPlaca' => $numPlaca]);

                    if (empty($numPlaca) || $numPlaca == "0") {
                        Log::warning("⚠ Eliminando asociaciones de máquina sin NumPlaca", ['machine_id' => $id_machine]);

                        $conexionComdata = nuevaConexionLocal('admin');
                        $existeEnExterna = DB::connection($conexionComdata)
                            ->table('nombres')
                            ->where('nombre', $machine->alias)
                            ->exists();

                        if ($existeEnExterna) {
                            Log::info("🗑 Eliminando máquina en BD externa", ['alias' => $machine->alias]);
                            DB::connection($conexionComdata)
                                ->table('nombres')
                                ->where('nombre', $machine->alias)
                                ->delete();
                        }

                        Acumulado::where('machine_id', $id_machine)->delete();
                        continue;
                    }

                    $existe = Acumulado::where('NumPlaca', $numPlaca)
                        ->where('machine_id', '!=', $id_machine)
                        ->exists();

                    if ($existe) {
                        throw new \Exception("La placa Nº$numPlaca ya está asociada a otra máquina.");
                    }

                    $resultado = $this->checkAccumulated($numPlaca, $machine->alias);
                    Log::info("🔍 Resultado checkAccumulated", ['resultado' => $resultado]);

                    if (!$resultado['success']) {
                        throw new \Exception($resultado['message']);
                    }

                    $acumuladoExterno = $resultado['data'] ?? [];
                    Log::info("📊 Datos obtenidos de BD externa", ['acumuladoExterno' => $acumuladoExterno]);

                    $r_auxiliar = $machineData['r_auxiliar'] ?? $machine->r_auxiliar ?? 0;
                    $AnularPM = $machineData['AnularPM'] ?? 0;
                    Log::info("🔹 Parámetros AnularPM", ['r_auxiliar' => $r_auxiliar, 'AnularPM' => $AnularPM]);

                    $resultado = $this->sendAnularPM($machine->alias, $numPlaca, $r_auxiliar, $AnularPM);
                    Log::info("📡 Resultado sendAnularPM", ['resultado' => $resultado]);

                    if (!$resultado['success']) {
                        throw new \Exception($resultado['message']);
                    }

                    $camposActualizar = collect([
                        'NumPlaca' => $numPlaca,
                        'nombre' => $machine->alias ?? null,
                        'local_id' => $machine->local_id,
                        'machine_id' => $machine->id,
                        'updated_at' => now(),
                    ])->merge(collect($acumuladoExterno)->only([
                        'entradas',
                        'salidas',
                        'CEntradas',
                        'CSalidas',
                        'acumulado',
                        'CAcumulado',
                        'OrdenPago',
                        'factor',
                        'PagoManual',
                        'HoraActual',
                        'EstadoMaquina',
                        'comentario',
                        'TipoProtocolo',
                        'version',
                        'e1c',
                        'e2c',
                        'e5c',
                        'e10c',
                        'e20c',
                        'e50c',
                        'e1e',
                        'e2e',
                        'e5e',
                        'e10e',
                        'e20e',
                        'e50e',
                        'e100e',
                        'e200e',
                        'e500e',
                        's1c',
                        's2c',
                        's5c',
                        's10c',
                        's20c',
                        's50c',
                        's1e',
                        's2e',
                        's5e',
                        's10e',
                        's20e',
                        's50e',
                        's100e',
                        's200e',
                        's500e',
                        'c10c',
                        'c20c',
                        'c50c',
                        'c1e',
                        'c2e'
                    ])->map(fn($val) => $val ?? 0));

                    Log::info("📌 Datos a actualizar en BD", ['camposActualizar' => $camposActualizar]);

                    Acumulado::updateOrCreate(
                        ['NumPlaca' => $numPlaca, 'local_id' => $machine->local_id],
                        $camposActualizar->toArray()
                    );
                }
            });

            Log::info("✅ Máquinas actualizadas correctamente.");
            return response()->json(['success' => true, 'message' => 'Máquinas actualizadas correctamente.'], 200);
        } catch (\Exception $e) {
            Log::error("❌ Error al actualizar las máquinas", ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al actualizar las máquinas: ' . $e->getMessage()], 500);
        }
    }









    public function clearAll(Request $request)
    {
        // Verificamos si en la petición se indica que se deben eliminar asociaciones
        if ($request->has('machines')) {
            foreach ($request->machines as $machine) {
                if (isset($machine['numPlaca']) && $machine['numPlaca'] == "0") {
                    // Eliminar la máquina que tenía una placa asociada
                    Acumulado::where('machine_id', $machine['machine_id'])->delete();
                }
            }
        }

        return response()->json([
            'message' => 'Se han eliminado las asociaciones de máquinas con numPlaca = 0.'
        ], 200);
    }

    public function checkAccumulated($numPlaca, $name)
    {
        $conexion = nuevaConexionLocal('admin');

        if (!$conexion) {
            return ['success' => false, 'message' => 'Error de conexión con la base de datos externa.'];
        }

        // Obtener el registro que coincida con el NumPlaca y el nombre
        $acumuladoExterno = DB::connection($conexion)
            ->table('acumulado')
            ->where('NumPlaca', $numPlaca)
            ->where('nombre', $name)
            ->first();

        if (!$acumuladoExterno) {
            return ['success' => false, 'message' => "No se encontraron datos con el NumPlaca: $numPlaca y el nombre: $name especificados."];
        }

        return ['success' => true, 'data' => $acumuladoExterno];
    }





    // Método para anular el pago manual en la base de datos del comdata
    // podemos editarlo para que la mauina ya este comprobada y en ve de enviarle el id_machine le pasemos en numplaca
    public function sendAnularPM($nombre, $NumPlaca, $r_auxiliar, $AnularPM)
    {
        //dd($nombre);
        Log::info("🔄 Ejecutando sendAnularPM", compact('NumPlaca', 'r_auxiliar', 'AnularPM'));

        $conexion = nuevaConexionLocal('admin');

        try {
            $registro = DB::connection($conexion)->table('nombres')->where('NumPlaca', $NumPlaca)->first();

            if ($registro) {
                DB::connection($conexion)->table('nombres')
                    ->where('NumPlaca', $NumPlaca)
                    ->update([
                        'nombre'    => $nombre,
                        'TypeIsAux' => $r_auxiliar ?? 0,
                        'AnularPM'  => $AnularPM
                    ]);

                Log::info("🔄 Registro actualizado en `nombres` para NumPlaca: $NumPlaca");
                return ['success' => true, 'message' => 'Registro actualizado correctamente.'];
            } else {
                DB::connection($conexion)->table('nombres')->insert([
                    'NumPlaca'   => $NumPlaca,
                    'nombre'     => $nombre, // Ajustar si hay una forma de obtener el nombre
                    'TypeIsAux'  => $r_auxiliar ?? 0,
                    'AnularPM'   => $AnularPM
                ]);

                Log::info("🆕 Nuevo registro insertado en `nombres` para NumPlaca: $NumPlaca");
                return ['success' => true, 'message' => 'Nuevo registro insertado correctamente.'];
            }
        } catch (\Exception $e) {
            Log::error("❌ Error en sendAnularPM: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al procesar la operación: ' . $e->getMessage()];
        }
    }
}
