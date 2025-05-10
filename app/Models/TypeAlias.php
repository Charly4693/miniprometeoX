<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TypeAlias extends Model
{
    use HasFactory;

    protected $table =  'type_alias';


    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
