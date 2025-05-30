<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'year',
        'serie',
        'local_id',
        'bar_id',
        'alias',
        'identificador',
        'delegation_id',
        'type',
        'r_auxiliar',
        'parent_id',
        'created_at',
        'updated_at',
    ];

    /**
     * Relación con el modelo Local.
     */
    public function local()
    {
        return $this->belongsTo(Local::class);
    }

    /**
     * Relación con el modelo Bar.
     */
    public function bar()
    {
        return $this->belongsTo(Bar::class);
    }

    public function delegation()
    {
        return $this->belongsTo(Delegation::class);
    }

    public function typeAlias()
    {
        return $this->hasMany(TypeAlias::class);
    }

    public function auxiliars()
    {
        return $this->hasMany(Auxiliar::class);
    }

    // Relación para el "padre" de la máquina
    public function parent()
    {
        return $this->belongsTo(Machine::class, 'parent_id');
    }

    // Relación para los "hijos" de una máquina
    public function children()
    {
        return $this->hasMany(Machine::class, 'parent_id');
    }

    // Método para verificar si es de tipo "parent"
    public function isParent()
    {
        return $this->type === 'parent';
    }

    public function isSingle()
    {
        return $this->type === 'single';
    }

    // Método para verificar si es de tipo "roulette"
    public function isRoulette()
    {
        return $this->type === 'roulette';
    }

    // Método para obtener máquinas de tipo "parent" o "roulette"
    public static function getMachinesByType($type)
    {
        return self::where('type', $type)->get();
    }

    // Método para obtener los hijos de una máquina dependiendo de su tipo
    public function getChildrenByParentType()
    {
        if ($this->isParent()) {
            // Si la máquina es de tipo "parent", devuelve los hijos
            return $this->children; // Devuelve todos los hijos
        } elseif ($this->isRoulette()) {
            // Si la máquina es de tipo "roulette", devuelve los hijos
            return $this->children; // También puedes filtrar por otros criterios si es necesario
        }

        return collect(); // Si no es de tipo "parent" o "roulette", devuelve una colección vacía
    }

    // Método para obtener todos los hijos, sin importar el tipo
    public function getAllChildren()
    {
        return $this->children; // Devuelve todos los hijos sin importar el tipo
    }

    public function addChild(array $childData)
    {
        // Verificar si la máquina actual puede tener hijos
        if (!$this->isParent() && !$this->isRoulette()) {
            throw new \Exception("Solo las máquinas de tipo 'parent' o 'roulette' pueden tener hijos.");
        }

        // Completar los datos del hijo con el ID del padre
        $childData['parent_id'] = $this->id;

        // Crear y retornar el hijo
        return self::create($childData);
    }

    public function getIdentificadorAsArray()
    {
        $identificador = explode(':', $this->identificador);

        return [
            'mode' => $identificador[0] ?? null,
            'codigo' => $identificador[1] ?? null,
            'serie' => $identificador[2] ?? null,
            'numero' => $identificador[3] ?? null,
        ];
    }

    // Método para contar los hijos
    public function countChildren()
    {
        // Contar los hijos asociados con esta máquina
        return $this->children()->count();
    }

    // En el modelo Machine

    public static function orderMachinesWithChildren($machines)
    {
        $orderedMachines = [];

        // Ordena las máquinas: primero las madres (roulette y parent), luego las hijas
        foreach ($machines as $machine) {
            if (in_array($machine->type, ['roulette', 'parent'])) {
                // Agrega la madre
                $orderedMachines[] = $machine;

                // Agrega sus hijos debajo de ella (si existen)
                foreach ($machine->children as $child) {
                    $orderedMachines[] = $child;
                }
            } elseif ($machine->type === 'single') {
                // Las máquinas individuales se agregan directamente
                $orderedMachines[] = $machine;
            }
        }

        return $orderedMachines;
    }

    public static function getOnlyChildren($id){

        $machinesChildren = self::where('local_id', $id)
            ->where(function ($query) {
                $query->whereNotIn('type', ['roulette', 'parent']) // Excluir 'roulette' y 'parent'
                    ->orWhereHas('parent', function ($q) {
                        $q->whereIn('type', ['roulette', 'parent']); // Incluir hijos de estos tipos
                    });
            })
            ->get();
        return $machinesChildren;
    }

    public function acumulado()
    {
        return $this->hasOne(Acumulado::class);
    }

    public function plate()
    {
        return $this->hasOne(Plate::class);
    }


}
