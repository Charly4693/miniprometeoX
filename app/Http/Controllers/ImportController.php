<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Machine;
use App\Models\MachinePrometeo;
use App\Models\Local;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{

    public function index()
    {
        try {
            // Obtener todas las máquinas de MiniPrometeo (sin filtrar aún)
            $machines_all = Machine::all();

            // Filtrar solo type 'single' o null para la vista
            $machines = $machines_all->whereIn('type', ['single', null]);

            $importBD = false;
            $message = "";
            $local = Local::all();

            if (count($local) !== 1) {
                return redirect()->back()->with("errorConfiguracion", "No hay configuración del sistema");
            }

            // Obtener todas las máquinas de Prometeo (sin filtrar)
            $machines_prometeo = collect();
            try {
                $connection = DB::connection('remote_prometeo_test');
                $machines_prometeo = $connection->table('machines')
                    ->where('local_id', $local[0]->id)
                    ->get();
            } catch (\Exception $exception) {
                //Log::info($exception);
                $message = "No hay conexión";
            }

            // Comparar todas las máquinas
            $diferencia = [];
            $faltantes = [];
            if ($machines_prometeo->isNotEmpty()) {
                $diferencias = $this->comparar($machines_all, $machines_prometeo);
                $diferencia = $diferencias['diferencia'];

                // Guardamos la máquina completa en faltantes
                $faltantes = $machines_prometeo->whereNotIn('id', $machines_all->pluck('id')->toArray());
            }

            // Filtrar las máquinas de la vista (solo type 'single' o null)
            $machines_prometeo_filtered = $machines_prometeo->filter(function ($machine) {
                return in_array($machine->type, ['single', null]);
            });

            return view("import.index", [
                "machines" => $machines,
                "machines_prometeo" => $machines_prometeo_filtered,
                "importBD" => $importBD,
                "message" => $message,
                "diferencia" => $diferencia,
                "faltantes" => $faltantes // Contiene las máquinas completas
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with("error", $e->getMessage());
        }
    }

    private function comparar($machines, $machines_prometeo)
    {
        $identificadoresMachine = $machines->pluck('identificador');
        $identificadoresMachinePrometeo = $machines_prometeo->pluck('identificador');

        // Máquinas que están en MiniPrometeo pero no en Prometeo
        $diferencia = $identificadoresMachine->diff($identificadoresMachinePrometeo);

        // Máquinas que están en Prometeo pero no en MiniPrometeo
        $faltantes = $identificadoresMachinePrometeo->diff($identificadoresMachine);

        // Convertir faltantes a alias
        $faltantes = $faltantes->map(fn($id) => $machinesPrometeoMap[$id] ?? $id)->values()->toArray();

        return [
            'diferencia' => $diferencia->values()->toArray(), // Sigue devolviendo identificadores
            'faltantes' => $faltantes, // Ahora devuelve alias en lugar de identificadores
        ];
    }


    public function store()
    {
        try {
            $importBD = false;
            $message = "";
            $local = Local::all();

            if ($local->count() !== 1) {
                return redirect()->back()->with("errorConfiguracion", "No hay configuración de sistema");
            }

            try {
                $connection = DB::connection('remote_prometeo_test');

                $machines_prometeo = $connection->table('machines')
                    ->where('local_id', $local[0]->id)
                    ->get();
            } catch (\Exception $exception) {
                //Log::info($exception);
                return redirect()->back()->with("errorConfiguracion", "No hay conexión con Prometeo");
            }

            // Obtener los identificadores de las máquinas de Prometeo
            $machines_prometeo_ids = $machines_prometeo->pluck('identificador')->toArray();

            // Eliminar máquinas que no están en Prometeo
            Machine::whereNotIn('identificador', $machines_prometeo_ids)->delete();
            //dd($machines_prometeo);
            // Insertar o actualizar máquinas de Prometeo
            foreach ($machines_prometeo as $machine) {

                Machine::updateOrCreate(
                    ['identificador' => $machine->identificador], // Clave única para encontrar la máquina
                    [
                        'id' => $machine->id,
                        'name' => $machine->name,
                        'local_id' => $machine->local_id,
                        'bar_id' => $machine->bar_id ?? null, // Si el valor es 0 o no válido, lo pone en NULL
                        'delegation_id' => $machine->delegation_id,
                        'type' => $machine->type,
                        'parent_id' => $machine->parent_id,
                        'r_auxiliar' => $machine->r_auxiliar,
                        'alias' => Machine::where('identificador', $machine->identificador)->value('alias') ?? $machine->alias
                    ]
                );
            }

            $machines = Machine::where('type', 'single')
                ->orWhereNull('type')
                ->get();

            $importBD = true;
            $message = "La importación de datos se ha realizado correctamente.";

            // Filtrar máquinas de Prometeo que no son 'roulette' ni 'parent'
            $machines_prometeo_filtered = $machines_prometeo->filter(function ($item) {
                return $item->type == 'single' || $item->type === null;
            });

            return view("import.index", [
                "machines" => $machines,
                "machines_prometeo" => $machines_prometeo_filtered,
                "importBD" => $importBD,
                "message" => $message,
                "diferencia" => []
            ]);
        } catch (\Exception $e) {
            //Log::info($e->getMessage());
            return redirect()->back()->with("error", $e->getMessage());
        }
    }
}
