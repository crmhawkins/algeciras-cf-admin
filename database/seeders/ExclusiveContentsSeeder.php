<?php

namespace Database\Seeders;

use App\Models\ExclusiveContent;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExclusiveContentsSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'title'        => 'Entrevista exclusiva al capitán',
                'category'     => 'video',
                'excerpt'      => 'Charla privada con el capitán: el vestuario, los ascensos y lo que viene.',
                'body'         => "Hablamos a corazón abierto con nuestro capitán sobre la temporada, el grupo humano del vestuario, el papel de la afición en el Nuevo Mirador y los objetivos del bloque para esta campaña.\n\nUn contenido íntimo, solo accesible para abonados.",
                'cover_image'  => 'img/club/escudo.png',
                'external_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            ],
            [
                'title'         => '20% descuento en la tienda oficial',
                'category'      => 'descuento',
                'excerpt'       => 'Código exclusivo para abonados: 20% en toda la equipación y merchandising.',
                'body'          => "Como abonado del Algeciras CF tienes un 20% de descuento permanente en la tienda oficial. Aplica al hacer el pedido usando tu código de socio.\n\nNo acumulable con otras promociones. Válido hasta fin de temporada.",
                'cover_image'   => 'img/club/escudo.png',
                'discount_code' => 'SOCIO20',
            ],
            [
                'title'       => 'Próxima sesión a puerta cerrada con la plantilla',
                'category'    => 'noticia',
                'excerpt'     => 'Solo abonados podrán asistir al entrenamiento previo al partido grande de la jornada.',
                'body'        => "El club abrirá las puertas del campo de entrenamiento a sus abonados para presenciar la sesión previa al choque de la jornada. Aforo limitado, primero llegan, primero entran.\n\nLas confirmaciones se enviarán por email al correo registrado como socio.",
                'cover_image' => 'img/club/escudo.png',
            ],
            [
                'title'       => 'Cena anual con la directiva',
                'category'    => 'evento',
                'excerpt'     => 'Reserva tu plaza en la cena anual del club. Plazas muy limitadas.',
                'body'        => "Como cada temporada, la directiva organiza una cena para socios donde se hace balance del año, se presentan los proyectos en curso y se comparte mesa con jugadores y cuerpo técnico.\n\nFecha y lugar se confirmarán en cuanto el aforo esté cerrado.",
                'cover_image' => 'img/club/escudo.png',
            ],
            [
                'title'       => 'Sorteo: camiseta firmada del capitán',
                'category'    => 'sorteo',
                'excerpt'     => 'Participa en el sorteo de una camiseta oficial firmada por el capitán del primer equipo.',
                'body'        => "Solo abonados con la cuota al día pueden participar en este sorteo exclusivo. Una sola participación por socio.\n\nEl ganador será anunciado al cierre de la jornada por los canales oficiales del club y contactado por email.",
                'cover_image' => 'img/club/escudo.png',
            ],
            [
                'title'       => 'Renovación de la equipación 26-27',
                'category'    => 'noticia',
                'excerpt'     => 'Adelanto exclusivo del diseño de la nueva equipación de la temporada que viene.',
                'body'        => "Antes de hacerlo público, te enseñamos el adelanto del diseño de la nueva equipación para la temporada 26-27. Materiales reciclados, escudo bordado y un guiño al barrio que es seña del club.\n\nReservas anticipadas para abonados disponibles próximamente.",
                'cover_image' => 'img/club/escudo.png',
            ],
        ];

        foreach ($items as $data) {
            $data['slug']         = Str::slug($data['title']);
            $data['publish_at']   = Carbon::now()->subDays(rand(1, 30));
            $data['is_published'] = true;

            ExclusiveContent::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
