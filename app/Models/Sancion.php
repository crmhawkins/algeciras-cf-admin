<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sancion extends Model
{
    protected $table = 'sanciones';

    protected $fillable = ['customer_id', 'motivo', 'importe', 'fecha', 'activa'];

    protected $casts = [
        'fecha'   => 'date',
        'importe' => 'decimal:2',
        'activa'  => 'bool',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
