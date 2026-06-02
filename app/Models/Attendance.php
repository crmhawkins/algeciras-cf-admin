<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Una entrada al estadio (ticket + partido) registrada por un operador.
 *
 * Diferencia clave vs MatchAttendance: es per-ticket (UNIQUE ticket+match)
 * en lugar de per-customer. Permite detectar el caso "abonado intenta
 * reentrar al mismo partido con el mismo QR".
 */
class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'match_id',
        'scanned_at',
        'scanned_by_user_id',
        'gate_id',
        'meta',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'meta' => 'array',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function match()
    {
        return $this->belongsTo(FootballMatch::class, 'match_id');
    }

    public function scannedBy()
    {
        return $this->belongsTo(User::class, 'scanned_by_user_id');
    }
}
