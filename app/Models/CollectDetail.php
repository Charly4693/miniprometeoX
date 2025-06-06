<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collectdetail extends Model
{
    use HasFactory;

    // Definir la tabla correspondiente
    protected $table = 'collectdetails';

    // Definir los campos que son asignables en masa
    protected $fillable = [
        'local_id',
        'UserMoney',
        'Name',
        'Money1',
        'Money2',
        'Money3',
        'CollectDetailType',
        'State',
    ];

    // Definir la relación con el modelo Local
    public function local()
    {
        return $this->belongsTo(Local::class, 'local_id');
    }
}
