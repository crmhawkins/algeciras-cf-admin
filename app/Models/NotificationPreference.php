<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = ['customer_id', 'category', 'email_enabled', 'push_enabled'];
    protected $casts = ['email_enabled' => 'bool', 'push_enabled' => 'bool'];

    public function customer() { return $this->belongsTo(Customer::class); }

    /** Categorías disponibles (sincronizar con frontend) */
    public static function categories(): array
    {
        return [
            'goals'            => 'Goles del Algeciras',
            'lineups'          => 'Alineación oficial',
            'news'             => 'Noticias del club',
            'store_offers'     => 'Ofertas tienda',
            'fanzone'          => 'FanZone (votación MVP)',
            'matchday_reminder'=> 'Recordatorio día de partido',
            'exclusive_content'=> 'Contenido exclusivo socios',
        ];
    }
}
